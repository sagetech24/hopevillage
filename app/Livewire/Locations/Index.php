<?php

namespace App\Livewire\Locations;

use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $viewMode = 'card';

    public bool $showMessage = false;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        abort_unless(auth()->user()?->can('location.view'), 403);

        $this->showMessage = session()->has('message');
        $this->viewMode = session('locations_view_mode', 'card');
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['card', 'list'], true)) {
            return;
        }

        $this->viewMode = $mode;
        session(['locations_view_mode' => $mode]);
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
        abort_unless(auth()->user()?->can('location.delete'), 403);

        $location = $this->findLocation((int) $id);
        $location->delete();

        session()->flash('message', 'Location archived successfully.');
        $this->showMessage = true;
        $this->dispatch('location-deleted');
    }

    public function restore($id): void
    {
        abort_unless(auth()->user()?->can('location.delete'), 403);

        $location = $this->findLocation((int) $id, trashed: true);
        $location->restore();

        session()->flash('message', 'Location restored successfully.');
        $this->showMessage = true;
    }

    public function render()
    {
        $query = Location::query()
            ->with('media')
            ->withCount('events');

        if ($this->statusFilter === 'archived') {
            $query->onlyTrashed();
        } elseif ($this->statusFilter !== 'all') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('address', 'like', '%'.$this->search.'%')
                    ->orWhere('city', 'like', '%'.$this->search.'%')
                    ->orWhere('province', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('location_code', 'like', '%'.$this->search.'%');
            });
        }

        $locations = $query->orderBy('name')->paginate(10);

        return view('livewire.locations.index', [
            'locations' => $locations,
        ])->layout('layouts.app');
    }

    protected function findLocation(int $id, bool $trashed = false): Location
    {
        $query = Location::query();

        if ($trashed) {
            $query->onlyTrashed();
        }

        return $query->findOrFail($id);
    }
}
