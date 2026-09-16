<?php

namespace App\Livewire\Locations;

use App\Models\Location;
use App\Services\QrCodeService;
use Livewire\Component;

class Profile extends Component
{
    public $locationCode;
    public $location;

    public function mount($location_code)
    {
        $this->locationCode = $location_code;
        $this->loadLocation();
    }

    public function loadLocation()
    {
        $this->location = Location::with(['events' => function ($query) {
            $query->forLocationProfile();
        }])->where('location_code', $this->locationCode)->firstOrFail();
    }

    public function render()
    {
        $qrCodeService = app(QrCodeService::class);
        $qrCodeImage = $qrCodeService->generateQrCodeImage($this->location->location_code, 400);

        return view('livewire.locations.profile', [
            'location' => $this->location,
            'qrCodeImage' => $qrCodeImage,
        ])->layout('layouts.app');
    }
}
