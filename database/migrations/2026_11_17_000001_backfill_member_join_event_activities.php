<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create member_activities rows for historical member_join_event point awards
     * so they appear on member activity timelines. Does not change total_points.
     */
    public function up(): void
    {
        $joinActivityTypeId = DB::table('activity_types')
            ->where('name', 'member_join_event')
            ->value('id');

        if (! $joinActivityTypeId) {
            return;
        }

        $orphanLogs = DB::table('point_logs')
            ->where('activity_type_id', $joinActivityTypeId)
            ->whereNull('member_activity_id')
            ->orderBy('id')
            ->get(['id', 'user_id', 'location_id', 'points', 'description', 'awarded_at', 'created_at']);

        foreach ($orphanLogs as $log) {
            $registration = DB::table('event_registrations')
                ->where('user_id', $log->user_id)
                ->whereNotNull('registered_at')
                ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, registered_at, ?)) <= 60', [$log->awarded_at])
                ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, registered_at, ?))', [$log->awarded_at])
                ->first();

            if (! $registration) {
                continue;
            }

            $existingActivityId = DB::table('member_activities')
                ->where('user_id', $log->user_id)
                ->where('activity_type_id', $joinActivityTypeId)
                ->where('event_id', $registration->event_id)
                ->value('id');

            if ($existingActivityId) {
                DB::table('point_logs')
                    ->where('id', $log->id)
                    ->whereNull('member_activity_id')
                    ->update(['member_activity_id' => $existingActivityId]);

                continue;
            }

            $event = DB::table('events')->where('id', $registration->event_id)->first();
            $user = DB::table('users')->where('id', $log->user_id)->first();

            $activityId = DB::table('member_activities')->insertGetId([
                'user_id' => $log->user_id,
                'activity_type_id' => $joinActivityTypeId,
                'location_id' => $log->location_id ?? $event?->location_id,
                'amenity_id' => null,
                'event_id' => $registration->event_id,
                'activity_time' => $log->awarded_at ?? $log->created_at,
                'description' => $event
                    ? 'Member joined event: '.$event->title
                    : ($log->description ?: 'Member joined event'),
                'metadata' => json_encode([
                    'event_id' => $registration->event_id,
                    'event_code' => $event?->event_code,
                    'event_title' => $event?->title,
                    'qr_code' => $user->qr_code ?? null,
                    'access_type' => $registration->type ?? 'app',
                    'backfilled' => true,
                ]),
                'created_at' => $log->created_at ?? now(),
                'updated_at' => now(),
            ]);

            DB::table('point_logs')
                ->where('id', $log->id)
                ->update(['member_activity_id' => $activityId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $joinActivityTypeId = DB::table('activity_types')
            ->where('name', 'member_join_event')
            ->value('id');

        if (! $joinActivityTypeId) {
            return;
        }

        $activityIds = DB::table('member_activities')
            ->where('activity_type_id', $joinActivityTypeId)
            ->where('metadata->backfilled', true)
            ->pluck('id');

        if ($activityIds->isEmpty()) {
            return;
        }

        DB::table('point_logs')
            ->whereIn('member_activity_id', $activityIds)
            ->update(['member_activity_id' => null]);

        DB::table('member_activities')
            ->whereIn('id', $activityIds)
            ->delete();
    }
};
