<?php

namespace App\Livewire\Admin;

use App\Models\ActivityType;
use App\Models\MemberActivity;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EntryScansPerLocation extends Component
{
    private const ATTEND_EVENT_TYPE_ID = 7;

    private const VOUCHER_REDEMPTION_LABEL = 'Voucher redemption';

    private const CHART_COLORS = [
        ['bg' => 'rgba(59, 130, 246, 0.8)', 'border' => 'rgb(59, 130, 246)'],
        ['bg' => 'rgba(16, 185, 129, 0.8)', 'border' => 'rgb(16, 185, 129)'],
        ['bg' => 'rgba(245, 158, 11, 0.8)', 'border' => 'rgb(245, 158, 11)'],
        ['bg' => 'rgba(239, 68, 68, 0.8)', 'border' => 'rgb(239, 68, 68)'],
        ['bg' => 'rgba(139, 92, 246, 0.8)', 'border' => 'rgb(139, 92, 246)'],
        ['bg' => 'rgba(236, 72, 153, 0.8)', 'border' => 'rgb(236, 72, 153)'],
        ['bg' => 'rgba(20, 184, 166, 0.8)', 'border' => 'rgb(20, 184, 166)'],
        ['bg' => 'rgba(251, 146, 60, 0.8)', 'border' => 'rgb(251, 146, 60)'],
        ['bg' => 'rgba(99, 102, 241, 0.8)', 'border' => 'rgb(99, 102, 241)'],
        ['bg' => 'rgba(168, 85, 247, 0.8)', 'border' => 'rgb(168, 85, 247)'],
    ];

    public function placeholder()
    {
        return view('livewire.admin.partials.chart-placeholder', [
            'title' => 'Activity Types Over Time',
        ]);
    }

    public function getActivityTypesDataProperty(): array
    {
        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();

        $dateKeys = [];
        $labels = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dateKeys[] = $day->format('Y-m-d');
            $labels[] = $day->format('M d');
        }

        $countsByTypeAndDate = $this->aggregatedActivityCounts($startDate, $endDate);
        $voucherRedemptionCounts = $this->aggregatedVoucherRedemptionCounts($startDate, $endDate);

        $activityTypes = ActivityType::where('is_active', true)
            ->whereNotIn('name', [
                PointsService::ACTIVITY_VOUCHER_REDEEM,
                PointsService::ACTIVITY_ADMIN_VOUCHER_REDEEM,
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        $datasets = [];
        $colorIndex = 0;

        foreach ($activityTypes as $activityType) {
            $typeCounts = $countsByTypeAndDate[$activityType->id] ?? [];
            $data = array_map(
                fn (string $date) => (int) ($typeCounts[$date] ?? 0),
                $dateKeys
            );

            if (array_sum($data) === 0) {
                continue;
            }

            $color = self::CHART_COLORS[$colorIndex % count(self::CHART_COLORS)];
            $datasets[] = [
                'label' => $activityType->description ?: $activityType->name,
                'data' => $data,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'tension' => 0.4,
                'fill' => false,
            ];
            $colorIndex++;
        }

        $voucherData = array_map(
            fn (string $date) => (int) ($voucherRedemptionCounts[$date] ?? 0),
            $dateKeys
        );

        if (array_sum($voucherData) > 0) {
            $color = self::CHART_COLORS[$colorIndex % count(self::CHART_COLORS)];
            $datasets[] = [
                'label' => self::VOUCHER_REDEMPTION_LABEL,
                'data' => $voucherData,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'tension' => 0.4,
                'fill' => false,
            ];
        }

        if ($datasets === []) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    public function render()
    {
        return view('livewire.admin.entry-scans-per-location', [
            'activityTypesData' => $this->activityTypesData,
        ]);
    }

    /**
     * Aggregate daily counts in SQL. Non-attend rows are counted as-is;
     * member_attend_event rows are de-duplicated by user + event (latest wins).
     *
     * @return array<int, array<string, int>>
     */
    protected function aggregatedActivityCounts($startDate, $endDate): array
    {
        $nonEventCounts = MemberActivity::query()
            ->whereBetween('activity_time', [$startDate, $endDate])
            ->where('activity_type_id', '!=', self::ATTEND_EVENT_TYPE_ID)
            ->selectRaw('DATE(activity_time) as date, activity_type_id, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(activity_time)'), 'activity_type_id')
            ->get();

        $eventCounts = MemberActivity::query()
            ->whereBetween('activity_time', [$startDate, $endDate])
            ->where('activity_type_id', self::ATTEND_EVENT_TYPE_ID)
            ->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('member_activities as newer')
                    ->whereColumn('newer.activity_type_id', 'member_activities.activity_type_id')
                    ->whereColumn('newer.user_id', 'member_activities.user_id')
                    ->whereRaw(
                        "COALESCE(newer.event_id, JSON_UNQUOTE(JSON_EXTRACT(newer.metadata, '$.event_id')), 'null') = COALESCE(member_activities.event_id, JSON_UNQUOTE(JSON_EXTRACT(member_activities.metadata, '$.event_id')), 'null')"
                    )
                    ->where(function ($newerRowQuery) {
                        $newerRowQuery->whereColumn('newer.activity_time', '>', 'member_activities.activity_time')
                            ->orWhere(function ($sameTimeQuery) {
                                $sameTimeQuery->whereColumn('newer.activity_time', 'member_activities.activity_time')
                                    ->whereColumn('newer.id', '>', 'member_activities.id');
                            });
                    });
            })
            ->selectRaw('DATE(activity_time) as date, activity_type_id, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(activity_time)'), 'activity_type_id')
            ->get();

        $result = [];

        foreach ($nonEventCounts->concat($eventCounts) as $row) {
            $typeId = (int) $row->activity_type_id;
            $date = (string) $row->date;
            $result[$typeId][$date] = (int) $row->count;
        }

        return $result;
    }

    /**
     * Daily merchant + admin voucher redemptions. Pivot tables remain the source of
     * truth so the dedicated "Voucher redemption" series is not double-counted when
     * redeem flows also write member_activities rows.
     *
     * @return array<string, int>
     */
    protected function aggregatedVoucherRedemptionCounts($startDate, $endDate): array
    {
        $merchantCounts = DB::table('user_voucher')
            ->where('status', 'redeemed')
            ->whereNotNull('redeemed_at')
            ->whereBetween('redeemed_at', [$startDate, $endDate])
            ->selectRaw('DATE(redeemed_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(redeemed_at)'))
            ->get();

        $adminCounts = DB::table('user_admin_voucher')
            ->where('status', 'redeemed')
            ->whereNotNull('redeemed_at')
            ->whereBetween('redeemed_at', [$startDate, $endDate])
            ->selectRaw('DATE(redeemed_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(redeemed_at)'))
            ->get();

        $result = [];

        foreach ($merchantCounts->concat($adminCounts) as $row) {
            $date = (string) $row->date;
            $result[$date] = ($result[$date] ?? 0) + (int) $row->count;
        }

        return $result;
    }
}
