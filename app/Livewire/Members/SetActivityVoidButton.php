<?php

namespace App\Livewire\Members;

use App\Models\EventRegistration;
use App\Models\MarketplaceOrder;
use App\Models\MemberActivity;
use App\Models\PointLog;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class SetActivityVoidButton extends Component
{
    public MemberActivity $memberActivity;

    public $showMessage = false;

    public function setAsVoid(): void
    {
        $this->showMessage = false;

        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to set activity as void.');

            return;
        }

        if (($this->memberActivity->metadata['status'] ?? null) === 'void') {
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
            $metadata = array_merge($this->memberActivity->metadata ?? [], ['status' => 'void']);
            $this->memberActivity->update(['metadata' => $metadata]);

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
                $this->voidMarketplaceRedeem($user, $pointLog, $meta, $admin, $randomCode);
            }

            // 2. Register or attend event: delete user from event_registrations
            $eventActivityNames = [
                PointsService::ACTIVITY_EVENT_JOIN,
                PointsService::ACTIVITY_EVENT_ATTEND,
                'member_attend_event',
            ];
            if (in_array($activityTypeName, $eventActivityNames, true)) {
                $eventId = $meta['event_id'] ?? null;
                if ($eventId) {
                    EventRegistration::query()
                        ->where('user_id', $user->id)
                        ->where('event_id', $eventId)
                        ->delete();
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
                    $user->vouchers()->detach($voucherId);
                    Log::info('Activity voided: user_voucher pivot removed', [
                        'member_activity_id' => $this->memberActivity->id,
                        'user_id' => $user->id,
                        'voucher_id' => $voucherId,
                    ]);
                }
            }

            // 4. Claim or redeem admin voucher: remove user from user_admin_voucher
            $adminVoucherActivityNames = [
                PointsService::ACTIVITY_ADMIN_VOUCHER_CLAIM,
            ];
            if (in_array($activityTypeName, $adminVoucherActivityNames, true)) {
                $adminVoucherId = $meta['admin_voucher_id'] ?? null;
                if ($adminVoucherId) {
                    $user->adminVouchers()->detach($adminVoucherId);
                    Log::info('Activity voided: user_admin_voucher pivot removed', [
                        'member_activity_id' => $this->memberActivity->id,
                        'user_id' => $user->id,
                        'admin_voucher_id' => $adminVoucherId,
                    ]);
                }
            }
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
        $this->showMessage = true;
        $this->dispatch('activity-updated');
    }

    /**
     * Credit back marketplace spend and mark the linked fulfilled order as voided.
     */
    private function voidMarketplaceRedeem(
        User $user,
        ?PointLog $pointLog,
        array $meta,
        User $admin,
        string $randomCode,
    ): void {
        $orderId = isset($meta['marketplace_order_id']) ? (int) $meta['marketplace_order_id'] : null;
        $order = $orderId
            ? MarketplaceOrder::query()->whereKey($orderId)->first()
            : null;

        if ($order && $order->status === MarketplaceOrder::STATUS_FULFILLED) {
            $order->voidByAdmin(
                $admin,
                'Voided via member activity #'.$this->memberActivity->id.' (trx: '.$randomCode.')',
            );

            Log::info('Activity voided: marketplace order voided and points credited', [
                'admin_id' => $admin->id,
                'member_activity_id' => $this->memberActivity->id,
                'marketplace_order_id' => $order->id,
                'user_id' => $user->id,
                'points_credited' => (int) $order->points_total,
                'trx_code' => $randomCode,
            ]);

            return;
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
    }

    public function render()
    {
        return view('livewire.members.set-activity-void-button');
    }
}
