<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class AllEvents extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $locationFilter = '';

    public string $viewMode = 'list';

    public bool $showMessage = false;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        abort_unless(auth()->user()?->can('event.view'), 403);

        $this->showMessage = session()->has('message');
        $this->viewMode = session('all_events_view_mode', 'list');
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['card', 'list'], true)) {
            return;
        }

        $this->viewMode = $mode;
        session(['all_events_view_mode' => $mode]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function delete($id): void
    {
        abort_unless(auth()->user()?->can('event.delete'), 403);

        $event = $this->findEvent((int) $id);
        $event->delete();

        session()->flash('message', 'Event archived successfully.');
        $this->showMessage = true;
        $this->dispatch('event-deleted');
    }

    public function restore($id): void
    {
        abort_unless(auth()->user()?->can('event.delete'), 403);

        $event = $this->findEvent((int) $id, trashed: true);
        $event->restore();

        session()->flash('message', 'Event restored successfully.');
        $this->showMessage = true;
    }

    public function render()
    {
        $query = Event::query()
            ->with(['location', 'creator', 'media'])
            ->withCount('registrations')
            ->whereHas('location', function ($locationQuery) {
                $locationQuery->whereNull('deleted_at');
            });

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
                    ->orWhere('venue', 'like', '%'.$this->search.'%')
                    ->orWhereHas('location', function ($locationQuery) {
                        $locationQuery->whereNull('deleted_at')
                            ->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        if ($this->locationFilter !== '') {
            $query->where('location_id', $this->locationFilter);
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(10);

        $locations = Location::whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('livewire.events.all-events', [
            'events' => $events,
            'locations' => $locations,
        ])->layout('layouts.app');
    }

    protected function findEvent(int $id, bool $trashed = false): Event
    {
        $query = Event::query()->whereHas('location', function ($locationQuery) {
            $locationQuery->whereNull('deleted_at');
        });

        if ($trashed) {
            $query->onlyTrashed();
        }

        return $query->findOrFail($id);
    }
}
