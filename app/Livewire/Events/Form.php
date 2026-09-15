<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\Location;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public $locationCode;
    public $eventId;
    public $location;
    public $title = '';
    public $description = '';
    public $start_date = '';
    public $start_time = '';
    public $end_date = '';
    public $end_time = '';
    public $venue = '';
    public $max_participants = '';
    public $status = 'draft';
    public $thumbnail;
    public $existingThumbnail = null;
    public $showMessage = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'start_date' => 'required|date',
        'start_time' => 'required',
        'end_date' => 'required|date|after_or_equal:start_date',
        'end_time' => 'required',
        'venue' => 'nullable|string|max:255',
        'max_participants' => 'nullable|integer|min:1',
        'status' => 'required|in:draft,published,cancelled,completed',
        'thumbnail' => 'nullable|image|max:2048',
    ];

    public function mount($location_code, $id = null)
    {
        $this->locationCode = $location_code;
        $this->location = Location::where('location_code', $location_code)->firstOrFail();
        $this->eventId = $id;
        $this->showMessage = session()->has('message');

        if ($this->eventId) {
            $event = Event::where('location_id', $this->location->id)
                ->findOrFail($this->eventId);
            
            $this->title = $event->title;
            $this->description = $event->description ?? '';
            $this->start_date = $event->start_date->format('Y-m-d');
            $this->start_time = $event->start_date->format('H:i');
            $this->end_date = $event->end_date->format('Y-m-d');
            $this->end_time = $event->end_date->format('H:i');
            $this->venue = $event->venue;
            $this->max_participants = $event->max_participants;
            $this->status = $event->status;
            
            $media = $event->getFirstMedia('thumbnail');
            if ($media) {
                $this->existingThumbnail = $media->getUrl();
            }
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        $startDateTime = $this->start_date . ' ' . $this->start_time;
        $endDateTime = $this->end_date . ' ' . $this->end_time;

        if ($this->eventId) {
            $event = Event::where('location_id', $this->location->id)
                ->findOrFail($this->eventId);
            
            $event->update([
                'title' => $this->title,
                'description' => $this->description,
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'venue' => $this->venue,
                'max_participants' => $this->max_participants ? (int)$this->max_participants : null,
                'status' => $this->status,
            ]);
            $message = 'Event updated successfully.';
        } else {
            $event = Event::create([
                'location_id' => $this->location->id,
                'created_by' => auth()->id(),
                'title' => $this->title,
                'description' => $this->description,
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'venue' => $this->venue,
                'max_participants' => $this->max_participants ? (int)$this->max_participants : null,
                'status' => $this->status,
            ]);
            $message = 'Event created successfully.';
        }

        // Handle thumbnail upload
        if ($this->thumbnail) {
            // Clear existing thumbnail
            $event->clearMediaCollection('thumbnail');
            
            // Add new thumbnail
            $event->addMedia($this->thumbnail->getRealPath())
                ->usingName($event->title . ' - Thumbnail')
                ->toMediaCollection('thumbnail');
        }

        session()->flash('message', $message);
        $this->showMessage = true;
        return redirect()->route('admin.locations.events.index', $this->locationCode);
    }

    public function removeThumbnail()
    {
        if ($this->eventId) {
            $event = Event::where('location_id', $this->location->id)
                ->findOrFail($this->eventId);
            $event->clearMediaCollection('thumbnail');
            $this->existingThumbnail = null;
        }
    }

    public function render()
    {
        return view('livewire.events.form', [
            'location' => $this->location,
        ])->layout('layouts.app');
    }
}
