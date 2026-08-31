<?php

namespace App\Livewire\Admin;

use App\Models\PointLog;
use Livewire\Component;

class PointsDistribution extends Component
{
    public function placeholder()
    {
        return view('livewire.admin.partials.chart-placeholder', [
            'title' => 'Points Distribution',
        ]);
    }

    public function getPointsDataProperty()
    {
        $pointsByActivity = PointLog::query()
            ->selectRaw('activity_types.name as activity_name, SUM(point_logs.points) as total_points')
            ->join('activity_types', 'activity_types.id', '=', 'point_logs.activity_type_id')
            ->where('point_logs.awarded_at', '>=', now()->subDays(30))
            ->groupBy('activity_types.id', 'activity_types.name')
            ->having('total_points', '>', 0)
            ->orderByDesc('total_points')
            ->limit(8)
            ->get();

        if ($pointsByActivity->isEmpty()) {
            return [
                'labels' => [],
                'data' => [],
            ];
        }

        return [
            'labels' => $pointsByActivity->pluck('activity_name')->toArray(),
            'data' => $pointsByActivity->pluck('total_points')->toArray(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.points-distribution', [
            'pointsData' => $this->pointsData,
        ]);
    }
}
