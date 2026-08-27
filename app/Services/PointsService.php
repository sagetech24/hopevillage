<?php

namespace App\Services;

use App\Models\ActivityType;
use App\Models\AdminVoucher;
use App\Models\Event;
use App\Models\MemberActivity;
use App\Models\PointLog;
use App\Models\PointSystemConfig;
use App\Models\Setting;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class PointsService
{
    public const ACTIVITY_ACCOUNT_VERIFICATION = 'account_verification';

    public const ACTIVITY_REGISTRATION = 'member_registration';

    public const ACTIVITY_LOCATION_ENTRY = 'member_entry_location';

    public const ACTIVITY_EVENT_JOIN = 'member_join_event';

    public const ACTIVITY_EVENT_ATTEND = 'member_attend_event';

    public const ACTIVITY_VOUCHER_CLAIM = 'member_claim_voucher';

    public const ACTIVITY_VOUCHER_REDEEM = 'member_redeem_voucher';

    public const ACTIVITY_ADMIN_VOUCHER_CLAIM = 'member_claim_admin_voucher';

    public const ACTIVITY_ADMIN_AWARD_ADMIN_VOUCHER = 'admin_award_admin_voucher';

    public const ACTIVITY_ADMIN_VOUCHER_VOID = 'admin_void_admin_voucher';

    public const ACTIVITY_REFERRAL = 'member_referral';

    public const ACTIVITY_MARKETPLACE_REDEEM = 'marketplace_redeem';

    public const ACTIVITY_MARKETPLACE_REFUND = 'marketplace_refund';

    // Fallback points (used only if no admin configuration exists)
    private const FALLBACK_POINTS_ACCOUNT_VERIFICATION = 10;

    private const FALLBACK_POINTS_REGISTRATION = 10;

    private const FALLBACK_POINTS_LOCATION_ENTRY = 10;

    private const FALLBACK_POINTS_EVENT_JOIN = 10;

    private const FALLBACK_POINTS_EVENT_ATTEND = 20;

    private const FALLBACK_POINTS_VOUCHER_CLAIM = 10;

    private const FALLBACK_POINTS_VOUCHER_REDEEM = 5;

    private const FALLBACK_POINTS_REFERRAL = 5;

    public function awardAccountVerification(User $user): void
    {
        $this->award(
            user: $user,
            activityName: self::ACTIVITY_ACCOUNT_VERIFICATION,
            description: 'Account created and verified',
            locationId: null,
        );
    }

    public function awardRegistration(User $user): void
    {

        // Create member activity record
        $memberActivity = MemberActivity::create([
            'user_id' => $user->id,
            'activity_type_id' => ActivityType::where('name', 'member_registration')->first()->id,
            'location_id' => 1, // hardcoded location id for now
            'amenity_id' => null,
            'activity_time' => now(),
            'description' => 'Member account created.',
            'metadata' => [
                'scanned_at' => now()->toIso8601String(),
                'location_code' => 1, // hardcoded location id for now
                'qr_code' => $user->qr_code,
                'device_info' => 'mobile_device',
                'access_type' => 'browser_application',
            ],
        ]);

        $memberActivity->save();

        $this->award(
            user: $user,
            activityName: 'member_registration',
            description: 'Member account created',
            locationId: null,
            memberActivityId: $memberActivity->id,
            amenityId: null,
        );

    }

    public function awardLocationEntry(User $user, int $locationId): void
    {
        $this->award(
            user: $user,
            activityName: self::ACTIVITY_LOCATION_ENTRY,
            description: 'Member entry to location',
            locationId: $locationId,
        );
    }

    public function awardEventJoin(User $user, Event $event): void
    {
        $this->award(
            user: $user,
            activityName: self::ACTIVITY_EVENT_JOIN,
            description: 'Member joined event',
            locationId: $event->location_id,
        );
    }

    public function awardEventAttend(User $user, Event $event): void
    {
        $this->award(
            user: $user,
            activityName: self::ACTIVITY_EVENT_ATTEND,
            description: 'Member attended event',
            locationId: $event->location_id,
        );
    }

    public function awardVoucherClaim(User $user, Voucher $voucher): void
    {
        $this->award(
            user: $user,
            activityName: self::ACTIVITY_VOUCHER_CLAIM,
            description: 'Member claimed voucher '.$voucher->voucher_code,
            locationId: null,
        );
    }

    public function awardVoucherRedeem(User $user, Voucher $voucher): void
    {
        $this->award(
            user: $user,
            activityName: self::ACTIVITY_VOUCHER_REDEEM,
            description: 'Member redeemed voucher '.$voucher->voucher_code,
            locationId: null,
        );
    }

    public function awardReferral(User $referrer, User $referredUser): void
    {
        // log the activity to the member activity table
        $memberActivity = MemberActivity::create([
            'user_id' => $referrer->id,
            'activity_type_id' => ActivityType::where('name', 'member_referral')->first()->id,
            'location_id' => 1, // hardcoded location id for now
            'amenity_id' => null,
            'activity_time' => now(),
            'description' => 'Referred new member: '.$referredUser->name.' ('.$referredUser->qr_code.')',
            'metadata' => [
                'scanned_at' => now()->toIso8601String(),
                'location_code' => 1, // hardcoded location id for now
                'referred_user_qr_code' => $referredUser->qr_code,
                'referrer_user_qr_code' => $referrer->qr_code,
                'device_info' => 'mobile_device',
                'access_type' => 'browser_application',
            ],
        ]);
        $memberActivity->save();

        $this->award(
            user: $referrer,
            activityName: 'member_referral',
            description: 'Referred new member: '.$referredUser->name.' ('.$referredUser->qr_code.')',
            locationId: null,
            memberActivityId: $memberActivity->id,
            amenityId: null,
        );
    }

    /**
     * Get fallback points based on activity name
     */
    private function getFallbackPoints(string $activityName): int
    {
        return match ($activityName) {
            self::ACTIVITY_ACCOUNT_VERIFICATION => self::FALLBACK_POINTS_ACCOUNT_VERIFICATION,
            self::ACTIVITY_REGISTRATION => self::FALLBACK_POINTS_REGISTRATION,
            self::ACTIVITY_LOCATION_ENTRY => self::FALLBACK_POINTS_LOCATION_ENTRY,
            self::ACTIVITY_EVENT_JOIN => self::FALLBACK_POINTS_EVENT_JOIN,
            self::ACTIVITY_EVENT_ATTEND => self::FALLBACK_POINTS_EVENT_ATTEND,
            self::ACTIVITY_VOUCHER_CLAIM => self::FALLBACK_POINTS_VOUCHER_CLAIM,
            self::ACTIVITY_VOUCHER_REDEEM => self::FALLBACK_POINTS_VOUCHER_REDEEM,
            self::ACTIVITY_REFERRAL => self::FALLBACK_POINTS_REFERRAL,
            default => 0,
        };
    }

    /**
     * Get or create point system configuration
     */
    private function getOrCreateConfig(
        int $activityTypeId,
        ?int $locationId = null,
        ?int $amenityId = null,
        string $activityName = '',
        ?string $description = null
    ): PointSystemConfig {
        // First, try to find a specific config (with location and/or amenity)
        $config = PointSystemConfig::where('activity_type_id', $activityTypeId)
            ->where('location_id', $locationId)
            ->where('amenity_id', $amenityId)
            ->where('is_active', true)
            ->first();

        if ($config) {
            return $config;
        }

        // If no specific config, try global config (no location, no amenity)
        $globalConfig = PointSystemConfig::where('activity_type_id', $activityTypeId)
            ->whereNull('location_id')
            ->whereNull('amenity_id')
            ->where('is_active', true)
            ->first();

        if ($globalConfig) {
            return $globalConfig;
        }

        // No admin config found, use fallback and create a default config
        $points = $this->getFallbackPoints($activityName);

        return PointSystemConfig::query()->firstOrCreate(
            [
                'activity_type_id' => $activityTypeId,
                'location_id' => $locationId,
                'amenity_id' => $amenityId,
            ],
            [
                'points' => $points,
                'description' => $description ?? $activityName,
                'is_active' => true,
            ]
        );
    }

    public function award(
        User $user,
        string $activityName,
        ?string $description = null,
        ?int $locationId = null,
        ?int $memberActivityId = null,
        ?int $amenityId = null,
    ): void {
        // Check if point system is globally enabled
        $pointSystemEnabled = (bool) Setting::get('point_system_enabled', true);

        if (! $pointSystemEnabled) {
            // Point system is disabled, don't award points
            return;
        }

        DB::transaction(function () use ($user, $activityName, $description, $locationId, $memberActivityId, $amenityId) {
            $activityType = ActivityType::where('name', $activityName)->first();
            if (! $activityType) {
                throw new \Exception('Activity type not found: '.$activityName);
            }

            // Get or create the point system configuration
            $config = $this->getOrCreateConfig(
                $activityType->id,
                $locationId,
                $amenityId,
                $activityName,
                $description
            );

            // Use points from the configuration
            $points = $config->points;

            PointLog::query()->create([
                'user_id' => $user->id,
                'member_activity_id' => $memberActivityId,
                'point_system_config_id' => $config->id,
                'activity_type_id' => $activityType->id,
                'location_id' => $locationId,
                'amenity_id' => $amenityId,
                'points' => $points,
                'description' => $description ?? $activityName,
                'awarded_at' => now(),
            ]);

            // Keep user's running total in sync.
            $user->increment('total_points', $points);
        });
    }

    /**
     * Deduct points from user's balance
     */
    public function deduct(
        User $user,
        int $points,
        string $activityName,
        ?string $description = null,
        ?int $locationId = null,
        ?int $amenityId = null,
        ?int $memberActivityId = null,
    ): void {
        DB::transaction(function () use ($user, $points, $activityName, $description, $locationId, $amenityId, $memberActivityId) {
            $this->deductWithinTransaction($user, $points, $activityName, $description, $locationId, $amenityId, $memberActivityId);
        });
    }

    /**
     * Deduct points; caller must wrap in {@see DB::transaction} and lock rows as needed.
     *
     * @param  ?int  $memberActivityId  When set, stored on {@see PointLog} (e.g. marketplace redeem).
     */
    public function deductWithinTransaction(
        User $user,
        int $points,
        string $activityName,
        ?string $description = null,
        ?int $locationId = null,
        ?int $amenityId = null,
        ?int $memberActivityId = null,
    ): void {
        $pointSystemEnabled = (bool) Setting::get('point_system_enabled', true);

        if (! $pointSystemEnabled) {
            throw new \Exception('Point system is disabled.');
        }

        if ($user->total_points < $points) {
            throw new \Exception('Insufficient points balance.');
        }

        $activityType = $this->resolveActivityTypeForPointMovement($activityName);

        $config = $this->getOrCreateConfig(
            $activityType->id,
            $locationId,
            $amenityId,
            $activityName,
            $description
        );

        PointLog::query()->create([
            'user_id' => $user->id,
            'member_activity_id' => $memberActivityId,
            'point_system_config_id' => $config->id,
            'activity_type_id' => $activityType->id,
            'location_id' => $locationId,
            'amenity_id' => $amenityId,
            'points' => -$points,
            'description' => $description ?? $activityName,
            'awarded_at' => now(),
        ]);

        $user->decrement('total_points', $points);
    }

    /**
     * Credit points (e.g. marketplace order cancellation); caller must wrap in {@see DB::transaction}.
     */
    public function creditPointsWithinTransaction(
        User $user,
        int $points,
        string $activityName,
        ?string $description = null,
        ?int $locationId = null,
        ?int $amenityId = null,
    ): void {
        if ($points <= 0) {
            return;
        }

        $pointSystemEnabled = (bool) Setting::get('point_system_enabled', true);
        if (! $pointSystemEnabled) {
            return;
        }

        $activityType = $this->resolveActivityTypeForPointMovement($activityName);

        $config = $this->getOrCreateConfig(
            $activityType->id,
            $locationId,
            $amenityId,
            $activityName,
            $description
        );

        PointLog::query()->create([
            'user_id' => $user->id,
            'member_activity_id' => null,
            'point_system_config_id' => $config->id,
            'activity_type_id' => $activityType->id,
            'location_id' => $locationId,
            'amenity_id' => $amenityId,
            'points' => $points,
            'description' => $description ?? $activityName,
            'awarded_at' => now(),
        ]);

        $user->increment('total_points', $points);
    }

    /**
     * Deduct points for admin voucher claim
     */
    public function deductAdminVoucherClaim(User $user, AdminVoucher $adminVoucher): void
    {
        $this->deduct(
            user: $user,
            points: $adminVoucher->points_cost,
            activityName: self::ACTIVITY_ADMIN_VOUCHER_CLAIM,
            description: 'Claimed admin voucher '.$adminVoucher->voucher_code.' - '.$adminVoucher->name,
            locationId: null,
        );
    }

    /**
     * Audit log when an admin awards an admin voucher to a member (no points movement).
     * Caller must wrap in {@see DB::transaction} when used alongside pivot attach.
     */
    public function logAdminVoucherAward(
        User $member,
        AdminVoucher $adminVoucher,
        ?string $reason,
        User $admin,
    ): void {
        $description = sprintf(
            'Admin %s (#%d) awarded voucher %s - %s to %s (#%d)',
            $admin->name,
            $admin->id,
            $adminVoucher->voucher_code,
            $adminVoucher->name,
            $member->name,
            $member->id,
        );

        if ($reason) {
            $description .= ' — '.$reason;
        }

        $activityType = $this->resolveActivityTypeForPointMovement(self::ACTIVITY_ADMIN_AWARD_ADMIN_VOUCHER);

        $config = PointSystemConfig::query()->firstOrCreate(
            [
                'activity_type_id' => $activityType->id,
                'location_id' => null,
                'amenity_id' => null,
            ],
            [
                'points' => 0,
                'description' => 'Admin awarded admin voucher to member',
                'is_active' => true,
            ],
        );

        PointLog::query()->create([
            'user_id' => $member->id,
            'point_system_config_id' => $config->id,
            'activity_type_id' => $activityType->id,
            'location_id' => null,
            'amenity_id' => null,
            'points' => 0,
            'description' => $description,
            'awarded_at' => now(),
        ]);
    }

    /**
     * Resolve how many points to refund when voiding an admin voucher assignment.
     * Uses the original claim PointLog when present; awards (0-point) refund nothing.
     */
    public function resolveAdminVoucherRefundAmount(User $member, AdminVoucher $adminVoucher): int
    {
        $claimLog = PointLog::query()
            ->where('user_id', $member->id)
            ->where('points', '<', 0)
            ->where('description', 'like', '%'.$adminVoucher->voucher_code.'%')
            ->whereHas('activityType', fn ($q) => $q->where('name', self::ACTIVITY_ADMIN_VOUCHER_CLAIM))
            ->orderByDesc('id')
            ->first();

        if (! $claimLog) {
            return 0;
        }

        $alreadyRefunded = PointLog::query()
            ->where('user_id', $member->id)
            ->where('points', '>', 0)
            ->where('description', 'like', '%'.$adminVoucher->voucher_code.'%')
            ->whereHas('activityType', fn ($q) => $q->where('name', self::ACTIVITY_ADMIN_VOUCHER_VOID))
            ->exists();

        if ($alreadyRefunded) {
            return 0;
        }

        return abs((int) $claimLog->points);
    }

    /**
     * Credit refund (when claim was paid) and always write a void audit PointLog.
     * Caller must wrap in {@see DB::transaction}.
     *
     * @return int Points credited to the member wallet
     */
    public function refundAdminVoucherVoid(
        User $member,
        AdminVoucher $adminVoucher,
        User $admin,
        string $previousStatus,
        ?string $reason = null,
    ): int {
        $refund = $this->resolveAdminVoucherRefundAmount($member, $adminVoucher);

        $description = sprintf(
            'Admin %s (#%d) voided %s voucher %s - %s for %s (#%d); refunded %d pts',
            $admin->name,
            $admin->id,
            $previousStatus,
            $adminVoucher->voucher_code,
            $adminVoucher->name,
            $member->name,
            $member->id,
            $refund,
        );

        if ($reason) {
            $description .= ' — '.$reason;
        }

        if ($refund > 0) {
            $this->creditPointsWithinTransaction(
                $member,
                $refund,
                self::ACTIVITY_ADMIN_VOUCHER_VOID,
                $description,
            );

            return $refund;
        }

        $activityType = $this->resolveActivityTypeForPointMovement(self::ACTIVITY_ADMIN_VOUCHER_VOID);

        $config = PointSystemConfig::query()->firstOrCreate(
            [
                'activity_type_id' => $activityType->id,
                'location_id' => null,
                'amenity_id' => null,
            ],
            [
                'points' => 0,
                'description' => 'Admin voided admin voucher assignment',
                'is_active' => true,
            ],
        );

        PointLog::query()->create([
            'user_id' => $member->id,
            'point_system_config_id' => $config->id,
            'activity_type_id' => $activityType->id,
            'location_id' => null,
            'amenity_id' => null,
            'points' => 0,
            'description' => $description,
            'awarded_at' => now(),
        ]);

        return 0;
    }

    /**
     * Resolve activity type (creating marketplace redeem/refund types when missing).
     * Use before creating a {@see MemberActivity} row that will be linked from {@see PointLog}.
     */
    public function activityTypeForPointMovement(string $activityName): ActivityType
    {
        return $this->resolveActivityTypeForPointMovement($activityName);
    }

    /**
     * Resolve activity type for point movements. Marketplace redeem/refund rows are created on demand
     * if migrations that seed them have not been run.
     */
    private function resolveActivityTypeForPointMovement(string $activityName): ActivityType
    {
        if ($activityName === self::ACTIVITY_MARKETPLACE_REDEEM) {
            return ActivityType::query()->firstOrCreate(
                ['name' => self::ACTIVITY_MARKETPLACE_REDEEM],
                [
                    'description' => 'Points spent on marketplace items',
                    'is_active' => true,
                ],
            );
        }

        if ($activityName === self::ACTIVITY_MARKETPLACE_REFUND) {
            return ActivityType::query()->firstOrCreate(
                ['name' => self::ACTIVITY_MARKETPLACE_REFUND],
                [
                    'description' => 'Points refunded from cancelled marketplace order',
                    'is_active' => true,
                ],
            );
        }

        if ($activityName === self::ACTIVITY_ADMIN_AWARD_ADMIN_VOUCHER) {
            return ActivityType::query()->firstOrCreate(
                ['name' => self::ACTIVITY_ADMIN_AWARD_ADMIN_VOUCHER],
                [
                    'description' => 'Admin awarded admin voucher to member',
                    'is_active' => true,
                ],
            );
        }

        if ($activityName === self::ACTIVITY_ADMIN_VOUCHER_VOID) {
            return ActivityType::query()->firstOrCreate(
                ['name' => self::ACTIVITY_ADMIN_VOUCHER_VOID],
                [
                    'description' => 'Admin voided admin voucher; points refunded when applicable',
                    'is_active' => true,
                ],
            );
        }

        if ($activityName === self::ACTIVITY_ADMIN_VOUCHER_CLAIM) {
            return ActivityType::query()->firstOrCreate(
                ['name' => self::ACTIVITY_ADMIN_VOUCHER_CLAIM],
                [
                    'description' => 'Points spent claiming an admin voucher',
                    'is_active' => true,
                ],
            );
        }

        $activityType = ActivityType::query()->where('name', $activityName)->first();
        if (! $activityType) {
            throw new \Exception("Activity type '{$activityName}' does not exist.");
        }

        return $activityType;
    }
}
