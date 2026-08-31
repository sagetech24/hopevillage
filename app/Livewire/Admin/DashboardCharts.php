<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class DashboardCharts extends Component
{
    public function placeholder()
    {
        return view('livewire.admin.partials.chart-placeholder', [
            'title' => 'Analytics',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.dashboard-charts');
    }
}
