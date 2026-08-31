<?php

namespace App\Livewire\Vouchers;

use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\Voucher;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $tab = 'merchant'; // 'merchant' or 'admin'
    public $search = '';
    public $statusFilter = 'all';
    public $merchantFilter = '';
    public $sortBy = 'start_date'; // 'start_date', 'created_at', 'name'
    public $sortDirection = 'desc'; // 'asc' or 'desc'
    public $showMessage = false;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'tab' => ['except' => 'merchant'],
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'merchantFilter' => ['except' => ''],
        'sortBy' => ['except' => 'start_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->showMessage = session()->has('message');
        
        // Get tab from URL parameter if present
        if (request()->has('tab')) {
            $tabParam = request()->query('tab', 'merchant');
            if (in_array($tabParam, ['merchant', 'admin'])) {
                $this->tab = $tabParam;
            }
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

    public function updatingMerchantFilter()
    {
        $this->resetPage();
    }

    public function updatingTab()
    {
        $this->resetPage();
        // Reset filters when switching tabs
        $this->search = '';
        $this->statusFilter = 'all';
        $this->merchantFilter = '';
        $this->sortBy = 'start_date';
        $this->sortDirection = 'desc';
    }

    public function updatingSortBy()
    {
        $this->resetPage();
    }

    public function updatingSortDirection()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function toggleSort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleApproval($voucher_code)
    {
        $voucher = Voucher::where('voucher_code', $voucher_code)->firstOrFail();
        $voucher->is_active = !$voucher->is_active;
        $voucher->save();
        
        $status = $voucher->is_active ? 'approved' : 'rejected';
        session()->flash('message', "Voucher {$status} successfully.");
        $this->showMessage = true;
        $this->dispatch('voucher-updated');
    }

    public function edit($voucher_code)
    {
        if ($this->tab === 'admin') {
            return redirect()->route('admin.admin-vouchers.edit', $voucher_code);
        }
        return redirect()->route('admin.vouchers.edit', $voucher_code);
    }

    public function delete($voucher_code)
    {
        if ($this->tab === 'admin') {
            $adminVoucher = AdminVoucher::where('voucher_code', $voucher_code)->firstOrFail();
            $adminVoucher->delete(); // This will perform a soft delete
            session()->flash('message', 'Admin voucher archived successfully.');
        } else {
            $voucher = Voucher::where('voucher_code', $voucher_code)->firstOrFail();
            $voucher->delete(); // This will perform a soft delete
            session()->flash('message', 'Voucher archived successfully.');
        }
        
        $this->showMessage = true;
        $this->dispatch('voucher-deleted');
    }

    public function render()
    {
        if ($this->tab === 'admin') {
            return $this->renderAdminVouchers();
        }
        
        return $this->renderMerchantVouchers();
    }

    protected function renderMerchantVouchers()
    {
        $query = Voucher::with('merchant');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('voucher_code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('merchant', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'pending') {
                $query->where('is_active', false);
            } elseif ($this->statusFilter === 'active') {
                $query->where('is_active', true);
            } else {
                $query->where('is_active', false);
            }
        }

        if ($this->merchantFilter) {
            $query->where('merchant_id', $this->merchantFilter);
        }

        // Apply sorting
        if ($this->sortBy === 'start_date') {
            $query->orderByRaw('CASE WHEN valid_from IS NULL THEN 1 ELSE 0 END')
                  ->orderBy('valid_from', $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $allVouchers = $query->get();
        $merchants = Merchant::orderBy('name')->get();

        // Group vouchers by status
        $groupedVouchers = [
            'pending' => collect(),
            'active' => collect(),
            'expired' => collect(),
        ];

        foreach ($allVouchers as $voucher) {
            $status = $voucher->getListStatusGroup();
            if (isset($groupedVouchers[$status])) {
                $groupedVouchers[$status]->push($voucher);
            }
        }

        return view('livewire.vouchers.index', [
            'groupedVouchers' => $groupedVouchers,
            'vouchers' => collect(),
            'adminVouchers' => collect(),
            'groupedAdminVouchers' => collect(),
            'merchants' => $merchants,
            'statusCounts' => [
                'pending' => $groupedVouchers['pending']->count(),
                'active' => $groupedVouchers['active']->count(),
                'expired' => $groupedVouchers['expired']->count(),
            ],
            'pendingCount' => $groupedVouchers['pending']->count(),
        ])->layout('layouts.app');
    }

    protected function renderAdminVouchers()
    {
        $query = AdminVoucher::with(['merchants', 'createdBy']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('voucher_code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        // Apply sorting
        if ($this->sortBy === 'start_date') {
            $query->orderByRaw('CASE WHEN valid_from IS NULL THEN 1 ELSE 0 END')
                  ->orderBy('valid_from', $this->sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $allAdminVouchers = $query->get();
        $merchants = Merchant::orderBy('name')->get();

        // Group admin vouchers by status
        $groupedAdminVouchers = [
            'pending' => collect(),
            'active' => collect(),
            'expired' => collect(),
        ];

        foreach ($allAdminVouchers as $voucher) {
            $status = $voucher->getListStatusGroup();
            if (isset($groupedAdminVouchers[$status])) {
                $groupedAdminVouchers[$status]->push($voucher);
            }
        }

        return view('livewire.vouchers.index', [
            'vouchers' => collect(),
            'adminVouchers' => collect(),
            'groupedVouchers' => collect(),
            'groupedAdminVouchers' => $groupedAdminVouchers,
            'merchants' => $merchants,
            'statusCounts' => [
                'pending' => $groupedAdminVouchers['pending']->count(),
                'active' => $groupedAdminVouchers['active']->count(),
                'expired' => $groupedAdminVouchers['expired']->count(),
            ],
            'pendingCount' => $groupedAdminVouchers['pending']->count(),
        ])->layout('layouts.app');
    }
}
