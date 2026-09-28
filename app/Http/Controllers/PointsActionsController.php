<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\AdminVoucher;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Location;
use App\Models\User;
use App\Models\Voucher;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsActionsController extends Controller
{
    /**
     * Member entry to a location (+10 points)
     * Expects: fin, location_id
     */
    public function locationEntry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fin' => ['required', 'string'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $user = User::query()->where('fin', $data['fin'])->firstOrFail();
        $location = Location::query()->findOrFail($data['location_id']);

        DB::transaction(function () use ($user, $location) {
            $activityType = ActivityType::where('name', PointsService::ACTIVITY_LOCATION_ENTRY)->first();
            if (! $activityType) {
                throw new \Exception('Activity type "'.PointsService::ACTIVITY_LOCATION_ENTRY.'" does not exist.');
            }

            $memberActivity = $user->memberActivities()->create([
                'activity_type_id' => $activityType->id,
                'location_id' => $location->id,
                'amenity_id' => null,
                'activity_time' => now(),
                'description' => 'Member entry to location',
                'metadata' => null,
            ]);

            app(PointsService::class)->award(
                user: $user,
                activityName: PointsService::ACTIVITY_LOCATION_ENTRY,
                description: 'Member entry to location',
                locationId: $location->id,
                memberActivityId: $memberActivity->id,
            );
        });

        return response()->json([
            'ok' => true,
            'fin' => $user->fin,
            'total_points' => $user->fresh()->total_points,
        ]);
    }

    /**
     * Mark member attended an event (+20 points)
     * Expects: fin, event_id OR event_code
     */
    public function attendEvent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fin' => ['required', 'string'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'event_code' => ['nullable', 'string'],
        ]);

        $user = User::query()->where('fin', $data['fin'])->firstOrFail();

        $event = Event::query()
            ->when($data['event_id'] ?? null, fn ($q) => $q->whereKey($data['event_id']))
            ->when($data['event_code'] ?? null, fn ($q) => $q->where('event_code', $data['event_code']))
            ->firstOrFail();

        $registration = EventRegistration::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->firstOrFail();

        if ($registration->status === 'attended') {
            return response()->json([
                'ok' => true,
                'already_attended' => true,
                'total_points' => $user->total_points,
            ]);
        }

        DB::transaction(function () use ($registration, $user, $event) {
            $registration->update([
                'status' => 'attended',
                'attended_at' => now(),
            ]);

            app(PointsService::class)->awardEventAttend($user, $event);
        });

        return response()->json([
            'ok' => true,
            'total_points' => $user->fresh()->total_points,
        ]);
    }

    /**
     * Redeem a claimed voucher (+5 points)
     * Expects: fin, voucher_code
     */
    public function redeemVoucher(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fin' => ['required', 'string'],
            'voucher_code' => ['required', 'string', 'exists:vouchers,voucher_code'],
        ]);

        $user = User::query()->where('fin', $data['fin'])->firstOrFail();
        $voucher = Voucher::query()->where('voucher_code', $data['voucher_code'])->firstOrFail();

        $pivot = $user->vouchers()
            ->where('vouchers.id', $voucher->id)
            ->first();

        if (! $pivot) {
            return response()->json([
                'ok' => false,
                'message' => 'Voucher not claimed by this member.',
            ], 422);
        }

        if ($pivot->pivot->status === 'redeemed') {
            return response()->json([
                'ok' => true,
                'already_redeemed' => true,
                'total_points' => $user->total_points,
            ]);
        }

        // Block inactive / out-of-date vouchers - no redemption or points (usage limit is claim-only)
        if (! $voucher->isRedeemable()) {
            $reason = $voucher->getRedeemStatusReason();

            return response()->json([
                'ok' => false,
                'message' => $reason ? "Voucher cannot be redeemed: {$reason}." : 'Voucher is not valid.',
            ], 422);
        }

        DB::transaction(function () use ($user, $voucher) {
            // Mark redeemed on pivot
            $user->vouchers()->updateExistingPivot($voucher->id, [
                'status' => 'redeemed',
                'redeemed_at' => now(),
            ]);

            // Note: usage_count is only incremented on claim, not redemption

            app(PointsService::class)->awardVoucherRedeem($user, $voucher);
        });

        return response()->json([
            'ok' => true,
            'total_points' => $user->fresh()->total_points,
        ]);
    }

    /**
     * Redeem a claimed admin voucher
     * Expects: fin, voucher_code
     */
    public function redeemAdminVoucher(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fin' => ['required', 'string'],
            'voucher_code' => ['required', 'string', 'exists:admin_vouchers,voucher_code'],
        ]);

        $user = User::query()->where('fin', $data['fin'])->firstOrFail();
        $adminVoucher = AdminVoucher::query()->where('voucher_code', $data['voucher_code'])->firstOrFail();

        $pivot = $user->adminVouchers()
            ->where('admin_vouchers.id', $adminVoucher->id)
            ->first();

        if (! $pivot) {
            return response()->json([
                'ok' => false,
                'message' => 'Admin voucher not claimed by this member.',
            ], 422);
        }

        if ($pivot->pivot->status === 'redeemed') {
            return response()->json([
                'ok' => true,
                'already_redeemed' => true,
                'total_points' => $user->total_points,
            ]);
        }

        // Block inactive / out-of-date admin vouchers - no redemption (usage limit is claim-only)
        if (! $adminVoucher->isRedeemable()) {
            $reason = $adminVoucher->getRedeemStatusReason();

            return response()->json([
                'ok' => false,
                'message' => $reason ? "Voucher cannot be redeemed: {$reason}." : 'Admin voucher is not valid.',
            ], 422);
        }

        DB::transaction(function () use ($user, $adminVoucher) {
            // Mark redeemed on pivot
            $user->adminVouchers()->updateExistingPivot($adminVoucher->id, [
                'status' => 'redeemed',
                'redeemed_at' => now(),
            ]);

            app(PointsService::class)->recordAdminVoucherRedeem($user, $adminVoucher);

            // Note: usage_count is only incremented on claim, not redemption
            // Note: No points are awarded for admin voucher redemption
            // Points were already deducted when the voucher was claimed
        });

        return response()->json([
            'ok' => true,
            'total_points' => $user->fresh()->total_points,
        ]);
    }
}
