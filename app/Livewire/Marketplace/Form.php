<?php

namespace App\Livewire\Marketplace;

use App\Models\Location;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceItem;
use App\Services\MarketplaceItemAuditService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $itemId = null;

    public string $name = '';

    public string $description = '';

    public $points_cost = 0;

    public $amount_cost = 0.00;

    public ?int $marketplace_category_id = null;

    public string $new_category_name = '';

    public ?int $stock = null;

    public string $stockInput = '';

    public bool $unlimited_stock = false;

    public bool $compute_points_cost = false;

    public bool $no_daily_limit = false;

    public string $dailyLimitInput = '';

    public bool $available_in_all_locations = true;

    public array $selectedLocations = [];

    public string $valid_from = '';

    public string $valid_until = '';

    public bool $is_active = true;

    public $itemImage;

    public ?string $existingItemImage = null;

    public bool $showMessage = false;

    public bool $confirmingZeroPoints = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_cost' => 'required|integer|min:0',
            'amount_cost' => 'required|numeric|min:0',
            'marketplace_category_id' => 'nullable|exists:marketplace_categories,id',
            'new_category_name' => 'nullable|string|max:120',
            'stockInput' => 'nullable|integer|min:0',
            'compute_points_cost' => 'boolean',
            'unlimited_stock' => 'boolean',
            'dailyLimitInput' => 'nullable|integer|min:1',
            'selectedLocations' => 'nullable|array',
            'selectedLocations.*' => 'exists:locations,id',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
            'itemImage' => 'nullable|image|max:2048',
        ];
    }

    public function mount(?int $id = null): void
    {
        $this->showMessage = session()->has('message');

        if ($id) {
            abort_unless(auth()->user()?->can('marketplace.edit'), 403);

            $item = MarketplaceItem::query()->findOrFail($id);
            $this->itemId = $item->id;
            $this->name = $item->name;
            $this->description = (string) ($item->description ?? '');
            $this->points_cost = $item->points_cost;
            $this->amount_cost = $item->amount_cost;
            $this->marketplace_category_id = $item->marketplace_category_id;
            if ($item->stock === null) {
                $this->unlimited_stock = true;
                $this->stockInput = '';
            } else {
                $this->unlimited_stock = false;
                $this->stockInput = (string) $item->stock;
            }
            if ($item->daily_limit_quantity === null) {
                $this->no_daily_limit = true;
                $this->dailyLimitInput = '';
            } else {
                $this->no_daily_limit = false;
                $this->dailyLimitInput = (string) $item->daily_limit_quantity;
            }
            $this->valid_from = $item->valid_from ? $item->valid_from->format('Y-m-d\TH:i') : '';
            $this->valid_until = $item->valid_until ? $item->valid_until->format('Y-m-d\TH:i') : '';
            $this->is_active = $item->is_active;
            $this->selectedLocations = $item->locations()->pluck('locations.id')->map(fn ($id) => (int) $id)->all();
            $this->available_in_all_locations = empty($this->selectedLocations);
            $this->existingItemImage = $item->image_url;
        } else {
            abort_unless(auth()->user()?->can('marketplace.create'), 403);
        }
    }

    public function updated($propertyName): void
    {
        if ($propertyName === 'points_cost' && $this->isEmptyPointsCost()) {
            $this->resetErrorBag('points_cost');
        } elseif (! in_array($propertyName, ['confirmingZeroPoints', 'compute_points_cost', 'unlimited_stock'], true)) {
            $this->validateOnly($propertyName);
        }

        if ($propertyName === 'unlimited_stock' && $this->unlimited_stock) {
            $this->stockInput = '0';
            $this->resetErrorBag('stockInput');
            $this->compute_points_cost = false;
        }

        if (in_array($propertyName, ['amount_cost', 'stockInput', 'compute_points_cost', 'unlimited_stock'], true)) {
            $this->applyComputedPointsCost();
        }
    }

    public function normalizeEmptyPointsCost(): void
    {
        if ($this->isEmptyPointsCost()) {
            $this->points_cost = 0;
        }

        $this->validateOnly('points_cost');
    }

    protected function isEmptyPointsCost(): bool
    {
        return $this->points_cost === '' || $this->points_cost === null;
    }

    protected function applyComputedPointsCost(): void
    {
        if (! $this->compute_points_cost || $this->unlimited_stock) {
            return;
        }

        $computed = $this->computedPointsCost();
        if ($computed === null) {
            return;
        }

        $this->points_cost = $computed;
        $this->resetErrorBag('points_cost');
    }

    protected function computedPointsCost(): ?int
    {
        if ($this->stockInput === '' || $this->stockInput === null) {
            return null;
        }

        if ($this->amount_cost === '' || $this->amount_cost === null) {
            return null;
        }

        if (! is_numeric($this->stockInput) || ! is_numeric($this->amount_cost)) {
            return null;
        }

        $stock = (int) $this->stockInput;
        $amount = (float) $this->amount_cost;

        if ($stock < 0 || $amount < 0) {
            return null;
        }

        return (int) round($amount * $stock);
    }

    public function computedCostPerItem(): float
    {
        $points = is_numeric($this->points_cost) ? (float) $this->points_cost : 0;
        $amount = is_numeric($this->amount_cost) ? (float) $this->amount_cost : 0;

        return round(max(0, $points) * max(0, $amount), 2);
    }

    public function save(): mixed
    {
        return $this->saveItem(requireZeroPointsConfirmation: true);
    }

    public function confirmZeroPointsAndSave(): mixed
    {
        $this->confirmingZeroPoints = false;

        return $this->saveItem(requireZeroPointsConfirmation: false);
    }

    protected function saveItem(bool $requireZeroPointsConfirmation): mixed
    {
        abort_unless(
            $this->itemId
                ? auth()->user()?->can('marketplace.edit')
                : auth()->user()?->can('marketplace.create'),
            403
        );

        if ($this->isEmptyPointsCost()) {
            $this->points_cost = 0;
        }

        $this->validate();

        if ($this->new_category_name !== '') {
            $category = MarketplaceCategory::query()->firstOrCreate(
                ['name' => trim($this->new_category_name)],
                ['is_active' => true]
            );
            $this->marketplace_category_id = $category->id;
        }
        if (! $this->marketplace_category_id) {
            $this->addError('marketplace_category_id', __('Please select or create a category.'));

            return null;
        }

        $stock = null;
        if (! $this->unlimited_stock) {
            $this->validate(['stockInput' => 'required|integer|min:0']);
            $stock = (int) $this->stockInput;
        }
        $dailyLimit = null;
        if (! $this->no_daily_limit) {
            $this->validate(['dailyLimitInput' => 'required|integer|min:1']);
            $dailyLimit = (int) $this->dailyLimitInput;
        }
        if (! $this->available_in_all_locations) {
            $this->validate(['selectedLocations' => 'required|array|min:1']);
        }

        $this->applyComputedPointsCost();

        if ($requireZeroPointsConfirmation && (int) $this->points_cost === 0) {
            $this->confirmingZeroPoints = true;

            return null;
        }

        $data = [
            'name' => $this->name,
            'marketplace_category_id' => $this->marketplace_category_id,
            'description' => $this->description ?: null,
            'points_cost' => (int) $this->points_cost,
            'amount_cost' => $this->amount_cost === '' || $this->amount_cost === null ? 0.00 : $this->amount_cost,
            'per_item_quantity' => 1,
            'stock' => $stock,
            'daily_limit_quantity' => $dailyLimit,
            'valid_from' => $this->valid_from ? date('Y-m-d H:i:s', strtotime($this->valid_from)) : null,
            'valid_until' => $this->valid_until ? date('Y-m-d H:i:s', strtotime($this->valid_until)) : null,
            'is_active' => (bool) $this->is_active,
        ];

        if ($this->itemId) {
            $item = MarketplaceItem::query()->findOrFail($this->itemId);
            $item->update($data);
            $message = 'Marketplace item updated successfully.';
        } else {
            $data['created_by'] = auth()->id();
            $item = MarketplaceItem::query()->create($data);
            $message = 'Marketplace item created successfully.';
        }

        $previousLocationIds = $item->locations()->pluck('locations.id')->map(fn ($id) => (int) $id)->all();
        $newLocationIds = $this->available_in_all_locations ? [] : array_map('intval', $this->selectedLocations);
        $item->locations()->sync($newLocationIds);
        app(MarketplaceItemAuditService::class)->logLocationsChanged($item, $previousLocationIds, $newLocationIds);

        if ($this->itemImage) {
            $hadImage = $item->getFirstMedia('image') !== null;
            $item->clearMediaCollection('image');
            $item->addMedia($this->itemImage->getRealPath())
                ->usingName($item->name.' — Image')
                ->toMediaCollection('image');
            app(MarketplaceItemAuditService::class)->logImageChanged($item, $hadImage ? 'replaced' : 'added');
        }

        session()->flash('message', $message);
        $this->showMessage = true;

        return redirect()->route('admin.marketplace.index');
    }

    public function removeItemImage(): void
    {
        abort_unless(auth()->user()?->can('marketplace.edit'), 403);

        if ($this->itemId) {
            $item = MarketplaceItem::query()->findOrFail($this->itemId);
            $hadImage = $item->getFirstMedia('image') !== null;
            $item->clearMediaCollection('image');
            $this->existingItemImage = null;
            if ($hadImage) {
                app(MarketplaceItemAuditService::class)->logImageChanged($item, 'removed');
            }
        }
        $this->itemImage = null;
    }

    public function render()
    {
        return view('livewire.marketplace.form', [
            'categories' => MarketplaceCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
