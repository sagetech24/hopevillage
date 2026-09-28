<?php

namespace App\Livewire\Admin;

use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\PointLog;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DashboardStats extends Component
{
    public function render()
    {
        $activeAdminVouchers = AdminVoucher::where('is_active', true)->count();
        $activeMerchantVouchers = Voucher::where('is_active', true)->count();
        $activeVouchers = $activeAdminVouchers + $activeMerchantVouchers;
        $pendingMerchantVouchers = $this->countPendingMerchantVouchers();
        $pendingMerchantApplications = Merchant::where('is_active', false)->count();
        $totalMembers = User::where('user_type', 'member')->count();
        $totalMerchants = Merchant::count();
        $totalPoints = PointLog::sum('points');

        $activeMembers30d = $this->countActiveMembers(30);
        $activeMembers90d = $this->countActiveMembers(90);

        return view('livewire.admin.dashboard-stats', [
            'activeVouchers' => $activeVouchers,
            'activeAdminVouchers' => $activeAdminVouchers,
            'activeMerchantVouchers' => $activeMerchantVouchers,
            'pendingMerchantVouchers' => $pendingMerchantVouchers,
            'pendingMerchantApplications' => $pendingMerchantApplications,
            'totalMembers' => $totalMembers,
            'totalMerchants' => $totalMerchants,
            'totalPoints' => $totalPoints,
            'activeMembers30d' => $activeMembers30d,
            'activeMembers90d' => $activeMembers90d,
        ]);
    }

    /**
     * Merchant vouchers awaiting approval (inactive and not expired).
     */
    private function countPendingMerchantVouchers(): int
    {
        return Voucher::query()
            ->where('is_active', false)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->count();
    }

    /**
     * Distinct members with activity in the last N days (join is faster than a whereHas subquery).
     */
    private function countActiveMembers(int $days): int
    {
        return (int) DB::table('member_activities')
            ->join('users', 'users.id', '=', 'member_activities.user_id')
            ->where('users.user_type', 'member')
            ->whereNull('users.deleted_at')
            ->where('member_activities.activity_time', '>=', now()->subDays($days))
            ->distinct()
            ->count('member_activities.user_id');
    }
}
