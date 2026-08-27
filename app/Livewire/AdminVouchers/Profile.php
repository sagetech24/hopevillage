<?php

namespace App\Livewire\AdminVouchers;

use App\Models\AdminVoucher;
use App\Models\User;
use App\Services\PointsService;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Profile extends Component
{
    public $voucherCode;

    public $voucher;

    public bool $showAwardModal = false;

    public string $memberSearch = '';

    /** @var array<int, int|string> */
    public array $selectedMemberIds = [];

    public string $awardReason = '';

    protected function rules(): array
    {
        return [
            'selectedMemberIds' => 'required|array|min:1',
            'selectedMemberIds.*' => 'integer|exists:users,id',
            'awardReason' => 'nullable|string|max:500',
        ];
    }

    public function mount($voucher_code)
    {
        $this->voucherCode = $voucher_code;
        $this->loadVoucher();
    }

    public function loadVoucher()
    {
        $this->voucher = AdminVoucher::with(['merchants', 'createdBy'])
            ->where('voucher_code', $this->voucherCode)
            ->firstOrFail();
    }

    public function openAwardModal(): void
    {
        $this->showAwardModal = true;
        $this->memberSearch = '';
        $this->selectedMemberIds = [];
        $this->awardReason = '';
        $this->loadVoucher();
    }

    public function closeAwardModal(): void
    {
        $this->showAwardModal = false;
        $this->memberSearch = '';
        $this->selectedMemberIds = [];
        $this->awardReason = '';
    }

    public function clearSelectedMembers(): void
    {
        $this->selectedMemberIds = [];
    }

    public function removeSelectedMember(int $memberId): void
    {
        $this->selectedMemberIds = collect($this->selectedMemberIds)
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === $memberId)
            ->values()
            ->all();
    }

    public function getRemainingForAwardProperty(): ?int
    {
        if ($this->voucher->usage_limit === null) {
            return null;
        }

        return max(0, (int) $this->voucher->usage_limit - (int) $this->voucher->usage_count);
    }

    public function updatedSelectedMemberIds(): void
    {
        $this->selectedMemberIds = collect($this->selectedMemberIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $remaining = $this->remainingForAward;
        if ($remaining === null) {
            return;
        }

        if ($remaining === 0) {
            $this->selectedMemberIds = [];
            $this->dispatch('notify', type: 'error', message: 'No vouchers remaining available for awarding.');

            return;
        }

        if (count($this->selectedMemberIds) > $remaining) {
            $this->selectedMemberIds = array_slice($this->selectedMemberIds, 0, $remaining);
            $this->dispatch(
                'notify',
                type: 'error',
                message: 'You can only select up to '.$remaining.' member'.($remaining === 1 ? '' : 's').' with the remaining vouchers.'
            );
        }
    }

    public function getSelectedMembersProperty()
    {
        $selectedIds = collect($this->selectedMemberIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($selectedIds === []) {
            return collect();
        }

        return User::query()
            ->where('user_type', 'member')
            ->whereIn('id', $selectedIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getAwardableMembersProperty()
    {
        $search = trim($this->memberSearch);

        if ($search === '') {
            return null;
        }

        $attachedUserIds = $this->voucher->users()->pluck('users.id');

        return User::query()
            ->where('user_type', 'member')
            ->whereNotIn('id', $attachedUserIds)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('fin', 'like', '%'.$search.'%');
            })
            ->orderBy('name')
            ->get();
    }

    public function awardToMembers(): void
    {
        $this->validate();

        $admin = auth()->user();
        if (! $admin) {
            $this->dispatch('notify', type: 'error', message: 'You must be logged in to award vouchers.');

            return;
        }

        $selectedIds = collect($this->selectedMemberIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $members = User::query()
            ->where('user_type', 'member')
            ->whereIn('id', $selectedIds)
            ->get();

        if ($members->isEmpty()) {
            $this->dispatch('notify', type: 'error', message: 'No valid members selected.');

            return;
        }

        if ($members->count() !== count($selectedIds)) {
            $this->dispatch('notify', type: 'error', message: 'One or more selected users are not valid members.');

            return;
        }

        $reason = trim($this->awardReason) !== '' ? trim($this->awardReason) : null;
        $awardedCount = 0;

        try {
            DB::transaction(function () use ($members, $admin, $reason, &$awardedCount) {
                $voucher = AdminVoucher::query()
                    ->where('voucher_code', $this->voucherCode)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $voucher->isValid()) {
                    throw new \RuntimeException('Voucher is not available for awarding.');
                }

                $alreadyAttachedIds = $voucher->users()
                    ->whereIn('users.id', $members->pluck('id'))
                    ->pluck('users.id')
                    ->all();

                $eligibleMembers = $members->reject(
                    fn (User $member) => in_array($member->id, $alreadyAttachedIds, true)
                )->values();

                if ($eligibleMembers->isEmpty()) {
                    throw new \RuntimeException('All selected members already have this voucher.');
                }

                if ($voucher->usage_limit !== null) {
                    $remaining = $voucher->usage_limit - $voucher->usage_count;
                    if ($remaining < $eligibleMembers->count()) {
                        throw new \RuntimeException(
                            'Not enough voucher slots remaining. Remaining: '.$remaining.', selected: '.$eligibleMembers->count().'.'
                        );
                    }
                }

                $pointsService = app(PointsService::class);
                $now = now();

                foreach ($eligibleMembers as $member) {
                    $member->adminVouchers()->attach($voucher->id, [
                        'status' => 'claimed',
                        'claimed_at' => $now,
                    ]);

                    $pointsService->logAdminVoucherAward(
                        $member,
                        $voucher,
                        $reason,
                        $admin,
                    );

                    $awardedCount++;
                }

                $voucher->increment('usage_count', $awardedCount);
            });

            $this->loadVoucher();
            $this->closeAwardModal();
            $this->dispatch(
                'notify',
                type: 'success',
                message: $awardedCount === 1
                    ? 'Voucher awarded successfully to 1 member.'
                    : 'Voucher awarded successfully to '.$awardedCount.' members.'
            );
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function getClaimedMembersProperty()
    {
        return $this->voucher->users()
            ->wherePivot('status', 'claimed')
            ->orderBy('user_admin_voucher.claimed_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fin' => $user->fin,
                    'claimed_at' => $user->pivot->claimed_at,
                ];
            });
    }

    public function getRedeemedMembersProperty()
    {
        return $this->voucher->users()
            ->wherePivot('status', 'redeemed')
            ->orderBy('user_admin_voucher.redeemed_at', 'desc')
            ->get()
            ->map(function ($user) {
                $merchant = null;
                if ($user->pivot->redeemed_at_merchant_id) {
                    $merchant = \App\Models\Merchant::find($user->pivot->redeemed_at_merchant_id);
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fin' => $user->fin,
                    'claimed_at' => $user->pivot->claimed_at,
                    'redeemed_at' => $user->pivot->redeemed_at,
                    'merchant' => $merchant,
                ];
            });
    }

    public function render()
    {
        $qrCodeService = app(QrCodeService::class);
        $qrCodeImage = $qrCodeService->generateQrCodeImage($this->voucher->voucher_code, 400);

        return view('livewire.admin-vouchers.profile', [
            'voucher' => $this->voucher,
            'claimedMembers' => $this->claimedMembers,
            'redeemedMembers' => $this->redeemedMembers,
            'qrCodeImage' => $qrCodeImage,
            'awardableMembers' => ($this->showAwardModal && trim($this->memberSearch) !== '') ? $this->awardableMembers : null,
            'selectedMembers' => $this->showAwardModal ? $this->selectedMembers : collect(),
            'remainingForAward' => $this->remainingForAward,
        ])->layout('layouts.app');
    }
}
