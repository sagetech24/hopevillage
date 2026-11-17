<?php

namespace App\Livewire\Members;

use App\Models\AdminVoucher;
use App\Models\EventRegistration;
use App\Models\MarketplaceOrder;
use App\Models\MemberActivity;
use App\Models\PointLog;
use App\Models\User;
use App\Services\AdminVoucherVoidService;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class SetActivityVoidButton extends Component
{
    public MemberActivity $memberActivity;

    public $showMessage = false;

    public string $messageText = '';

    public function setAsVoid(): void
    {
        $this->showMessage = false;
        $this->messageText = '';

        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to set activity as void.');

            return;
        }

        if (($this->memberActivity->metadata['status'] ?? null) === 'void') {
            return;
        }

        if ($this->memberActivity->activityType?->name === PointsService::ACTIVITY_ADMIN_VOUCHER_VOID) {
            return;
        }

        $pointLog = $this->memberActivity->pointLog;
        $user = $this->memberActivity->user;
        $activityTypeName = $this->memberActivity->activityType?->name;
        $meta = $this->memberActivity->metadata ?? [];
        $isMarketplaceRedeem = $activityTypeName === PointsService::ACTIVITY_MARKETPLACE_REDEEM;

        $pointsToDeduct = 0;
        if (! $isMarketplaceRedeem && $pointLog && $pointLog->points > 0) {
            $pointsToDeduct = min($pointLog->points, (int) $user->total_points);
        }

        $randomCode = Str::random(8);
        $admin = auth()->user();

        DB::transaction(function () use ($pointLog, $user, $pointsToDeduct, $randomCode, $activityTypeName, $meta, $isMarketplaceRedeem, $admin) {
            $voidContext = [
                'voided_at' => now()->toIso8601String(),
                'voided_by' => $admin->id,
                'trx_code' => $randomCode,
                'points_reversed' => $pointsToDeduct,
                'points_credited' => 0,
            ];

            // 1. Points reversal for award activities (positive point logs)
            if ($pointsToDeduct > 0 && $pointLog) {
                PointLog::query()->create([
                    'user_id' => $user->id,
                    'member_activity_id' => $this->memberActivity->id,
                    'point_system_config_id' => $pointLog->point_system_config_id,
                    'activity_type_id' => $pointLog->activity_type_id,
                    'location_id' => $pointLog->location_id,
                    'amenity_id' => $pointLog->amenity_id,
                    'points' => -$pointsToDeduct,
                    'description' => 'Points reversed - activity voided (Member Activity #'.$this->memberActivity->id.') - with trx code: '.$randomCode,
                    'awarded_at' => now(),
                ]);
                $user->decrement('total_points', $pointsToDeduct);
            }

            // 1b. Marketplace redeem: credit points back and void the fulfilled order
            if ($isMarketplaceRedeem) {
                $credited = $this->voidMarketplaceRedeem($user, $pointLog, $meta, $admin, $randomCode);
                $voidContext['points_credited'] = $credited;
                $voidContext['marketplace_order_id'] = isset($meta['marketplace_order_id'])
                    ? (int) $meta['marketplace_order_id']
                    : null;
            }

            // 2. Register or attend event: delete user from event_registrations
            $eventActivityNames = [
                PointsService::ACTIVITY_EVENT_JOIN,
                PointsService::ACTIVITY_EVENT_ATTEND,
                'member_attend_event',
            ];
            if (in_array($activityTypeName, $eventActivityNames, true)) {
                $eventId = $meta['event_id'] ?? $this->memberActivity->event_id;
                if ($eventId) {
                    $registration = EventRegistration::query()
                        ->where('user_id', $user->id)
                        ->where('event_id', $eventId)
                        ->first();

                    if ($registration) {
                        $voidContext['event_registration'] = [
                            'event_id' => $registration->event_id,
                            'type' => $registration->type,
                            'status' => $registration->status,
                            'registered_at' => optional($registration->registered_at)?->toDateTimeString(),
                            'attended_at' => optional($registration->attended_at)?->toDateTimeString(),
                            'notes' => $registration->notes,
                        ];
                        $registration->delete();
                    }

                    $voidContext['event_id'] = (int) $eventId;

                    Log::info('Activity voided: event registration removed', [
                        'member_activity_id' => $this->memberActivity->id,
                        'user_id' => $user->id,
                        'event_id' => $eventId,
                    ]);
                }
            }

            // 3. Claim or redeem merchant voucher: remove user from user_voucher
            $merchantVoucherActivityNames = [
                PointsService::ACTIVITY_VOUCHER_CLAIM,
                PointsService::ACTIVITY_VOUCHER_REDEEM,
            ];
            if (in_array($activityTypeName, $merchantVoucherActivityNames, true)) {
                $voucherId = $meta['voucher_id'] ?? null;
                if ($voucherId) {
                    $voidContext['voucher_id'] = (int) $voucherId;
                    $user->vouchers()->detach($voucherId);
                    Log::info('Activity voided: user_voucher pivot removed', [
                        'member_activity_id' => $this->memberActivity->id,
                        'user_id' => $user->id,
                        'voucher_id' => $voucherId,
                    ]);
                }
            }

            // 4. Claim or redeem admin voucher: void the assignment (refund + audit)
            $adminVoucherActivityNames = [
                PointsService::ACTIVITY_ADMIN_VOUCHER_CLAIM,
                PointsService::ACTIVITY_ADMIN_VOUCHER_REDEEM,
            ];
            if (in_array($activityTypeName, $adminVoucherActivityNames, true)) {
                $adminVoucherId = $meta['admin_voucher_id'] ?? null;
                if ($adminVoucherId) {
                    $adminVoucher = AdminVoucher::query()->find($adminVoucherId);
                    if ($adminVoucher) {
                        try {
                            $result = app(AdminVoucherVoidService::class)->voidForMember(
                                $adminVoucher,
                                $user,
                                $admin,
                                'Voided via member activity #'.$this->memberActivity->id.' (trx: '.$randomCode.')',
                            );
                            $voidContext['admin_voucher_id'] = (int) $adminVoucherId;
                            $voidContext['admin_voucher_previous_status'] = $result['previous_status'];
                            $voidContext['admin_voucher_points_refunded'] = $result['points_refunded'];
                        } catch (\Throwable $e) {
                            Log::info('Activity voided: admin voucher assignment already inactive', [
                                'member_activity_id' => $this->memberActivity->id,
                                'user_id' => $user->id,
                                'admin_voucher_id' => $adminVoucherId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }

            $metadata = array_merge($this->memberActivity->metadata ?? [], [
                'status' => 'void',
                'void_context' => $voidContext,
            ]);
            $this->memberActivity->update(['metadata' => $metadata]);
        });

        if ($pointsToDeduct > 0) {
            Log::info('Activity voided: points deducted from user wallet', [
                'admin_id' => auth()->id(),
                'admin_email' => auth()->user()?->email,
                'member_activity_id' => $this->memberActivity->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'points_deducted' => $pointsToDeduct,
                'user_total_points_after' => $user->fresh()->total_points,
                'voided_at' => now()->toIso8601String(),
                'trx_code' => $randomCode,
            ]);
        }

        $this->memberActivity->refresh();
        $this->messageText = 'Activity voided successfully...';
        $this->showMessage = true;
        $this->dispatch('activity-updated');
    }

    public function undoVoid(): void
    {
        $this->showMessage = false;
        $this->messageText = '';

        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to undo a voided activity.');

            return;
        }

        if (($this->memberActivity->metadata['status'] ?? null) !== 'void') {
            return;
        }

        if ($this->memberActivity->activityType?->name === PointsService::ACTIVITY_ADMIN_VOUCHER_VOID) {
            return;
        }

        $user = $this->memberActivity->user;
        $activityTypeName = $this->memberActivity->activityType?->name;
        $meta = $this->memberActivity->metadata ?? [];
        $voidContext = is_array($meta['void_context'] ?? null) ? $meta['void_context'] : [];
        $admin = auth()->user();
        $randomCode = Str::random(8);

        try {
            DB::transaction(function () use ($user, $activityTypeName, $meta, $voidContext, $admin, $randomCode) {
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                // 1. Restore points that were reversed on void (award activities)
                $pointsToRestore = (int) ($voidContext['points_reversed'] ?? 0);
                if ($pointsToRestore <= 0) {
                    $pointsToRestore = $this->inferReversedPoints();
                }

                if ($pointsToRestore > 0) {
                    $originalLog = PointLog::query()
                        ->where('member_activity_id', $this->memberActivity->id)
                        ->where('points', '>', 0)
                        ->orderBy('id')
                        ->first();

                    PointLog::query()->create([
                        'user_id' => $lockedUser->id,
                        'member_activity_id' => $this->memberActivity->id,
                        'point_system_config_id' => $originalLog?->point_system_config_id,
                        'activity_type_id' => $originalLog?->activity_type_id ?? $this->memberActivity->activity_type_id,
                        'location_id' => $originalLog?->location_id ?? $this->memberActivity->location_id,
                        'amenity_id' => $originalLog?->amenity_id ?? $this->memberActivity->amenity_id,
                        'points' => $pointsToRestore,
                        'description' => 'Points restored - void undone (Member Activity #'.$this->memberActivity->id.') - with trx code: '.$randomCode,
                        'awarded_at' => now(),
                    ]);
                    $lockedUser->increment('total_points', $pointsToRestore);
                }

                // 1b. Marketplace redeem: re-charge points and restore fulfilled order
                if ($activityTypeName === PointsService::ACTIVITY_MARKETPLACE_REDEEM) {
                    $this->undoMarketplaceRedeem($lockedUser, $meta, $voidContext, $admin, $randomCode);
                }

                // 2. Restore event registration
                $eventActivityNames = [
                    PointsService::ACTIVITY_EVENT_JOIN,
                    PointsService::ACTIVITY_EVENT_ATTEND,
                    'member_attend_event',
                ];
                if (in_array($activityTypeName, $eventActivityNames, true)) {
                    $this->restoreEventRegistration($lockedUser, $meta, $voidContext);
                }

                // 3. Restore merchant voucher attachment
                $merchantVoucherActivityNames = [
                    PointsService::ACTIVITY_VOUCHER_CLAIM,
                    PointsService::ACTIVITY_VOUCHER_REDEEM,
                ];
                if (in_array($activityTypeName, $merchantVoucherActivityNames, true)) {
                    $voucherId = $voidContext['voucher_id'] ?? $meta['voucher_id'] ?? null;
                    if ($voucherId && ! $lockedUser->vouchers()->where('vouchers.id', $voucherId)->exists()) {
                        $lockedUser->vouchers()->attach($voucherId);
                        Log::info('Activity void undone: user_voucher pivot restored', [
                            'member_activity_id' => $this->memberActivity->id,
                            'user_id' => $lockedUser->id,
                            'voucher_id' => $voucherId,
                        ]);
                    }
                }

                // 4. Restore admin voucher assignment (re-charge refunded points)
                $adminVoucherActivityNames = [
                    PointsService::ACTIVITY_ADMIN_VOUCHER_CLAIM,
                    PointsService::ACTIVITY_ADMIN_VOUCHER_REDEEM,
                ];
                if (in_array($activityTypeName, $adminVoucherActivityNames, true)) {
                    $this->undoAdminVoucherVoid($lockedUser, $meta, $voidContext, $admin, $randomCode);
                }

                $newMeta = $this->memberActivity->metadata ?? [];
                unset($newMeta['status'], $newMeta['void_context']);
                $newMeta['void_undone_at'] = now()->toIso8601String();
                $newMeta['void_undone_by'] = $admin->id;
                $newMeta['void_undone_trx'] = $randomCode;
                $this->memberActivity->update(['metadata' => $newMeta]);
            });
        } catch (\Throwable $e) {
            Log::warning('Activity void undo failed', [
                'member_activity_id' => $this->memberActivity->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', $e->getMessage());
            $this->dispatch('activity-updated');

            return;
        }

        Log::info('Activity void undone', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'member_activity_id' => $this->memberActivity->id,
            'user_id' => $user->id,
            'activity_type' => $activityTypeName,
            'user_total_points_after' => $user->fresh()->total_points,
            'trx_code' => $randomCode,
        ]);

        $this->memberActivity->refresh();
        $this->messageText = 'Void undone successfully...';
        $this->showMessage = true;
        $this->dispatch('activity-updated');
    }

    /**
     * Credit back marketplace spend and mark the linked fulfilled order as voided.
     *
     * @return int Points credited to the member
     */
    private function voidMarketplaceRedeem(
        User $user,
        ?PointLog $pointLog,
        array $meta,
        User $admin,
        string $randomCode,
    ): int {
        $orderId = isset($meta['marketplace_order_id']) ? (int) $meta['marketplace_order_id'] : null;
        $order = $orderId
            ? MarketplaceOrder::query()->whereKey($orderId)->first()
            : null;

        if ($order && $order->status === MarketplaceOrder::STATUS_FULFILLED) {
            $credited = (int) $order->points_total;
            $order->voidByAdmin(
                $admin,
                'Voided via member activity #'.$this->memberActivity->id.' (trx: '.$randomCode.')',
            );

            Log::info('Activity voided: marketplace order voided and points credited', [
                'admin_id' => $admin->id,
                'member_activity_id' => $this->memberActivity->id,
                'marketplace_order_id' => $order->id,
                'user_id' => $user->id,
                'points_credited' => $credited,
                'trx_code' => $randomCode,
            ]);

            return $credited;
        }

        // Fallback: no fulfilled order found — credit from the original spend log
        $pointsToCredit = 0;
        if ($pointLog && $pointLog->points < 0) {
            $pointsToCredit = abs((int) $pointLog->points);
        } elseif (! empty($meta['points_total'])) {
            $pointsToCredit = (int) $meta['points_total'];
        }

        if ($pointsToCredit > 0) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            app(PointsService::class)->creditPointsWithinTransaction(
                $lockedUser,
                $pointsToCredit,
                PointsService::ACTIVITY_MARKETPLACE_REFUND,
                'Refund for voided marketplace redeem (Member Activity #'.$this->memberActivity->id.') - trx: '.$randomCode,
                null,
                null,
            );

            Log::info('Activity voided: marketplace redeem points credited without order update', [
                'admin_id' => $admin->id,
                'member_activity_id' => $this->memberActivity->id,
                'marketplace_order_id' => $orderId,
                'order_status' => $order?->status,
                'user_id' => $user->id,
                'points_credited' => $pointsToCredit,
                'trx_code' => $randomCode,
            ]);
        }

        return $pointsToCredit;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $voidContext
     */
    private function undoMarketplaceRedeem(
        User $user,
        array $meta,
        array $voidContext,
        User $admin,
        string $randomCode,
    ): void {
        $orderId = $voidContext['marketplace_order_id']
            ?? (isset($meta['marketplace_order_id']) ? (int) $meta['marketplace_order_id'] : null);
        $order = $orderId
            ? MarketplaceOrder::query()->whereKey($orderId)->lockForUpdate()->first()
            : null;

        $pointsToDeduct = (int) ($voidContext['points_credited'] ?? 0);
        if ($pointsToDeduct <= 0 && $order) {
            $pointsToDeduct = (int) $order->points_total;
        }
        if ($pointsToDeduct <= 0 && ! empty($meta['points_total'])) {
            $pointsToDeduct = (int) $meta['points_total'];
        }

        if ($pointsToDeduct > 0) {
            if ((int) $user->total_points < $pointsToDeduct) {
                throw new \RuntimeException('Cannot undo void: member does not have enough points to re-charge the marketplace purchase.');
            }

            app(PointsService::class)->deductWithinTransaction(
                $user,
                $pointsToDeduct,
                PointsService::ACTIVITY_MARKETPLACE_REDEEM,
                'Points re-charged - void undone (Member Activity #'.$this->memberActivity->id.') - trx: '.$randomCode,
                null,
                null,
                $this->memberActivity->id,
            );
        }

        if ($order && $order->status === MarketplaceOrder::STATUS_VOIDED) {
            $order->update([
                'status' => MarketplaceOrder::STATUS_FULFILLED,
                'notes' => trim(($order->notes ? $order->notes.' | ' : '').'Void undone via member activity #'.$this->memberActivity->id.' (trx: '.$randomCode.')'),
                'fulfilled_by' => $admin->id,
            ]);
        }

        Log::info('Activity void undone: marketplace redeem restored', [
            'admin_id' => $admin->id,
            'member_activity_id' => $this->memberActivity->id,
            'marketplace_order_id' => $orderId,
            'user_id' => $user->id,
            'points_deducted' => $pointsToDeduct,
            'trx_code' => $randomCode,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $voidContext
     */
    private function restoreEventRegistration(User $user, array $meta, array $voidContext): void
    {
        $snapshot = is_array($voidContext['event_registration'] ?? null)
            ? $voidContext['event_registration']
            : null;

        $eventId = $snapshot['event_id']
            ?? $voidContext['event_id']
            ?? $meta['event_id']
            ?? $this->memberActivity->event_id;

        if (! $eventId) {
            return;
        }

        $defaults = [
            'type' => $snapshot['type'] ?? 'app',
            'status' => $snapshot['status'] ?? (
                in_array($this->memberActivity->activityType?->name, [
                    PointsService::ACTIVITY_EVENT_ATTEND,
                    'member_attend_event',
                ], true) ? 'attended' : 'registered'
            ),
            'registered_at' => $snapshot['registered_at'] ?? $this->memberActivity->activity_time ?? now(),
            'attended_at' => $snapshot['attended_at'] ?? (
                in_array($this->memberActivity->activityType?->name, [
                    PointsService::ACTIVITY_EVENT_ATTEND,
                    'member_attend_event',
                ], true) ? ($this->memberActivity->activity_time ?? now()) : null
            ),
            'notes' => $snapshot['notes'] ?? null,
        ];

        EventRegistration::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'event_id' => $eventId,
            ],
            $defaults,
        );

        Log::info('Activity void undone: event registration restored', [
            'member_activity_id' => $this->memberActivity->id,
            'user_id' => $user->id,
            'event_id' => $eventId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $voidContext
     */
    private function undoAdminVoucherVoid(
        User $user,
        array $meta,
        array $voidContext,
        User $admin,
        string $randomCode,
    ): void {
        $adminVoucherId = $voidContext['admin_voucher_id'] ?? $meta['admin_voucher_id'] ?? null;
        if (! $adminVoucherId) {
            return;
        }

        $adminVoucher = AdminVoucher::query()->find($adminVoucherId);
        if (! $adminVoucher) {
            return;
        }

        $previousStatus = $voidContext['admin_voucher_previous_status'] ?? null;
        $pointsRefunded = (int) ($voidContext['admin_voucher_points_refunded'] ?? 0);

        if (! $previousStatus || $pointsRefunded <= 0) {
            $voidAudit = MemberActivity::query()
                ->where('user_id', $user->id)
                ->whereHas('activityType', fn ($q) => $q->where('name', PointsService::ACTIVITY_ADMIN_VOUCHER_VOID))
                ->where(function ($q) use ($adminVoucherId) {
                    $q->where('metadata->admin_voucher_id', $adminVoucherId)
                        ->orWhere('metadata->admin_voucher_id', (string) $adminVoucherId);
                })
                ->latest('id')
                ->first();

            $previousStatus = $previousStatus ?: ($voidAudit?->metadata['previous_status'] ?? 'claimed');
            if ($pointsRefunded <= 0) {
                $pointsRefunded = (int) ($voidAudit?->metadata['points_refunded'] ?? 0);
            }
        }

        if (! in_array($previousStatus, ['claimed', 'redeemed'], true)) {
            $previousStatus = 'claimed';
        }

        $existing = $user->adminVouchers()
            ->where('admin_vouchers.id', $adminVoucher->id)
            ->first();

        $pivot = [
            'status' => $previousStatus,
            'voided_at' => null,
            'voided_by' => null,
            'void_reason' => null,
            'points_refunded' => 0,
        ];

        if ($existing) {
            $user->adminVouchers()->updateExistingPivot($adminVoucher->id, $pivot);
        } else {
            $user->adminVouchers()->attach($adminVoucher->id, array_merge([
                'claimed_at' => now(),
                'redeemed_at' => $previousStatus === 'redeemed' ? now() : null,
                'redeemed_at_merchant_id' => null,
            ], $pivot));
        }

        if ($previousStatus === 'redeemed') {
            $adminVoucher->increment('usage_count');
        }

        if ($pointsRefunded > 0) {
            if ((int) $user->total_points < $pointsRefunded) {
                throw new \RuntimeException('Cannot undo void: member does not have enough points to reverse the voucher refund.');
            }

            app(PointsService::class)->deductWithinTransaction(
                $user,
                $pointsRefunded,
                PointsService::ACTIVITY_ADMIN_VOUCHER_CLAIM,
                'Points re-charged - admin voucher void undone (Member Activity #'.$this->memberActivity->id.') - trx: '.$randomCode,
                null,
                null,
                $this->memberActivity->id,
            );
        }

        Log::info('Activity void undone: admin voucher assignment restored', [
            'admin_id' => $admin->id,
            'member_activity_id' => $this->memberActivity->id,
            'user_id' => $user->id,
            'admin_voucher_id' => $adminVoucher->id,
            'restored_status' => $previousStatus,
            'points_recharged' => $pointsRefunded,
            'trx_code' => $randomCode,
        ]);
    }

    private function inferReversedPoints(): int
    {
        $reversal = PointLog::query()
            ->where('member_activity_id', $this->memberActivity->id)
            ->where('points', '<', 0)
            ->where('description', 'like', '%activity voided%')
            ->orderByDesc('id')
            ->first();

        if ($reversal) {
            // If already undone once, a later restore log may exist after this reversal.
            $restoredAfter = PointLog::query()
                ->where('member_activity_id', $this->memberActivity->id)
                ->where('points', '>', 0)
                ->where('description', 'like', '%void undone%')
                ->where('id', '>', $reversal->id)
                ->exists();

            if (! $restoredAfter) {
                return abs((int) $reversal->points);
            }
        }

        return 0;
    }

    public function render()
    {
        return view('livewire.members.set-activity-void-button');
    }
}
