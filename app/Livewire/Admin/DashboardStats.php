<?php

namespace App\Livewire\Admin;

use App\Models\Location;
use App\Models\Merchant;
use App\Models\PointLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DashboardStats extends Component
{
    public function render()
    {
        $totalLocations = Location::count();
        $totalMembers = User::where('user_type', 'member')->count();
        $totalMerchants = Merchant::count();
        $totalPoints = PointLog::sum('points');

        // Distinct active members in last 30 days via join (faster than whereHas subquery)
        $activeMembers = (int) DB::table('member_activities')
            ->join('users', 'users.id', '=', 'member_activities.user_id')
            ->where('users.user_type', 'member')
            ->whereNull('users.deleted_at')
            ->where('member_activities.activity_time', '>=', now()->subDays(30))
            ->distinct()
            ->count('member_activities.user_id');

        $engagementRate = $totalMembers > 0
            ? round(($activeMembers / $totalMembers) * 100, 1)
            : 0;

        return view('livewire.admin.dashboard-stats', [
            'totalLocations' => $totalLocations,
            'totalMembers' => $totalMembers,
            'totalMerchants' => $totalMerchants,
            'totalPoints' => $totalPoints,
            'activeMembers' => $activeMembers,
            'engagementRate' => $engagementRate,
        ]);
    }
}
