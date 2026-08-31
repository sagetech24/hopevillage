<?php

namespace App\Livewire\Admin;

use App\Models\MemberActivity;
use Livewire\Component;

class RecentActivity extends Component
{
    public function placeholder()
    {
        return view('livewire.admin.partials.card-placeholder');
    }

    public function getRecentActivitiesProperty()
    {
        return MemberActivity::with(['user', 'activityType', 'location'])
            ->orderBy('activity_time', 'desc')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.recent-activity', [
            'recentActivities' => $this->recentActivities,
        ]);
    }
}
