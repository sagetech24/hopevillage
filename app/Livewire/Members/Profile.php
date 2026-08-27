<?php

namespace App\Livewire\Members;

use App\Models\MemberActivity;
use App\Models\PointLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class Profile extends Component
{
    private const RECENT_ACTIVITIES_LIMIT = 25;

    private const RECENT_POINT_LOGS_LIMIT = 25;

    public string $qr_code;

    public User $member;

    public bool $showMessage = false;

    public ?string $selectedUserType = null;

    public ?string $selectedTypeOfWork = null;

    public ?string $selectedTypeOfWorkCustom = null;

    /** @var int|null ID of member selected for "Update Mobile Number" modal. */
    public ?int $updateMobileUserId = null;

    protected $listeners = [
        'updateMobileModalClosed' => 'closeUpdateMobileModal',
    ];

    public function mount(string $qr_code): void
    {
        $this->qr_code = $qr_code;
        $this->loadMember();
        $this->showMessage = session()->has('message') || session()->has('error');
        $this->selectedUserType = $this->member->user_type;
        $this->syncTypeOfWorkFromMember();
    }

    public function openUpdateMobileModal(): void
    {
        if (! auth()->user()?->canUpdateMemberMobileNumber()) {
            session()->flash('error', 'You do not have permission to update member mobile number.');
            $this->showMessage = true;

            return;
        }

        $this->updateMobileUserId = $this->member->id;
    }

    public function closeUpdateMobileModal(): void
    {
        $this->updateMobileUserId = null;
        $this->loadMember();
        if (session()->has('message') || session()->has('error')) {
            $this->showMessage = true;
        }
    }

    #[On('activity-updated')]
    public function refreshMember(): void
    {
        $this->loadMember();
    }

    public function loadMember(): void
    {
        // Keep the Livewire-bound model lean — no heavy relation eager-loads.
        // Recent activities / point logs are loaded with real SQL LIMIT in render().
        $this->member = User::query()
            ->where('qr_code', $this->qr_code)
            ->firstOrFail();

        $this->selectedUserType = $this->member->user_type;
        $this->syncTypeOfWorkFromMember();
    }

    /**
     * @return Collection<int, MemberActivity>
     */
    protected function loadRecentActivities(): Collection
    {
        return MemberActivity::query()
            ->where('user_id', $this->member->id)
            ->with(['activityType', 'location', 'pointLog'])
            ->latest('activity_time')
            ->limit(self::RECENT_ACTIVITIES_LIMIT)
            ->get();
    }

    /**
     * @return Collection<int, PointLog>
     */
    protected function loadRecentPointLogs(): Collection
    {
        return PointLog::query()
            ->where('user_id', $this->member->id)
            ->with(['activityType', 'location'])
            ->latest('awarded_at')
            ->limit(self::RECENT_POINT_LOGS_LIMIT)
            ->get();
    }

    protected function syncTypeOfWorkFromMember(): void
    {
        $value = $this->member->type_of_work;
        if ($value === 'Migrant worker' || $value === 'Migrant domestic worker') {
            $this->selectedTypeOfWork = $value;
            $this->selectedTypeOfWorkCustom = null;
        } elseif ($value) {
            $this->selectedTypeOfWork = 'Others';
            $this->selectedTypeOfWorkCustom = $value;
        } else {
            $this->selectedTypeOfWork = '';
            $this->selectedTypeOfWorkCustom = null;
        }
    }

    public function updateTypeOfWork(): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'You do not have permission to change type of work.');
            $this->showMessage = true;

            return;
        }

        $newTypeOfWork = match ($this->selectedTypeOfWork) {
            '' => null,
            'Migrant worker', 'Migrant domestic worker' => $this->selectedTypeOfWork,
            'Others' => trim($this->selectedTypeOfWorkCustom ?? '') ?: null,
            default => $this->selectedTypeOfWork,
        };

        $current = $this->member->type_of_work ?? null;
        if (($newTypeOfWork ?? null) === $current) {
            session()->flash('error', 'No change detected. Type of work is already set to '.($this->member->type_of_work ?? 'N/A').'.');
            $this->showMessage = true;

            return;
        }

        $oldTypeOfWork = $this->member->type_of_work;

        $this->member->update(['type_of_work' => $newTypeOfWork]);

        Log::info('Type of work changed by administrator', [
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name,
            'target_user_id' => $this->member->id,
            'target_user_name' => $this->member->name,
            'old_type_of_work' => $oldTypeOfWork,
            'new_type_of_work' => $newTypeOfWork,
            'changed_at' => now()->toIso8601String(),
        ]);

        session()->flash('message', 'Type of work updated successfully.');
        $this->showMessage = true;
        $this->loadMember();
    }

    public function updateUserType(): void
    {
        if (! auth()->user()->isAdmin()) {
            session()->flash('error', 'You do not have permission to change user type.');
            $this->showMessage = true;

            return;
        }

        if (! $this->selectedUserType || $this->selectedUserType === $this->member->user_type) {
            session()->flash('error', 'No change detected. User type is already set to '.$this->member->user_type.'.');
            $this->showMessage = true;

            return;
        }

        $allowedTypes = ['admin', 'member', 'merchant_user'];
        if (! in_array($this->selectedUserType, $allowedTypes)) {
            session()->flash('error', 'Invalid user type selected.');
            $this->showMessage = true;

            return;
        }

        if ($this->member->id === auth()->id()) {
            session()->flash('error', 'You cannot change your own user type.');
            $this->showMessage = true;

            return;
        }

        $oldUserType = $this->member->user_type;
        $newUserType = $this->selectedUserType;

        $this->member->update(['user_type' => $newUserType]);

        Log::info('User type changed by administrator', [
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name,
            'admin_email' => auth()->user()->email,
            'target_user_id' => $this->member->id,
            'target_user_name' => $this->member->name,
            'target_user_email' => $this->member->email,
            'target_user_qr_code' => $this->member->qr_code,
            'old_user_type' => $oldUserType,
            'new_user_type' => $newUserType,
            'changed_at' => now()->toIso8601String(),
        ]);

        session()->flash('message', "User type changed from {$oldUserType} to {$newUserType} successfully.");
        $this->showMessage = true;
        $this->loadMember();
    }

    public function getTypeOfWorkOptions(): array
    {
        return config('member.type_of_work_options', ['Migrant worker', 'Migrant domestic worker', 'Others']);
    }

    public function render()
    {
        return view('livewire.members.profile', [
            'member' => $this->member,
            'typeOfWorkOptions' => $this->getTypeOfWorkOptions(),
            'recentActivities' => $this->loadRecentActivities(),
            'recentPointLogs' => $this->loadRecentPointLogs(),
        ])->layout('layouts.app');
    }
}
