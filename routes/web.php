<?php

use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PointsActionsController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\SingpassController;
use App\Http\Controllers\VerificationCodeController;
use App\Http\Controllers\WhatsAppValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Custom Password Reset Routes (override Fortify's default)
Route::get('/forgot-password', [PasswordResetController::class, 'show'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
Route::get('/forgot-password/verify-otp', [PasswordResetController::class, 'showVerifyOtp'])->name('password.reset.verify');
Route::post('/forgot-password/verify-otp', [PasswordResetController::class, 'verifyOtp'])
    ->middleware('throttle:10,1')
    ->name('password.reset.verify.submit');
Route::post('/forgot-password/resend-otp', [PasswordResetController::class, 'resendOtp'])
    ->middleware('throttle:3,1')
    ->name('password.reset.resend');

// WhatsApp Validation Check (needs CSRF protection, so in web routes)
Route::post('/api/check-whatsapp', [WhatsAppValidationController::class, 'check'])
    ->name('api.check-whatsapp');

// Public Merchant Application Route
Route::get('/merchant/apply', \App\Livewire\Merchant\Apply::class)->name('merchant.apply');

// Singpass Authentication Routes
Route::get('/auth/singpass', [SingpassController::class, 'redirect'])->name('singpass.redirect');
Route::get('/auth/singpass/callback', [SingpassController::class, 'callback'])->name('singpass.callback');
// Client JWKS URL – must be publicly reachable by Singpass (PX-E0101). Register this URL in Singpass Developer Portal.
Route::get('/.well-known/jwks.json', [SingpassController::class, 'jwks'])->name('singpass.jwks');
Route::get('/well-known/jwks.json', [SingpassController::class, 'jwks']); // fallback if server strips leading dot

// Member verification (OTP via email / WhatsApp)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
])->group(function () {
    Route::get('/verify-account', [VerificationCodeController::class, 'show'])->name('verification.code.show');
    Route::post('/verify-account', [VerificationCodeController::class, 'verify'])->name('verification.code.verify');
    Route::post('/verify-account/resend', [VerificationCodeController::class, 'resend'])->name('verification.code.resend');

    // QR Code route - Available to all authenticated users (admin, merchant_user, member)
    Route::get('/qr-code/full', [QrCodeController::class, 'fullSize'])->name('qr-code.full');

    // User points endpoint for real-time updates
    Route::get('/api/user/points', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'total_points' => $user->total_points ?? 0,
        ]);
    })->name('api.user.points');
});

// Admin Dashboard - Only accessible by admin users
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    // 'verified',
    'admin',
])->group(function () {
    // Route::get('/admin/dashboard', function () {
    //     return view('admin.dashboard');
    // })->name('admin.dashboard');

    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard-v2');
    })->name('admin.dashboard');

    // Location CRUD Routes
    Route::get('/admin/locations', \App\Livewire\Locations\Index::class)->name('admin.locations.index');
    Route::get('/admin/locations/create', \App\Livewire\Locations\Form::class)->name('admin.locations.create');
    Route::get('/admin/locations/{location_code}/edit', \App\Livewire\Locations\Form::class)->name('admin.locations.edit');
    Route::get('/admin/locations/{location_code}', \App\Livewire\Locations\Profile::class)->name('admin.locations.profile');

    // Event CRUD Routes
    Route::get('/admin/events', \App\Livewire\Events\AllEvents::class)->name('admin.events.index');
    Route::get('/admin/events/{event_code}', \App\Livewire\Events\Profile::class)->name('admin.events.profile');
    Route::get('/admin/locations/{location_code}/events', \App\Livewire\Events\Index::class)->name('admin.locations.events.index');
    Route::get('/admin/locations/{location_code}/events/create', \App\Livewire\Events\Form::class)->name('admin.locations.events.create');
    Route::get('/admin/locations/{location_code}/events/{id}/edit', \App\Livewire\Events\Form::class)->name('admin.locations.events.edit');

    // Amenity CRUD Routes
    Route::get('/admin/amenities', \App\Livewire\Amenities\Index::class)->name('admin.amenities.index');
    Route::get('/admin/amenities/create', \App\Livewire\Amenities\Form::class)->name('admin.amenities.create');
    Route::get('/admin/amenities/{id}/edit', \App\Livewire\Amenities\Form::class)->name('admin.amenities.edit');

    // Members
    Route::get('/admin/members', \App\Livewire\Members\Index::class)->name('admin.members.index');
    Route::get('/admin/members/activities', \App\Livewire\Members\Activities::class)->name('admin.members.activities');
    Route::get('/admin/members/activities/export', \App\Http\Controllers\MemberActivitiesExportController::class)->name('admin.members.activities.export');
    Route::get('/admin/members/{qr_code}', \App\Livewire\Members\Profile::class)->name('admin.members.profile');

    // Merchant CRUD Routes
    Route::get('/admin/merchants', \App\Livewire\Merchants\Index::class)->name('admin.merchants.index');
    Route::get('/admin/merchants/create', \App\Livewire\Merchants\Form::class)->name('admin.merchants.create');
    Route::get('/admin/merchants/{merchant_code}/edit', \App\Livewire\Merchants\Form::class)->name('admin.merchants.edit');
    Route::get('/admin/merchants/{merchant_code}', \App\Livewire\Merchants\Profile::class)->name('admin.merchants.profile');

    // Voucher CRUD Routes
    Route::get('/admin/vouchers', \App\Livewire\Vouchers\Index::class)->name('admin.vouchers.index');
    Route::get('/admin/vouchers/create', \App\Livewire\Vouchers\Form::class)->name('admin.vouchers.create');
    Route::get('/admin/vouchers/{voucher_code}/edit', \App\Livewire\Vouchers\Form::class)->name('admin.vouchers.edit');
    Route::get('/admin/vouchers/{voucher_code}', \App\Livewire\Vouchers\Profile::class)->name('admin.vouchers.profile');

    // Admin Voucher CRUD Routes
    Route::get('/admin/admin-vouchers', \App\Livewire\AdminVouchers\Index::class)->name('admin.admin-vouchers.index');
    Route::get('/admin/admin-vouchers/create', \App\Livewire\AdminVouchers\Form::class)->name('admin.admin-vouchers.create');
    Route::get('/admin/admin-vouchers/{voucher_code}/edit', \App\Livewire\AdminVouchers\Form::class)->name('admin.admin-vouchers.edit');
    Route::get('/admin/admin-vouchers/{voucher_code}/profile', \App\Livewire\AdminVouchers\Profile::class)->name('admin.admin-vouchers.profile');

    // Marketplace (points store)
    Route::get('/admin/marketplace/cashier', \App\Livewire\Marketplace\Cashier::class)->name('admin.marketplace.cashier');
    Route::get('/admin/marketplace/orders', \App\Livewire\Marketplace\Orders::class)->name('admin.marketplace.orders');
    Route::get('/admin/marketplace/create', \App\Livewire\Marketplace\Form::class)->name('admin.marketplace.create');
    Route::get('/admin/marketplace/{id}/inventory', \App\Livewire\Marketplace\Inventory::class)->name('admin.marketplace.inventory');
    Route::get('/admin/marketplace/{id}/history', \App\Livewire\Marketplace\History::class)->name('admin.marketplace.history');
    Route::get('/admin/marketplace/{id}/edit', \App\Livewire\Marketplace\Form::class)->name('admin.marketplace.edit');
    Route::get('/admin/marketplace', \App\Livewire\Marketplace\Index::class)->name('admin.marketplace.index');

    // Admin Voucher Ledger – Transaction History PDF (opens in new tab)
    Route::get('/admin/admin-voucher-ledger/{entry}/transaction-history-pdf', \App\Http\Controllers\AdminVoucherLedgerTransactionHistoryPdfController::class)
        ->name('admin.admin-voucher-ledger.transaction-history-pdf');

    // Point System CRUD Routes
    Route::get('/admin/point-system', \App\Livewire\PointSystem\Index::class)->name('admin.point-system.index');
    Route::get('/admin/point-system/create', \App\Livewire\PointSystem\Form::class)->name('admin.point-system.create');
    Route::get('/admin/point-system/{id}/edit', \App\Livewire\PointSystem\Form::class)->name('admin.point-system.edit');

    // Settings CRUD Routes
    Route::get('/admin/settings', \App\Livewire\Settings\Index::class)->name('admin.settings.index');

    // API Documentation
    Route::get('/admin/api-documentation', \App\Livewire\ApiDocumentation\Index::class)->name('admin.api-documentation.index');

    // Database Backups
    Route::get('/admin/backups', \App\Livewire\Admin\Backups\Index::class)->name('admin.backups.index');

    // Raffle / Roulette
    Route::get('/admin/raffle-v1', \App\Livewire\Raffle\Roulette::class)->name('admin.raffle.v1');
    Route::get('/admin/raffle', \App\Livewire\Raffle\RouletteV2::class)->name('admin.raffle');

    // News CRUD Routes
    Route::get('/admin/news', \App\Livewire\News\Index::class)->name('admin.news.index');
    Route::get('/admin/news/create', \App\Livewire\News\Form::class)->name('admin.news.create');
    Route::get('/admin/news/{id}/edit', \App\Livewire\News\Form::class)->name('admin.news.edit');
    Route::get('/admin/news/{id}', \App\Livewire\News\Profile::class)->name('admin.news.profile');

    // News Categories CRUD Routes
    Route::get('/admin/news-categories', \App\Livewire\NewsCategories\Index::class)->name('admin.news-categories.index');
    Route::get('/admin/news-categories/create', \App\Livewire\NewsCategories\Form::class)->name('admin.news-categories.create');
    Route::get('/admin/news-categories/{id}/edit', \App\Livewire\NewsCategories\Form::class)->name('admin.news-categories.edit');

    // Announcements CRUD Routes
    Route::get('/admin/announcements', \App\Livewire\Announcements\Index::class)->name('admin.announcements.index');
    Route::get('/admin/announcements/create', \App\Livewire\Announcements\Form::class)->name('admin.announcements.create');
    Route::get('/admin/announcements/{id}/edit', \App\Livewire\Announcements\Form::class)->name('admin.announcements.edit');
    Route::get('/admin/announcements/{id}', \App\Livewire\Announcements\Profile::class)->name('admin.announcements.profile');

    // Points actions (admin-operated)
    Route::post('/admin/points/location-entry', [PointsActionsController::class, 'locationEntry'])->name('admin.points.location-entry');
    Route::post('/admin/points/event-attend', [PointsActionsController::class, 'attendEvent'])->name('admin.points.event-attend');

    // Admin user permissions management
    Route::get('/admin/user-permissions', \App\Livewire\Admin\UserPermissions::class)->name('admin.user-permissions');

    // Admin Users - Superadmin-only
    Route::get('/admin/administrator-users', \App\Livewire\Admin\AdministratorUsers::class)->name('admin.administrator-users');
});

// Member Dashboard - Only accessible by member users
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    // 'verified',
    'member',
    // 'hv_verified',
])->group(function () {

    //  ====== to be deleted
    Route::get('/member/dashboard-v1', function () {
        return view('member.dashboard');
    })->name('member.dashboard.v1');

    Route::get('/member/events-v1', function () {
        return view('member.events');
    })->name('member.events.v1');

    Route::get('/member/events', function () {
        return view('member.events-v2');
    })->name('member.events');

    Route::get('/member/dashboard', function () {
        return view('member.dashboard-v2');
    })->name('member.dashboard');

    Route::get('/member/vouchers', function () {
        return view('member.vouchers-v3');
    })->name('member.vouchers');

    // Marketplace (points)
    Route::get('/member/marketplace/my-orders', \App\Livewire\Member\Marketplace\MyOrders::class)->name('member.marketplace.my-orders');
    Route::get('/member/marketplace/cart', function () {
        return redirect()->route('member.marketplace.index');
    })->name('member.marketplace.cart');
    Route::get('/member/marketplace', \App\Livewire\Member\Marketplace\Browse::class)->name('member.marketplace.index');

    // Member Activities History
    Route::get('/member/activities', \App\Livewire\Member\Activities::class)
        ->name('member.activities');

    Route::get('/member/event/{event_code}', \App\Livewire\Member\Events\Profile::class)
        ->name('member.events.profile');

    // Referral System
    Route::get('/member/referral-system', \App\Livewire\Member\ReferralSystem::class)
        ->name('member.referral-system');

    // News (published by admin)
    Route::get('/member/news', \App\Livewire\Member\News\Index::class)->name('member.news');
    Route::get('/member/news/{slug}', \App\Livewire\Member\News\Profile::class)->name('member.news.profile');

    // Partner merchants (blade wrapper pins the correct Livewire class for members)
    Route::get('/member/merchants', function () {
        return view('member.merchants');
    })->name('member.merchants.index');
    Route::get('/member/merchants/{merchant_code}', function (string $merchant_code) {
        return view('member.merchant-profile', ['merchant_code' => $merchant_code]);
    })->name('member.merchants.profile');

    // QR Code routes
    Route::get('/member/qr-code', [QrCodeController::class, 'show'])->name('member.qr-code');
});

// Merchant Dashboard - Only accessible by merchant users
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    // 'verified',
    'merchant_user',
])->group(function () {
    Route::get('/merchant/dashboard', function () {
        return view('merchant.dashboard');
    })->name('merchant.dashboard');

    // Merchant Voucher CRUD Routes
    Route::get('/merchant/vouchers', \App\Livewire\Merchant\Vouchers\Index::class)->name('merchant.vouchers.index');
    Route::get('/merchant/vouchers/create', \App\Livewire\Merchant\Vouchers\Form::class)->name('merchant.vouchers.create');
    Route::get('/merchant/vouchers/{voucher_code}/edit', \App\Livewire\Merchant\Vouchers\Form::class)->name('merchant.vouchers.edit');
    Route::get('/merchant/vouchers/{voucher_code}', \App\Livewire\Merchant\Vouchers\Profile::class)->name('merchant.vouchers.profile');

    Route::get('/merchant/redemptions', \App\Livewire\Merchant\Redemptions\Index::class)->name('merchant.redemptions.index');

    // Points actions (merchant-operated)
    Route::post('/merchant/points/voucher-redeem', [PointsActionsController::class, 'redeemVoucher'])->name('merchant.points.voucher-redeem');
});

// Legacy dashboard route - redirects based on user type
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    // 'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isMember()) {
            return redirect()->route('member.dashboard');
        } elseif ($user->isMerchantUser()) {
            return redirect()->route('merchant.dashboard');
        }
        abort(403, 'Invalid user type');
    })->name('dashboard');
});
