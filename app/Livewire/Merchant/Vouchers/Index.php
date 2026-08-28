<?php

namespace App\Livewire\Merchant\Vouchers;

use App\Models\Voucher;
use App\Models\AdminVoucher;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $showMessage = false;

    protected $paginationTheme = 'tailwind';

    protected $listeners = ['voucher-deleted' => '$refresh'];

    public function mount()
    {
        $this->showMessage = session()->has('message');

        if (! auth()->user()->currentMerchant()) {
            $this->redirect(route('merchant.dashboard'));
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function edit($voucher_code)
    {
        return redirect()->route('merchant.vouchers.edit', $voucher_code);
    }

    public function delete($voucher_code)
    {
        $merchant = auth()->user()->currentMerchant();
        if (!$merchant) {
            return redirect()->route('merchant.dashboard');
        }

        if (!$merchant->is_active) {
            abort(403, 'Your merchant account is pending approval. You cannot delete vouchers until your account is approved.');
        }

        $voucher = Voucher::where('voucher_code', $voucher_code)
            ->where('merchant_id', $merchant->id)
            ->firstOrFail();
        
        $voucher->delete(); // This will perform a soft delete
        
        session()->flash('message', 'Voucher archived successfully.');
        $this->showMessage = true;
        $this->dispatch('voucher-deleted');
    }

    public function render()
    {
        $merchant = auth()->user()->currentMerchant();

        if (! $merchant) {
            return view('livewire.merchant.vouchers.index', [
                'vouchers' => collect(),
                'adminVouchers' => collect(),
                'merchant' => null,
            ])->layout('layouts.app');
        }

        // Get all merchant vouchers (not filtered by is_active), sorted by valid_until DESC
        $query = Voucher::where('merchant_id', $merchant->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('voucher_code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Remove status filter - show all vouchers
        $vouchers = $query->get()
            ->sortBy(function (Voucher $voucher) {
                return [
                    $voucher->getDisplayStatusSortOrder(),
                    $voucher->valid_until ? -$voucher->valid_until->timestamp : PHP_INT_MIN,
                    -$voucher->id,
                ];
            })
            ->values();

        // Get admin vouchers associated with this merchant, sorted by valid_until DESC
        $adminVouchersQuery = AdminVoucher::whereHas('merchants', function ($q) use ($merchant) {
            $q->where('merchants.id', $merchant->id);
        });

        if ($this->search) {
            $adminVouchersQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('voucher_code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        $adminVouchers = $adminVouchersQuery->orderByRaw('CASE WHEN valid_until IS NULL THEN 1 ELSE 0 END')
            ->orderBy('valid_until', 'desc')
            ->get();

        return view('livewire.merchant.vouchers.index', [
            'vouchers' => $vouchers,
            'adminVouchers' => $adminVouchers,
            'merchant' => $merchant,
        ])->layout('layouts.app');
    }
}
