<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $locationCode;

    public $location;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $viewMode = 'list';

    public bool $showMessage = false;

    protected $paginationTheme = 'tailwind';

    public function mount($location_code)
    {
        abort_unless(auth()->user()?->can('event.view'), 403);

        $this->locationCode = $location_code;
        $this->location = Location::where('location_code', $location_code)->firstOrFail();
        $this->showMessage = session()->has('message');
        $this->viewMode = session('location_events_view_mode', 'list');
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['card', 'list'], true)) {
            return;
        }

        $this->viewMode = $mode;
        session(['location_events_view_mode' => $mode]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete($id): void
    {
        abort_unless(auth()->user()?->can('event.delete'), 403);

        $event = $this->findEventForLocation((int) $id);
        $event->delete();

        session()->flash('message', 'Event archived successfully.');
        $this->showMessage = true;
        $this->dispatch('event-deleted');
    }

    public function restore($id): void
    {
        abort_unless(auth()->user()?->can('event.delete'), 403);

        $event = $this->findEventForLocation((int) $id, trashed: true);
        $event->restore();

        session()->flash('message', 'Event restored successfully.');
        $this->showMessage = true;
    }

    public function render()
    {
        $query = Event::query()
            ->where('location_id', $this->location->id)
            ->with(['creator', 'media'])
            ->withCount('registrations');

        if ($this->statusFilter === 'deleted') {
            $query->onlyTrashed();
        } elseif ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('event_code', 'like', '%'.$this->search.'%')
                    ->orWhere('venue', 'like', '%'.$this->search.'%');
            });
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(10);

        return view('livewire.events.index', [
            'events' => $events,
            'location' => $this->location,
        ])->layout('layouts.app');
    }

    protected function findEventForLocation(int $id, bool $trashed = false): Event
    {
        $query = Event::query()->where('location_id', $this->location->id);

        if ($trashed) {
            $query->onlyTrashed();
        }

        return $query->findOrFail($id);
    }
}
