<?php

namespace App\Livewire\Merchant\Redemptions;

use App\Models\Merchant;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public string $sortOption = '';

    public string $activeTab = 'merchant';

    public Collection $merchantRedemptions;

    public ?Collection $adminRedemptions = null;

    public int $merchantCount = 0;

    public int $adminCount = 0;

    public bool $adminLoaded = false;

    public bool $adminHasMore = false;

    public int $adminPage = 1;

    private const ADMIN_PER_PAGE = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortOption' => ['except' => ''],
    ];

    private const VALID_SORT_OPTIONS = [
        'member_name_asc', 'member_name_desc',
        'voucher_name_asc', 'voucher_name_desc',
        'voucher_code_asc', 'voucher_code_desc',
        'redeemed_at_asc', 'redeemed_at_desc',
    ];

    public function mount(): void
    {
        if (! in_array($this->sortOption, self::VALID_SORT_OPTIONS, true)) {
            $this->sortOption = '';
        }

        if (! auth()->user()->currentMerchant()) {
            $this->redirect(route('merchant.dashboard'));
        }

        $this->merchantRedemptions = collect();
        $this->loadMerchantRedemptions();
        $this->refreshCounts();
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'admin' && ! $this->adminLoaded) {
            $this->loadAdminRedemptions(reset: true);
        }
    }

    public function loadMoreAdminRedemptions(): void
    {
        if (! $this->adminHasMore) {
            return;
        }

        $this->adminPage++;
        $this->appendAdminRedemptions();
    }

    public function updatedSearch(): void
    {
        $this->reloadLoadedTabs();
    }

    public function updatedSortOption(): void
    {
        if (! in_array($this->sortOption, self::VALID_SORT_OPTIONS, true)) {
            $this->sortOption = 'redeemed_at_desc';
        }

        $this->reloadLoadedTabs();
    }

    private function reloadLoadedTabs(): void
    {
        $this->refreshCounts();
        $this->loadMerchantRedemptions();

        if ($this->adminLoaded) {
            $this->loadAdminRedemptions(reset: true);
        }
    }

    private function getMerchant(): ?Merchant
    {
        return auth()->user()->currentMerchant();
    }

    private function loadMerchantRedemptions(): void
    {
        $merchant = $this->getMerchant();

        if (! $merchant) {
            $this->merchantRedemptions = collect();

            return;
        }

        $this->merchantRedemptions = $this->buildMerchantQuery($merchant)->get();
    }

    private function loadAdminRedemptions(bool $reset = false): void
    {
        $merchant = $this->getMerchant();

        if (! $merchant) {
            $this->adminRedemptions = collect();
            $this->adminLoaded = true;
            $this->adminHasMore = false;
            $this->adminPage = 1;

            return;
        }

        if ($reset) {
            $this->adminPage = 1;
            $this->adminRedemptions = collect();
        }

        $this->appendAdminRedemptions();
        $this->adminLoaded = true;
    }

    private function appendAdminRedemptions(): void
    {
        $merchant = $this->getMerchant();

        if (! $merchant) {
            $this->adminHasMore = false;

            return;
        }

        $batch = $this->buildAdminQuery($merchant)
            ->offset(($this->adminPage - 1) * self::ADMIN_PER_PAGE)
            ->limit(self::ADMIN_PER_PAGE)
            ->get();

        $this->adminRedemptions = ($this->adminRedemptions ?? collect())->concat($batch);
        $this->adminHasMore = $this->adminRedemptions->count() < $this->adminCount;
    }

    private function refreshCounts(): void
    {
        $merchant = $this->getMerchant();

        if (! $merchant) {
            $this->merchantCount = 0;
            $this->adminCount = 0;

            return;
        }

        $this->merchantCount = $this->buildMerchantQuery($merchant)->count();
        $this->adminCount = $this->buildAdminQuery($merchant)->count();
    }

    private function buildMerchantQuery(Merchant $merchant): Builder
    {
        $query = DB::table('user_voucher')
            ->join('vouchers', 'vouchers.id', '=', 'user_voucher.voucher_id')
            ->join('users', 'users.id', '=', 'user_voucher.user_id')
            ->whereNull('vouchers.deleted_at')
            ->whereNull('users.deleted_at')
            ->where('vouchers.merchant_id', $merchant->id)
            ->where('user_voucher.status', 'redeemed');

        if ($this->search !== '') {
            $query->where('users.name', 'like', '%'.$this->search.'%');
        }

        return $query
            ->select(
                'users.name as member_name',
                'vouchers.name as voucher_name',
                'vouchers.voucher_code',
                'user_voucher.redeemed_at'
            )
            ->orderBy($this->getMerchantSortColumn(), $this->getSortDirection());
    }

    private function buildAdminQuery(Merchant $merchant): Builder
    {
        $query = DB::table('user_admin_voucher')
            ->join('admin_vouchers', 'admin_vouchers.id', '=', 'user_admin_voucher.admin_voucher_id')
            ->join('users', 'users.id', '=', 'user_admin_voucher.user_id')
            ->whereNull('admin_vouchers.deleted_at')
            ->whereNull('users.deleted_at')
            ->where('user_admin_voucher.redeemed_at_merchant_id', $merchant->id)
            ->where('user_admin_voucher.status', 'redeemed');

        if ($this->search !== '') {
            $query->where('users.name', 'like', '%'.$this->search.'%');
        }

        return $query
            ->select(
                'users.name as member_name',
                'admin_vouchers.name as voucher_name',
                'admin_vouchers.voucher_code',
                'user_admin_voucher.redeemed_at'
            )
            ->orderBy($this->getAdminSortColumn(), $this->getSortDirection());
    }

    private function getSortBy(): string
    {
        $parts = explode('_', $this->sortOption);
        array_pop($parts);

        return implode('_', $parts) ?: 'redeemed_at';
    }

    private function getSortDirection(): string
    {
        $parts = explode('_', $this->sortOption);
        $direction = array_pop($parts);

        return ($direction ?? 'desc') === 'asc' ? 'asc' : 'desc';
    }

    private function getMerchantSortColumn(): string
    {
        return match ($this->getSortBy()) {
            'member_name' => 'users.name',
            'voucher_name' => 'vouchers.name',
            'voucher_code' => 'vouchers.voucher_code',
            default => 'user_voucher.redeemed_at',
        };
    }

    private function getAdminSortColumn(): string
    {
        return match ($this->getSortBy()) {
            'member_name' => 'users.name',
            'voucher_name' => 'admin_vouchers.name',
            'voucher_code' => 'admin_vouchers.voucher_code',
            default => 'user_admin_voucher.redeemed_at',
        };
    }

    public function render()
    {
        return view('livewire.merchant.redemptions.index', [
            'merchant' => $this->getMerchant(),
        ])->layout('layouts.app');
    }
}
