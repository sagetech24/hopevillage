<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\Location;
use Livewire\Component;

class RecentEventsParticipants extends Component
{
    public $selectedLocationId;

    public function mount()
    {
        $this->selectedLocationId = $this->activeLocations->first()?->id;
    }

    public function updatedSelectedLocationId()
    {
        // This will trigger a re-render when the location changes
    }

    public function getActiveLocationsProperty()
    {
        return Location::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getRecentEventsProperty()
    {
        if (! $this->selectedLocationId) {
            return [
                'labels' => [],
                'registered' => [],
                'attended' => [],
            ];
        }

        // Get the recent 5 finished events for the selected location
        $events = Event::where('location_id', $this->selectedLocationId)
            ->where('end_date', '<', now())
            ->where('status', 'published')
            ->with(['registrations' => function ($query) {
                $query->whereIn('status', ['registered', 'attended']);
            }])
            ->orderBy('end_date', 'desc')
            ->take(5)
            ->get();

        if ($events->isEmpty()) {
            return [
                'labels' => [],
                'registered' => [],
                'attended' => [],
            ];
        }

        $labels = [];
        $registeredData = [];
        $attendedData = [];

        foreach ($events->reverse() as $event) {
            // Truncate event title to 3 words max
            $words = explode(' ', $event->title);
            if (count($words) > 5) {
                $labels[] = implode(' ', array_slice($words, 0, 3)).'...';
            } else {
                $labels[] = $event->title;
            }

            // Count registered participants (including those who attended)
            $registeredCount = $event->registrations->whereIn('status', ['registered', 'attended'])->count();
            $registeredData[] = $registeredCount;

            // Count only attended participants
            $attendedCount = $event->registrations->where('status', 'attended')->count();
            $attendedData[] = $attendedCount;
        }

        return [
            'labels' => $labels,
            'registered' => $registeredData,
            'attended' => $attendedData,
        ];
    }

    public function render()
    {
        return view('livewire.admin.recent-events-participants', [
            'activeLocations' => $this->activeLocations,
            'recentEvents' => $this->recentEvents,
        ]);
    }
}
