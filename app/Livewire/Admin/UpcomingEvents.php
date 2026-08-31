<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use Livewire\Component;

class UpcomingEvents extends Component
{
    public function placeholder()
    {
        return view('livewire.admin.partials.card-placeholder');
    }

    public function getUpcomingEventsProperty()
    {
        return Event::where('status', 'published')
            ->where('start_date', '>=', now())
            ->with('location')
            ->withCount('registrations')
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.upcoming-events', [
            'upcomingEvents' => $this->upcomingEvents,
        ]);
    }
}
