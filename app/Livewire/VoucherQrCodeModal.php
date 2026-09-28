<?php

namespace App\Livewire;

use App\Models\AdminVoucher;
use App\Models\User;
use App\Models\Voucher;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class VoucherQrCodeModal extends Component
{
    public $voucherCode;

    public $open = false;

    public $voucher = null;

    public $adminVoucher = null;

    public $isAdminVoucher = false;

    public $memberFin = null;

    public $redeemerQrCode = null;

    public $redeemer = null;

    public $redeemerVoucherStatus = null;

    public $processing = false;

    public $error = null;

    public $success = false;

    public $successMessage = null;

    protected $listeners = [
        'openVoucherQrModal' => 'open',
        'closeVoucherQrModal' => 'close',
    ];

    public function mount($voucherCode = null)
    {
        $this->voucherCode = $voucherCode;
        if ($this->voucherCode) {
            $this->loadVoucher();
        }
    }

    public function open($voucherCode = null, $redeemerQrCode = null)
    {
        try {
            // Reset all properties first to clear any previous state
            $this->voucher = null;
            $this->adminVoucher = null;
            $this->isAdminVoucher = false;
            $this->redeemer = null;
            $this->redeemerVoucherStatus = null;
            $this->error = null;
            $this->success = false;
            $this->successMessage = null;
            $this->processing = false;

            if ($voucherCode) {
                $this->voucherCode = $voucherCode;
                Log::info('VoucherQrCodeModal opened', [
                    'voucher_code' => $voucherCode,
                    'user_id' => auth()->id(),
                    'user_type' => auth()->user()?->user_type,
                ]);
            } else {
                $this->voucherCode = null;
            }

            if ($redeemerQrCode) {
                $this->redeemerQrCode = $redeemerQrCode;
            } else {
                $this->redeemerQrCode = null;
            }

            $this->memberFin = auth()->user()?->fin;
            $this->open = true;

            // Load redeemer first if QR code provided
            if ($this->redeemerQrCode) {
                $this->loadRedeemer();
            }

            // Load voucher if code provided
            if ($this->voucherCode) {
                $this->loadVoucher();
            }
        } catch (\Exception $e) {
            Log::error('Failed to open VoucherQrCodeModal', [
                'voucher_code' => $voucherCode,
                'redeemer_qr_code' => $redeemerQrCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error = 'Failed to open voucher modal: '.$e->getMessage();
            $this->processing = false;
        }
    }

    public function close()
    {
        $this->open = false;
        $this->voucherCode = null;
        $this->voucher = null;
        $this->adminVoucher = null;
        $this->isAdminVoucher = false;
        $this->memberFin = null;
        $this->redeemerQrCode = null;
        $this->redeemer = null;
        $this->redeemerVoucherStatus = null;
        $this->processing = false;
        $this->error = null;
        $this->success = false;
        $this->successMessage = null;
    }

    public function loadRedeemer()
    {
        try {
            if ($this->redeemerQrCode) {
                // Normalize the QR code (trim and uppercase)
                $normalizedQrCode = strtoupper(trim($this->redeemerQrCode));

                Log::info('Loading redeemer for voucher redemption', [
                    'redeemer_qr_code' => $normalizedQrCode,
                    'voucher_code' => $this->voucherCode,
                ]);

                // Try to find user by qr_code or fin
                $this->redeemer = User::where('qr_code', $normalizedQrCode)
                    ->orWhere('fin', $normalizedQrCode)
                    ->first();

                if (! $this->redeemer) {
                    Log::warning('Redeemer not found', [
                        'redeemer_qr_code' => $normalizedQrCode,
                    ]);

                    return;
                }

                Log::info('Redeemer loaded', [
                    'redeemer_id' => $this->redeemer->id,
                    'redeemer_fin' => $this->redeemer->fin,
                ]);

                // Check voucher status for redeemer if voucher is loaded
                if ($this->redeemer && ($this->voucher || $this->adminVoucher)) {
                    if ($this->isAdminVoucher && $this->adminVoucher) {
                        $pivot = $this->redeemer->adminVouchers()
                            ->where('admin_vouchers.id', $this->adminVoucher->id)
                            ->first();
                        $this->redeemerVoucherStatus = $pivot?->pivot->status ?? null;

                        Log::info('Admin voucher status checked for redeemer', [
                            'redeemer_fin' => $this->redeemer->fin,
                            'voucher_code' => $this->adminVoucher->voucher_code,
                            'status' => $this->redeemerVoucherStatus,
                        ]);
                    } elseif ($this->voucher) {
                        $pivot = $this->redeemer->vouchers()
                            ->where('vouchers.id', $this->voucher->id)
                            ->first();
                        $this->redeemerVoucherStatus = $pivot?->pivot->status ?? null;

                        Log::info('Voucher status checked for redeemer', [
                            'redeemer_fin' => $this->redeemer->fin,
                            'voucher_code' => $this->voucher->voucher_code,
                            'status' => $this->redeemerVoucherStatus,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load redeemer', [
                'redeemer_qr_code' => $this->redeemerQrCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error = 'Failed to load redeemer information: '.$e->getMessage();
        }
    }

    public function loadVoucher()
    {
        try {
            if ($this->voucherCode) {
                // Normalize the voucher code (trim and uppercase)
                $normalizedCode = strtoupper(trim($this->voucherCode));

                Log::info('Loading voucher', [
                    'voucher_code' => $normalizedCode,
                    'user_id' => auth()->id(),
                    'user_type' => auth()->user()?->user_type,
                ]);

                // Check if it's an admin voucher (AVOU- prefix) or regular voucher (VOU- prefix)
                if (str_starts_with($normalizedCode, 'AVOU-')) {
                    $this->isAdminVoucher = true;
                    Log::info('Detected admin voucher', ['voucher_code' => $normalizedCode]);

                    $this->adminVoucher = AdminVoucher::with('merchants')
                        ->where('voucher_code', $normalizedCode)
                        ->first();

                    if (! $this->adminVoucher) {
                        $errorMsg = 'Admin voucher not found.';
                        Log::error('Admin voucher not found', ['voucher_code' => $normalizedCode]);
                        $this->error = $errorMsg;

                        return;
                    }

                    // Block inactive / out-of-date vouchers early (usage limit is claim-only)
                    if (! $this->adminVoucher->isRedeemable()) {
                        $reason = $this->adminVoucher->getRedeemStatusReason();
                        $this->error = $reason ? "Voucher cannot be redeemed: {$reason}." : 'Admin voucher is not valid.';

                        return;
                    }

                    Log::info('Admin voucher loaded', [
                        'voucher_id' => $this->adminVoucher->id,
                        'voucher_name' => $this->adminVoucher->name,
                        'allowed_merchants_count' => $this->adminVoucher->merchants->count(),
                    ]);

                    // Check merchant authorization if merchant user is viewing
                    $user = auth()->user();
                    if ($user && $user->isMerchantUser() && $this->redeemer) {
                        $currentMerchant = $user->currentMerchant();
                        if ($currentMerchant && ! $this->adminVoucher->merchants->contains('id', $currentMerchant->id)) {
                            $errorMsg = 'This merchant is not allowed to redeem this admin voucher. Only the following merchants can redeem this voucher: '.$this->adminVoucher->merchants->pluck('name')->join(', ');
                            Log::warning('Merchant not authorized to view/redeem admin voucher', [
                                'merchant_id' => $currentMerchant->id,
                                'merchant_name' => $currentMerchant->name,
                                'voucher_code' => $this->adminVoucher->voucher_code,
                                'allowed_merchants' => $this->adminVoucher->merchants->pluck('name')->toArray(),
                            ]);
                            $this->error = $errorMsg;
                            $this->processing = false;

                            return;
                        }
                    }

                    // Only automatically process voucher redemption for members
                    if ($user && $user->isMember()) {
                        $this->processVoucherRedemption();
                    }

                    // If redeemer is loaded, check their voucher status
                    if ($this->redeemer) {
                        $pivot = $this->redeemer->adminVouchers()
                            ->where('admin_vouchers.id', $this->adminVoucher->id)
                            ->first();
                        $this->redeemerVoucherStatus = $pivot?->pivot->status ?? null;
                    }
                } else {
                    // Regular voucher
                    $this->isAdminVoucher = false;
                    Log::info('Detected regular voucher', ['voucher_code' => $normalizedCode]);

                    $this->voucher = Voucher::with('merchant')
                        ->where('voucher_code', $normalizedCode)
                        ->first();

                    if (! $this->voucher) {
                        $errorMsg = 'Voucher not found.';
                        Log::error('Voucher not found', ['voucher_code' => $normalizedCode]);
                        $this->error = $errorMsg;

                        return;
                    }

                    // Block inactive / out-of-date vouchers early (usage limit is claim-only)
                    if (! $this->voucher->isRedeemable()) {
                        $reason = $this->voucher->getRedeemStatusReason();
                        $this->error = $reason ? "Voucher cannot be redeemed: {$reason}." : 'Voucher is not valid.';

                        return;
                    }

                    Log::info('Regular voucher loaded', [
                        'voucher_id' => $this->voucher->id,
                        'voucher_name' => $this->voucher->name,
                        'merchant_id' => $this->voucher->merchant_id,
                        'merchant_name' => $this->voucher->merchant?->name,
                    ]);

                    // Check merchant ownership if merchant user is viewing
                    $user = auth()->user();
                    if ($user && $user->isMerchantUser()) {
                        $currentMerchant = $user->currentMerchant();
                        if ($currentMerchant && $this->voucher->merchant_id !== $currentMerchant->id) {
                            $errorMsg = 'This voucher does not belong to your merchant. Only the voucher owner can redeem it.';
                            Log::warning('Merchant attempted to redeem voucher not owned by them', [
                                'merchant_id' => $currentMerchant->id,
                                'merchant_name' => $currentMerchant->name,
                                'voucher_code' => $this->voucher->voucher_code,
                                'voucher_merchant_id' => $this->voucher->merchant_id,
                                'voucher_merchant_name' => $this->voucher->merchant?->name,
                            ]);
                            $this->error = $errorMsg;
                            $this->processing = false;

                            return;
                        }
                    }

                    // Only automatically process voucher redemption for members
                    if ($user && $user->isMember()) {
                        $this->processVoucherRedemption();
                    }

                    // If redeemer is loaded, check their voucher status
                    if ($this->redeemer) {
                        $pivot = $this->redeemer->vouchers()
                            ->where('vouchers.id', $this->voucher->id)
                            ->first();
                        $this->redeemerVoucherStatus = $pivot?->pivot->status ?? null;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load voucher', [
                'voucher_code' => $this->voucherCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error = 'Failed to load voucher: '.$e->getMessage();
        }
    }

    public function processVoucherRedemption()
    {
        $user = auth()->user();

        if (! $user || ! $user->fin) {
            $this->error = 'Please login to scan QR codes.';

            return;
        }

        // Check if user is a member
        if ($user->user_type !== 'member') {
            $this->error = 'User is not a member.';

            return;
        }

        if ($this->isAdminVoucher) {
            if (! $this->adminVoucher) {
                $this->error = 'Admin voucher not found.';

                return;
            }

            // Block inactive / out-of-date vouchers - no redemption or points (usage limit is claim-only)
            if (! $this->adminVoucher->isRedeemable()) {
                $reason = $this->adminVoucher->getRedeemStatusReason();
                $this->error = $reason ? "Voucher cannot be redeemed: {$reason}." : 'Admin voucher is not valid.';

                return;
            }

            $this->processing = true;
            $this->error = null;
            $this->success = false;
            $this->successMessage = null;

            try {
                DB::transaction(function () use ($user) {
                    // Check if admin voucher is claimed by this member
                    $pivot = $user->adminVouchers()
                        ->where('admin_vouchers.id', $this->adminVoucher->id)
                        ->first();

                    if (! $pivot) {
                        $this->error = 'Admin voucher not claimed by this member. Please claim the voucher first.';
                        $this->processing = false;

                        return;
                    }

                    // Check if admin voucher is already redeemed
                    if ($pivot->pivot->status === 'redeemed') {
                        $this->error = 'Admin voucher has already been redeemed.';
                        $this->processing = false;

                        return;
                    }

                    // Check if admin voucher status is 'claimed' (required for redemption)
                    if ($pivot->pivot->status !== 'claimed') {
                        $this->error = 'Admin voucher must be claimed before redemption.';
                        $this->processing = false;

                        return;
                    }

                    // Mark admin voucher as redeemed (no points awarded for admin voucher redemption)
                    $user->adminVouchers()->updateExistingPivot($this->adminVoucher->id, [
                        'status' => 'redeemed',
                        'redeemed_at' => now(),
                    ]);

                    app(PointsService::class)->recordAdminVoucherRedeem($user, $this->adminVoucher);

                    // Note: usage_count is only incremented on claim, not redemption

                    Log::info('Admin voucher redeemed', [
                        'member_fin' => $user->fin,
                        'voucher_code' => $this->adminVoucher->voucher_code,
                        'voucher_id' => $this->adminVoucher->id,
                    ]);

                    // Set success message
                    $this->success = true;
                    $this->successMessage = 'SUCCESS';

                    session()->flash('voucher-redeem-success', 'SUCCESS');
                    $this->processing = false;
                });
            } catch (\Exception $e) {
                Log::error('Failed to redeem admin voucher', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->error = 'Failed to redeem admin voucher: '.($e->getMessage() ?? 'Unknown error');
                $this->processing = false;
            }
        } else {
            if (! $this->voucher) {
                $this->error = 'Voucher not found.';

                return;
            }

            // Block inactive / out-of-date vouchers - no redemption or points (usage limit is claim-only)
            if (! $this->voucher->isRedeemable()) {
                $reason = $this->voucher->getRedeemStatusReason();
                $this->error = $reason ? "Voucher cannot be redeemed: {$reason}." : 'Voucher is not valid.';

                return;
            }

            $this->processing = true;
            $this->error = null;
            $this->success = false;
            $this->successMessage = null;

            try {
                DB::transaction(function () use ($user) {
                    // Check if voucher is claimed by this member
                    $pivot = $user->vouchers()
                        ->where('vouchers.id', $this->voucher->id)
                        ->first();

                    if (! $pivot) {
                        $this->error = 'Voucher not claimed by this member. Please claim the voucher first.';
                        $this->processing = false;

                        return;
                    }

                    // Check if voucher is already redeemed
                    if ($pivot->pivot->status === 'redeemed') {
                        $this->error = 'Voucher has already been redeemed.';
                        $this->processing = false;

                        return;
                    }

                    // Check if voucher status is 'claimed' (required for redemption)
                    if ($pivot->pivot->status !== 'claimed') {
                        $this->error = 'Voucher must be claimed before redemption.';
                        $this->processing = false;

                        return;
                    }

                    // Mark voucher as redeemed
                    $user->vouchers()->updateExistingPivot($this->voucher->id, [
                        'status' => 'redeemed',
                        'redeemed_at' => now(),
                    ]);

                    // Note: usage_count is only incremented on claim, not redemption

                    // Award points for voucher redemption
                    $pointsBefore = $user->total_points;
                    $pointsAwarded = 0;

                    app(PointsService::class)->awardVoucherRedeem($user, $this->voucher);

                    $user->refresh();
                    $pointsAwarded = $user->total_points - $pointsBefore;

                    Log::info('Voucher redeemed', [
                        'member_fin' => $user->fin,
                        'voucher_code' => $this->voucher->voucher_code,
                        'voucher_id' => $this->voucher->id,
                        'points_awarded' => $pointsAwarded,
                    ]);

                    // Set success message
                    $this->success = true;
                    $this->successMessage = 'SUCCESS';

                    session()->flash('voucher-redeem-success', 'SUCCESS');
                    $this->processing = false;
                });
            } catch (\Exception $e) {
                Log::error('Failed to redeem voucher', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->error = 'Failed to redeem voucher: '.($e->getMessage() ?? 'Unknown error');
                $this->processing = false;
            }
        }
    }

    public function processScan()
    {
        // This method is kept for backward compatibility but now calls processVoucherRedemption
        $this->processVoucherRedemption();
    }

    public function processRedeemerVoucherRedemption()
    {
        $currentUser = auth()->user();

        try {
            Log::info('Processing redeemer voucher redemption', [
                'user_id' => $currentUser?->id,
                'user_type' => $currentUser?->user_type,
                'voucher_code' => $this->voucherCode,
                'is_admin_voucher' => $this->isAdminVoucher,
            ]);

            // Only merchants can process redemption for redeemers
            if (! $currentUser || ! $currentUser->isMerchantUser()) {
                $errorMsg = 'Only merchants can process voucher redemption.';
                Log::warning('Unauthorized user attempted voucher redemption', [
                    'user_id' => $currentUser?->id,
                    'user_type' => $currentUser?->user_type,
                ]);
                $this->error = $errorMsg;
                $this->processing = false;

                return;
            }

            if (! $this->redeemer) {
                $errorMsg = 'Redeemer information not found.';
                Log::error('Redeemer not found for voucher redemption', [
                    'voucher_code' => $this->voucherCode,
                    'redeemer_qr_code' => $this->redeemerQrCode,
                ]);
                $this->error = $errorMsg;
                $this->processing = false;

                return;
            }

            if ($this->isAdminVoucher) {
                if (! $this->adminVoucher) {
                    $errorMsg = 'Admin voucher not found.';
                    Log::error('Admin voucher not found during redemption', [
                        'voucher_code' => $this->voucherCode,
                    ]);
                    $this->error = $errorMsg;
                    $this->processing = false;

                    return;
                }

                // Block inactive / out-of-date admin vouchers - no redemption (usage limit is claim-only)
                if (! $this->adminVoucher->isRedeemable()) {
                    $reason = $this->adminVoucher->getRedeemStatusReason();
                    $errorMsg = $reason ? "Voucher cannot be redeemed: {$reason}." : 'Admin voucher is not valid.';
                    Log::warning('Attempted to redeem invalid admin voucher', [
                        'voucher_code' => $this->adminVoucher->voucher_code,
                        'voucher_id' => $this->adminVoucher->id,
                        'reason' => $reason,
                    ]);
                    $this->error = $errorMsg;
                    $this->processing = false;

                    return;
                }

                // Check if current merchant is allowed to redeem this admin voucher
                $currentMerchant = $currentUser->currentMerchant();
                if (! $currentMerchant) {
                    $errorMsg = 'Merchant information not found.';
                    Log::error('Merchant not found for admin voucher redemption', [
                        'user_id' => $currentUser->id,
                        'voucher_code' => $this->adminVoucher->voucher_code,
                    ]);
                    $this->error = $errorMsg;
                    $this->processing = false;

                    return;
                }

                $isMerchantAllowed = $this->adminVoucher->merchants->contains('id', $currentMerchant->id);
                if (! $isMerchantAllowed) {
                    $errorMsg = 'This merchant is not allowed to redeem this admin voucher. Only the following merchants can redeem this voucher: '.$this->adminVoucher->merchants->pluck('name')->join(', ');
                    Log::warning('Merchant not allowed to redeem admin voucher', [
                        'merchant_id' => $currentMerchant->id,
                        'merchant_name' => $currentMerchant->name,
                        'voucher_code' => $this->adminVoucher->voucher_code,
                        'voucher_id' => $this->adminVoucher->id,
                        'allowed_merchants' => $this->adminVoucher->merchants->pluck('id')->toArray(),
                        'allowed_merchant_names' => $this->adminVoucher->merchants->pluck('name')->toArray(),
                    ]);
                    $this->error = $errorMsg;
                    $this->processing = false;

                    return;
                }

                Log::info('Merchant authorized for admin voucher redemption', [
                    'merchant_id' => $currentMerchant->id,
                    'merchant_name' => $currentMerchant->name,
                    'voucher_code' => $this->adminVoucher->voucher_code,
                ]);

                $this->processing = true;
                $this->error = null;
                $this->success = false;
                $this->successMessage = null;

                try {
                    DB::transaction(function () use ($currentUser, $currentMerchant) {
                        // Check if admin voucher is claimed by the redeemer
                        $pivot = $this->redeemer->adminVouchers()
                            ->where('admin_vouchers.id', $this->adminVoucher->id)
                            ->first();

                        if (! $pivot) {
                            $errorMsg = 'Admin voucher not claimed by this member. Please claim the voucher first.';
                            Log::warning('Admin voucher not claimed by member', [
                                'redeemer_fin' => $this->redeemer->fin,
                                'redeemer_id' => $this->redeemer->id,
                                'voucher_code' => $this->adminVoucher->voucher_code,
                                'voucher_id' => $this->adminVoucher->id,
                            ]);
                            $this->error = $errorMsg;

                            // Dispatch error event to notify member
                            $this->dispatch('voucher-redeemed', [
                                'type' => 'error',
                                'message' => "Voucher '{$this->adminVoucher->name}' is not claimed. Please claim it first.",
                                'voucher_code' => $this->adminVoucher->voucher_code,
                                'member_id' => $this->redeemer->id,
                            ])->to('member.vouchers-v2.my-vouchers');

                            $this->processing = false;

                            return;
                        }

                        Log::info('Admin voucher claim found for redeemer', [
                            'redeemer_fin' => $this->redeemer->fin,
                            'voucher_code' => $this->adminVoucher->voucher_code,
                            'current_status' => $pivot->pivot->status,
                            'claimed_at' => $pivot->pivot->claimed_at,
                        ]);

                        // Check if admin voucher is already redeemed
                        if ($pivot->pivot->status === 'redeemed') {
                            $errorMsg = 'Admin voucher has already been redeemed.';
                            Log::warning('Attempted to redeem already redeemed admin voucher', [
                                'redeemer_fin' => $this->redeemer->fin,
                                'voucher_code' => $this->adminVoucher->voucher_code,
                                'redeemed_at' => $pivot->pivot->redeemed_at,
                                'redeemed_at_merchant_id' => $pivot->pivot->redeemed_at_merchant_id,
                            ]);
                            $this->error = $errorMsg;

                            // Dispatch error event to notify member
                            $this->dispatch('voucher-redeemed', [
                                'type' => 'error',
                                'message' => "Voucher '{$this->adminVoucher->name}' has already been redeemed.",
                                'voucher_code' => $this->adminVoucher->voucher_code,
                                'member_id' => $this->redeemer->id,
                            ])->to('member.vouchers-v2.my-vouchers');

                            $this->processing = false;

                            return;
                        }

                        // Check if admin voucher status is 'claimed' (required for redemption)
                        if ($pivot->pivot->status !== 'claimed') {
                            $errorMsg = 'Admin voucher must be claimed before redemption.';
                            Log::warning('Admin voucher not in claimed status', [
                                'redeemer_fin' => $this->redeemer->fin,
                                'voucher_code' => $this->adminVoucher->voucher_code,
                                'current_status' => $pivot->pivot->status,
                            ]);
                            $this->error = $errorMsg;

                            // Dispatch error event to notify member
                            $this->dispatch('voucher-redeemed', [
                                'type' => 'error',
                                'message' => "Voucher '{$this->adminVoucher->name}' must be claimed before redemption.",
                                'voucher_code' => $this->adminVoucher->voucher_code,
                                'member_id' => $this->redeemer->id,
                            ])->to('member.vouchers-v2.my-vouchers');

                            $this->processing = false;

                            return;
                        }

                        // Mark admin voucher as redeemed with merchant tracking
                        $this->redeemer->adminVouchers()->updateExistingPivot($this->adminVoucher->id, [
                            'status' => 'redeemed',
                            'redeemed_at' => now(),
                            'redeemed_at_merchant_id' => $currentMerchant->id,
                        ]);

                        app(PointsService::class)->recordAdminVoucherRedeem(
                            $this->redeemer,
                            $this->adminVoucher,
                            $currentMerchant->id,
                        );

                        // Note: usage_count is only incremented on claim, not redemption

                        Log::info('Admin voucher redeemed by merchant - SUCCESS', [
                            'merchant_id' => $currentUser->id,
                            'merchant_name' => $currentMerchant->name,
                            'member_fin' => $this->redeemer->fin,
                            'member_id' => $this->redeemer->id,
                            'voucher_code' => $this->adminVoucher->voucher_code,
                            'voucher_id' => $this->adminVoucher->id,
                            'voucher_name' => $this->adminVoucher->name,
                            'points_cost' => $this->adminVoucher->points_cost,
                            'redeemed_at' => now()->toDateTimeString(),
                        ]);

                        // Set success message
                        $this->success = true;
                        $this->successMessage = 'Admin voucher redeemed successfully!';

                        // Dispatch event to notify member in real-time
                        $this->dispatch('voucher-redeemed', [
                            'type' => 'success',
                            'message' => "Your voucher '{$this->adminVoucher->name}' has been successfully redeemed at {$currentMerchant->name}!",
                            'voucher_code' => $this->adminVoucher->voucher_code,
                            'member_id' => $this->redeemer->id,
                        ])->to('member.vouchers-v2.my-vouchers');

                        // Update redeemer voucher status
                        $this->redeemerVoucherStatus = 'redeemed';

                        $this->processing = false;
                    });
                } catch (\Exception $e) {
                    Log::error('Failed to redeem admin voucher by merchant - EXCEPTION', [
                        'merchant_id' => $currentUser?->id,
                        'merchant_name' => $currentMerchant?->name ?? 'unknown',
                        'member_fin' => $this->redeemer?->fin,
                        'voucher_code' => $this->adminVoucher?->voucher_code,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $errorMessage = 'Failed to redeem admin voucher: '.($e->getMessage() ?? 'Unknown error');
                    $this->error = $errorMessage;

                    // Dispatch error event to notify member
                    if ($this->redeemer) {
                        $this->dispatch('voucher-redeemed', [
                            'type' => 'error',
                            'message' => "Failed to redeem voucher '{$this->adminVoucher->name}': ".($e->getMessage() ?? 'Unknown error'),
                            'voucher_code' => $this->adminVoucher->voucher_code,
                            'member_id' => $this->redeemer->id,
                        ])->to('member.vouchers-v2.my-vouchers');
                    }

                    $this->processing = false;
                }
            } else {
                // Regular voucher redemption
                Log::info('Processing regular voucher redemption', [
                    'voucher_code' => $this->voucherCode,
                ]);

                if (! $this->voucher) {
                    $errorMsg = 'Voucher not found.';
                    Log::error('Regular voucher not found during redemption', [
                        'voucher_code' => $this->voucherCode,
                    ]);
                    $this->error = $errorMsg;
                    $this->processing = false;

                    return;
                }

                // Check if merchant owns this voucher
                $currentMerchant = $currentUser->currentMerchant();
                if (! $currentMerchant) {
                    $errorMsg = 'Merchant information not found.';
                    Log::error('Merchant not found for voucher redemption', [
                        'user_id' => $currentUser->id,
                        'voucher_code' => $this->voucherCode,
                    ]);
                    $this->error = $errorMsg;
                    $this->processing = false;

                    return;
                }

                if ($this->voucher->merchant_id !== $currentMerchant->id) {
                    $errorMsg = 'This voucher does not belong to your merchant. Only the voucher owner can redeem it.';
                    Log::warning('Merchant attempted to redeem voucher not owned by them', [
                        'merchant_id' => $currentMerchant->id,
                        'merchant_name' => $currentMerchant->name,
                        'voucher_code' => $this->voucher->voucher_code,
                        'voucher_merchant_id' => $this->voucher->merchant_id,
                        'voucher_merchant_name' => $this->voucher->merchant?->name,
                    ]);
                    $this->error = $errorMsg;

                    // Dispatch error event to notify member
                    if ($this->redeemer) {
                        $this->dispatch('voucher-redeemed', [
                            'type' => 'error',
                            'message' => "Failed to redeem voucher '{$this->voucher->name}': This voucher does not belong to the scanning merchant.",
                            'voucher_code' => $this->voucher->voucher_code,
                            'member_id' => $this->redeemer->id,
                        ])->to('member.vouchers-v2.my-vouchers');
                    }

                    $this->processing = false;

                    return;
                }

                // Block inactive / out-of-date vouchers - no redemption or points (usage limit is claim-only)
                if (! $this->voucher->isRedeemable()) {
                    $reason = $this->voucher->getRedeemStatusReason();
                    $errorMsg = $reason ? "Voucher cannot be redeemed: {$reason}." : 'Voucher is not valid.';
                    Log::warning('Attempted to redeem invalid voucher', [
                        'voucher_code' => $this->voucher->voucher_code,
                        'voucher_id' => $this->voucher->id,
                        'reason' => $reason,
                    ]);
                    $this->error = $errorMsg;
                    $this->processing = false;

                    return;
                }

                $this->processing = true;
                $this->error = null;
                $this->success = false;
                $this->successMessage = null;

                try {
                    DB::transaction(function () use ($currentUser) {
                        // Check if voucher is claimed by the redeemer
                        $pivot = $this->redeemer->vouchers()
                            ->where('vouchers.id', $this->voucher->id)
                            ->first();

                        if (! $pivot) {
                            $errorMsg = 'Voucher not claimed by this member. Please claim the voucher first.';
                            Log::warning('Voucher not claimed by member', [
                                'redeemer_fin' => $this->redeemer->fin,
                                'redeemer_id' => $this->redeemer->id,
                                'voucher_code' => $this->voucher->voucher_code,
                                'voucher_id' => $this->voucher->id,
                            ]);
                            $this->error = $errorMsg;

                            // Dispatch error event to notify member
                            $this->dispatch('voucher-redeemed', [
                                'type' => 'error',
                                'message' => "Voucher '{$this->voucher->name}' is not claimed. Please claim it first.",
                                'voucher_code' => $this->voucher->voucher_code,
                                'member_id' => $this->redeemer->id,
                            ])->to('member.vouchers-v2.my-vouchers');

                            $this->processing = false;

                            return;
                        }

                        Log::info('Voucher claim found for redeemer', [
                            'redeemer_fin' => $this->redeemer->fin,
                            'voucher_code' => $this->voucher->voucher_code,
                            'current_status' => $pivot->pivot->status,
                            'claimed_at' => $pivot->pivot->claimed_at,
                        ]);

                        // Check if voucher is already redeemed
                        if ($pivot->pivot->status === 'redeemed') {
                            $errorMsg = 'Voucher has already been redeemed.';
                            Log::warning('Attempted to redeem already redeemed voucher', [
                                'redeemer_fin' => $this->redeemer->fin,
                                'voucher_code' => $this->voucher->voucher_code,
                                'redeemed_at' => $pivot->pivot->redeemed_at,
                            ]);
                            $this->error = $errorMsg;

                            // Dispatch error event to notify member
                            $this->dispatch('voucher-redeemed', [
                                'type' => 'error',
                                'message' => "Voucher '{$this->voucher->name}' has already been redeemed.",
                                'voucher_code' => $this->voucher->voucher_code,
                                'member_id' => $this->redeemer->id,
                            ])->to('member.vouchers-v2.my-vouchers');

                            $this->processing = false;

                            return;
                        }

                        // Check if voucher status is 'claimed' (required for redemption)
                        if ($pivot->pivot->status !== 'claimed') {
                            $errorMsg = 'Voucher must be claimed before redemption.';
                            Log::warning('Voucher not in claimed status', [
                                'redeemer_fin' => $this->redeemer->fin,
                                'voucher_code' => $this->voucher->voucher_code,
                                'current_status' => $pivot->pivot->status,
                            ]);
                            $this->error = $errorMsg;

                            // Dispatch error event to notify member
                            $this->dispatch('voucher-redeemed', [
                                'type' => 'error',
                                'message' => "Voucher '{$this->voucher->name}' must be claimed before redemption.",
                                'voucher_code' => $this->voucher->voucher_code,
                                'member_id' => $this->redeemer->id,
                            ])->to('member.vouchers-v2.my-vouchers');

                            $this->processing = false;

                            return;
                        }

                        // Mark voucher as redeemed
                        $this->redeemer->vouchers()->updateExistingPivot($this->voucher->id, [
                            'status' => 'redeemed',
                            'redeemed_at' => now(),
                        ]);

                        // Note: usage_count is only incremented on claim, not redemption

                        // Award points for voucher redemption
                        $pointsBefore = $this->redeemer->total_points;
                        $pointsAwarded = 0;

                        app(PointsService::class)->awardVoucherRedeem($this->redeemer, $this->voucher);

                        $this->redeemer->refresh();
                        $pointsAwarded = $this->redeemer->total_points - $pointsBefore;

                        Log::info('Voucher redeemed by merchant - SUCCESS', [
                            'merchant_id' => $currentUser->id,
                            'merchant_name' => $currentUser->currentMerchant()?->name,
                            'member_fin' => $this->redeemer->fin,
                            'member_id' => $this->redeemer->id,
                            'voucher_code' => $this->voucher->voucher_code,
                            'voucher_id' => $this->voucher->id,
                            'voucher_name' => $this->voucher->name,
                            'points_awarded' => $pointsAwarded,
                            'redeemed_at' => now()->toDateTimeString(),
                        ]);

                        // Set success message
                        $this->success = true;
                        $this->successMessage = 'Voucher redeemed successfully!';

                        // Dispatch event to notify member in real-time
                        $merchantName = $currentUser->currentMerchant()?->name ?? 'the merchant';
                        $this->dispatch('voucher-redeemed', [
                            'type' => 'success',
                            'message' => "Your voucher '{$this->voucher->name}' has been successfully redeemed at {$merchantName}!".($pointsAwarded > 0 ? " You earned {$pointsAwarded} points!" : ''),
                            'voucher_code' => $this->voucher->voucher_code,
                            'member_id' => $this->redeemer->id,
                        ])->to('member.vouchers-v2.my-vouchers');

                        // Update redeemer voucher status
                        $this->redeemerVoucherStatus = 'redeemed';

                        $this->processing = false;
                    });
                } catch (\Exception $e) {
                    Log::error('Failed to redeem voucher by merchant - EXCEPTION', [
                        'merchant_id' => $currentUser?->id,
                        'member_fin' => $this->redeemer?->fin,
                        'voucher_code' => $this->voucher?->voucher_code,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $errorMessage = 'Failed to redeem voucher: '.($e->getMessage() ?? 'Unknown error');
                    $this->error = $errorMessage;

                    // Dispatch error event to notify member
                    if ($this->redeemer) {
                        $this->dispatch('voucher-redeemed', [
                            'type' => 'error',
                            'message' => "Failed to redeem voucher '{$this->voucher->name}': ".($e->getMessage() ?? 'Unknown error'),
                            'voucher_code' => $this->voucher->voucher_code,
                            'member_id' => $this->redeemer->id,
                        ])->to('member.vouchers-v2.my-vouchers');
                    }

                    $this->processing = false;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to process redeemer voucher redemption - OUTER EXCEPTION', [
                'user_id' => $currentUser?->id,
                'voucher_code' => $this->voucherCode,
                'is_admin_voucher' => $this->isAdminVoucher,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error = 'Failed to process voucher redemption: '.$e->getMessage();
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.voucher-qr-code-modal');
    }
}
