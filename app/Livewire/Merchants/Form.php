<?php

namespace App\Livewire\Merchants;

use App\Models\Merchant;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public $merchantCode;
    public $name = '';
    public $description = '';
    public $contact_name = '';
    public $phone = '';
    public $email = '';
    public $address = '';
    public $city = '';
    public $province = '';
    public $postal_code = '';
    public $website = '';
    public $is_active = true;
    public $is_gst_member = false;
    public $logo;
    public $existingLogo = null;
    public $showMessage = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'contact_name' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'province' => 'nullable|string|max:255',
        'postal_code' => 'nullable|string|max:20',
        'website' => 'nullable|url|max:255',
        'is_active' => 'boolean',
        'is_gst_member' => 'boolean',
        'logo' => 'nullable|image|max:2048',
    ];

    public function mount($merchant_code = null)
    {
        $this->showMessage = session()->has('message');
        
        if ($merchant_code) {
            $this->merchantCode = $merchant_code;
            $merchant = Merchant::where('merchant_code', $merchant_code)->firstOrFail();
            $this->name = $merchant->name;
            $this->description = $merchant->description;
            $this->contact_name = $merchant->contact_name;
            $this->phone = $merchant->phone;
            $this->email = $merchant->email;
            $this->address = $merchant->address;
            $this->city = $merchant->city;
            $this->province = $merchant->province;
            $this->postal_code = $merchant->postal_code;
            $this->website = $merchant->website;
            $this->is_active = $merchant->is_active;
            $this->is_gst_member = $merchant->is_gst_member;
            
            $media = $merchant->getFirstMedia('logo');
            if ($media) {
                $this->existingLogo = $media->getUrl();
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

        if ($this->merchantCode) {
            $merchant = Merchant::where('merchant_code', $this->merchantCode)->firstOrFail();
            $merchant->update([
                'name' => $this->name,
                'description' => $this->description,
                'contact_name' => $this->contact_name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
                'website' => $this->website,
                'is_active' => $this->is_active,
                'is_gst_member' => $this->is_gst_member,
            ]);
            $message = 'Merchant updated successfully.';
        } else {
            $merchant = Merchant::create([
                'name' => $this->name,
                'description' => $this->description,
                'contact_name' => $this->contact_name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
                'website' => $this->website,
                'is_active' => $this->is_active,
                'is_gst_member' => $this->is_gst_member,
            ]);
            $message = 'Merchant created successfully.';
        }

        // Handle logo upload
        if ($this->logo) {
            // Clear existing logo
            $merchant->clearMediaCollection('logo');
            
            // Add new logo - for Livewire temporary files, use getPathname() or getRealPath()
            // Both should work, but getPathname() is more reliable for Livewire temp files
            $merchant->addMedia($this->logo->getPathname())
                ->usingName($merchant->name . ' - Logo')
                ->usingFileName($this->logo->getClientOriginalName())
                ->toMediaCollection('logo');
        }

        session()->flash('message', $message);
        $this->showMessage = true;
        return redirect()->route('admin.merchants.index');
    }

    public function removeLogo()
    {
        if ($this->merchantCode) {
            $merchant = Merchant::where('merchant_code', $this->merchantCode)->firstOrFail();
            $merchant->clearMediaCollection('logo');
            $this->existingLogo = null;
            $this->dispatch('logo-removed');
        }
    }

    public function render()
    {
        return view('livewire.merchants.form')->layout('layouts.app');
    }
}
