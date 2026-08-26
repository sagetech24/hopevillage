<?php

namespace App\Livewire\AdminVouchers;

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

    public function mount()
    {
        $this->showMessage = session()->has('message');
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
        return redirect()->route('admin.admin-vouchers.edit', $voucher_code);
    }

    public function delete($voucher_code)
    {
        $adminVoucher = AdminVoucher::where('voucher_code', $voucher_code)->firstOrFail();
        $adminVoucher->delete(); // This will perform a soft delete
        
        session()->flash('message', 'Admin voucher archived successfully.');
        $this->showMessage = true;
        $this->dispatch('admin-voucher-deleted');
    }

    public function render()
    {
        $query = AdminVoucher::with(['merchants', 'createdBy'])
            ->withCount([
                'users as claimed_count' => fn ($q) => $q->where('user_admin_voucher.status', 'claimed'),
                'users as redeemed_count' => fn ($q) => $q->where('user_admin_voucher.status', 'redeemed'),
            ]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('voucher_code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        $now = now();

        match ($this->statusFilter) {
            'active' => $query->valid(),
            'inactive' => $query->where('is_active', false),
            'full' => $query
                ->whereNotNull('usage_limit')
                ->whereColumn('usage_count', '>=', 'usage_limit'),
            'expired' => $query
                ->whereNotNull('valid_until')
                ->where('valid_until', '<', $now),
            default => null,
        };

        $adminVouchers = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.admin-vouchers.index', [
            'adminVouchers' => $adminVouchers,
        ])->layout('layouts.app');
    }
}

