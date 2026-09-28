<?php

namespace App\Livewire\Merchant\Vouchers;

use App\Models\Voucher;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public $voucherCode;

    public $voucherId;

    public $name = '';

    public $description = '';

    public $discount_type = '';

    public $discount_value = '';

    public $min_purchase = '';

    public $max_discount = '';

    public $valid_from = '';

    public $valid_until = '';

    public $usage_limit = '';

    public $is_active = false;

    public $visibilityToTypeOfWork = [];

    public $showMessage = false;

    public $voucherImage;

    public $existingVoucherImage = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'discount_type' => 'required|in:percentage,fixed,item',
        'discount_value' => 'required|numeric|min:0',
        'min_purchase' => 'nullable|numeric|min:0',
        'max_discount' => 'nullable|numeric|min:0',
        'valid_from' => 'nullable|date',
        'valid_until' => 'nullable|date|after_or_equal:valid_from',
        'usage_limit' => 'nullable|integer|min:1',
        'is_active' => 'boolean',
        'visibilityToTypeOfWork' => 'nullable|array',
        'visibilityToTypeOfWork.*' => 'in:Migrant worker,Migrant domestic worker,Others',
        'voucherImage' => 'nullable|image|max:2048',
    ];

    public function mount($voucher_code = null)
    {
        $merchant = auth()->user()->currentMerchant();
        if (! $merchant) {
            $this->redirect(route('merchant.dashboard.v2'));

            return;
        }

        if (! $merchant->is_active) {
            abort(403, 'Your merchant account is pending approval. You cannot create or edit vouchers until your account is approved.');
        }

        $this->showMessage = session()->has('message');

        if ($voucher_code) {
            $this->voucherCode = $voucher_code;
            $voucher = Voucher::where('voucher_code', $voucher_code)
                ->where('merchant_id', $merchant->id)
                ->firstOrFail();

            $this->voucherId = $voucher->id;
            $this->name = $voucher->name;
            $this->description = $voucher->description;
            $this->discount_type = $voucher->discount_type;
            $this->discount_value = $voucher->discount_value;
            $this->min_purchase = $voucher->min_purchase;
            $this->max_discount = $voucher->max_discount;
            $this->valid_from = $voucher->valid_from ? $voucher->valid_from->format('Y-m-d\TH:i') : '';
            $this->valid_until = $voucher->valid_until ? $voucher->valid_until->format('Y-m-d\TH:i') : '';
            $this->usage_limit = $voucher->usage_limit;
            $this->is_active = (bool) $voucher->is_active;
            $this->visibilityToTypeOfWork = $voucher->visibility_to_type_of_work ?? config('member.type_of_work_options', ['Migrant worker', 'Migrant domestic worker', 'Others']);

            $media = $voucher->getFirstMedia('image');
            if ($media) {
                $this->existingVoucherImage = $media->getUrl();
            }
        } else {
            $this->visibilityToTypeOfWork = config('member.type_of_work_options', ['Migrant worker', 'Migrant domestic worker', 'Others']);
        }
    }

    public function updatedDiscountType($value)
    {
        if ($value === 'item') {
            $this->discount_value = 100;
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $merchant = auth()->user()->currentMerchant();
        if (! $merchant) {
            return redirect()->route('merchant.dashboard.v2');
        }

        if (! $merchant->is_active) {
            abort(403, 'Your merchant account is pending approval. You cannot create or edit vouchers until your account is approved.');
        }

        $this->validate();

        if ($this->discount_type === 'item') {
            $this->discount_value = 100;
        }

        $data = [
            'merchant_id' => $merchant->id,
            'name' => $this->name,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'min_purchase' => $this->min_purchase ?: null,
            'max_discount' => $this->max_discount ?: null,
            'valid_from' => $this->valid_from ? date('Y-m-d H:i:s', strtotime($this->valid_from)) : null,
            'valid_until' => $this->valid_until ? date('Y-m-d H:i:s', strtotime($this->valid_until)) : null,
            'usage_limit' => $this->usage_limit ?: null,
            'visibility_to_type_of_work' => $this->visibilityToTypeOfWork ?: [],
        ];

        if ($this->voucherCode) {
            // Updating existing voucher - preserve current is_active value (merchants cannot change it)
            $voucher = Voucher::where('voucher_code', $this->voucherCode)
                ->where('merchant_id', $merchant->id)
                ->firstOrFail();
            $voucher->update($data);
            if (! $voucher->is_active) {
                $message = 'Voucher updated successfully. It is still pending administrator approval.';
            } else {
                $message = 'Voucher updated successfully.';
            }
        } else {
            // Creating new voucher - always set is_active to false (requires admin approval)
            $data['is_active'] = false;
            $voucher = Voucher::create($data);
            $message = 'Voucher created successfully and is pending administrator approval.';
        }

        // Handle voucher image upload
        if ($this->voucherImage) {
            // Clear existing image
            $voucher->clearMediaCollection('image');

            // Add new image
            $voucher->addMedia($this->voucherImage->getRealPath())
                ->usingName($voucher->name.' - Image')
                ->toMediaCollection('image');
        }

        session()->flash('message', $message);
        $this->showMessage = true;

        return redirect()->route('merchant.vouchers.index');
    }

    public function removeVoucherImage()
    {
        if ($this->voucherCode) {
            $merchant = auth()->user()->currentMerchant();
            if ($merchant) {
                $voucher = Voucher::where('voucher_code', $this->voucherCode)
                    ->where('merchant_id', $merchant->id)
                    ->first();
                if ($voucher) {
                    $voucher->clearMediaCollection('image');
                    $this->existingVoucherImage = null;
                    $this->dispatch('voucher-image-removed');
                }
            }
        }
    }

    public function render()
    {
        $discountTypes = [
            '' => '-- Select Deal Type --',
            'percentage' => 'Percentage',
            'fixed' => 'Fixed Amount',
            'item' => 'Free Item',
        ];
        $typeOfWorkOptions = config('member.type_of_work_options', ['Migrant worker', 'Migrant domestic worker']);

        $voucher = null;
        if ($this->voucherCode) {
            $merchant = auth()->user()?->currentMerchant();
            if ($merchant) {
                $voucher = Voucher::where('voucher_code', $this->voucherCode)
                    ->where('merchant_id', $merchant->id)
                    ->first();
            }
        }

        return view('livewire.merchant.vouchers.form-v2', [
            'discountTypes' => $discountTypes,
            'typeOfWorkOptions' => $typeOfWorkOptions,
            'voucher' => $voucher,
        ])->layout('layouts.app');
    }
}
