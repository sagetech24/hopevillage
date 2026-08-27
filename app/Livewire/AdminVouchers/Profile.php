<?php

namespace App\Livewire\AdminVouchers;

use App\Models\AdminVoucher;
use App\Models\User;
use App\Services\AdminVoucherVoidService;
use App\Services\PointsService;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Profile extends Component
{
    use WithPagination;
    public $voucherCode;

    public $voucher;

    public bool $showAwardModal = false;

    public string $memberSearch = '';

    public string $claimedMemberSearch = '';

    public string $redeemedMemberSearch = '';

    public string $engagementTab = 'claimed';

    /** @var array<int, int|string> */
    public array $selectedMemberIds = [];

    public string $awardReason = '';

    public bool $showVoidModal = false;

    public ?int $voidMemberId = null;

    public string $voidMemberName = '';

    public string $voidPreviousStatus = '';

    public int $voidRefundPreview = 0;

    public string $voidReason = '';

    protected function rules(): array
    {
        return [
            'selectedMemberIds' => 'required|array|min:1',
            'selectedMemberIds.*' => 'integer|exists:users,id',
            'awardReason' => 'nullable|string|max:500',
        ];
    }

    protected function voidRules(): array
    {
        return [
            'voidMemberId' => 'required|integer|exists:users,id',
            'voidReason' => 'nullable|string|max:500',
        ];
    }

    public function mount($voucher_code)
    {
        $this->voucherCode = $voucher_code;
        $this->loadVoucher();
    }

    public function updatedClaimedMemberSearch(): void
    {
        $this->resetPage('claimedPage');
    }

    public function updatedRedeemedMemberSearch(): void
    {
        $this->resetPage('redeemedPage');
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

        $attachedUserIds = $this->voucher->users()
            ->wherePivotIn('status', ['claimed', 'redeemed'])
            ->pluck('users.id');

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
                    ->wherePivotIn('status', ['claimed', 'redeemed'])
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
                    $member->claimAdminVoucherAssignment($voucher, [
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

    public function openVoidModal(int $memberId): void
    {
        $member = $this->voucher->users()
            ->where('users.id', $memberId)
            ->wherePivotIn('status', ['claimed', 'redeemed'])
            ->first();

        if (! $member) {
            $this->dispatch('notify', type: 'error', message: 'Member voucher assignment not found.');

            return;
        }

        $this->voidMemberId = $member->id;
        $this->voidMemberName = $member->name;
        $this->voidPreviousStatus = (string) $member->pivot->status;
        $this->voidRefundPreview = app(AdminVoucherVoidService::class)
            ->previewRefundAmount($this->voucher, $member);
        $this->voidReason = '';
        $this->showVoidModal = true;
        $this->resetErrorBag();
    }

    public function closeVoidModal(): void
    {
        $this->showVoidModal = false;
        $this->voidMemberId = null;
        $this->voidMemberName = '';
        $this->voidPreviousStatus = '';
        $this->voidRefundPreview = 0;
        $this->voidReason = '';
        $this->resetErrorBag();
    }

    public function voidMemberVoucher(): void
    {
        $this->validate($this->voidRules());

        $admin = auth()->user();
        if (! $admin) {
            $this->dispatch('notify', type: 'error', message: 'You must be logged in to void vouchers.');

            return;
        }

        $member = User::query()
            ->where('user_type', 'member')
            ->whereKey($this->voidMemberId)
            ->first();

        if (! $member) {
            $this->dispatch('notify', type: 'error', message: 'Member not found.');

            return;
        }

        try {
            $result = app(AdminVoucherVoidService::class)->voidForMember(
                $this->voucher,
                $member,
                $admin,
                $this->voidReason,
            );

            $this->loadVoucher();
            $this->closeVoidModal();

            $refunded = (int) $result['points_refunded'];
            $message = $refunded > 0
                ? 'Voucher voided. '.$refunded.' point'.($refunded === 1 ? '' : 's').' credited back to '.$member->name.'.'
                : 'Voucher voided for '.$member->name.'. No points were refunded (award or already refunded).';

            $this->dispatch('notify', type: 'success', message: $message);
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function getClaimedMembersTotalProperty(): int
    {
        return $this->voucher->users()
            ->wherePivot('status', 'claimed')
            ->count();
    }

    public function getRedeemedMembersTotalProperty(): int
    {
        return $this->voucher->users()
            ->wherePivot('status', 'redeemed')
            ->count();
    }

    public function getClaimedMembersProperty()
    {
        $query = $this->voucher->users()
            ->wherePivot('status', 'claimed');

        $search = trim($this->claimedMemberSearch);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', '%'.$search.'%')
                    ->orWhere('users.email', 'like', '%'.$search.'%')
                    ->orWhere('users.fin', 'like', '%'.$search.'%')
                    ->orWhere('users.qr_code', 'like', '%'.$search.'%');
            });
        }

        return $query
            ->orderBy('user_admin_voucher.claimed_at', 'desc')
            ->paginate(10, pageName: 'claimedPage')
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fin' => $user->fin,
                    'qr_code' => $user->qr_code,
                    'claimed_at' => $user->pivot->claimed_at,
                ];
            });
    }

    public function getRedeemedMembersProperty()
    {
        $query = $this->voucher->users()
            ->wherePivot('status', 'redeemed');

        $search = trim($this->redeemedMemberSearch);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', '%'.$search.'%')
                    ->orWhere('users.email', 'like', '%'.$search.'%')
                    ->orWhere('users.fin', 'like', '%'.$search.'%')
                    ->orWhere('users.qr_code', 'like', '%'.$search.'%');
            });
        }

        return $query
            ->orderBy('user_admin_voucher.redeemed_at', 'desc')
            ->paginate(10, pageName: 'redeemedPage')
            ->through(function ($user) {
                $merchant = null;
                if ($user->pivot->redeemed_at_merchant_id) {
                    $merchant = \App\Models\Merchant::find($user->pivot->redeemed_at_merchant_id);
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fin' => $user->fin,
                    'qr_code' => $user->qr_code,
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
            'claimedMembersTotal' => $this->claimedMembersTotal,
            'redeemedMembersTotal' => $this->redeemedMembersTotal,
            'qrCodeImage' => $qrCodeImage,
            'awardableMembers' => ($this->showAwardModal && trim($this->memberSearch) !== '') ? $this->awardableMembers : null,
            'selectedMembers' => $this->showAwardModal ? $this->selectedMembers : collect(),
            'remainingForAward' => $this->remainingForAward,
        ])->layout('layouts.app');
    }
}
