<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;

class WeeklyRegistrationTraffic extends Component
{
    public string $range = '30';

    public function placeholder()
    {
        return view('livewire.admin.partials.chart-placeholder', [
            'title' => 'Membership Signups vs Event Days',
        ]);
    }

    public function updatedRange(): void
    {
        if (! in_array($this->range, ['30', '90', '365'], true)) {
            $this->range = '30';
        }
    }

    public function getSignupTrendDataProperty(): array
    {
        $days = (int) $this->range;
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $registrations = User::where('user_type', 'member')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $eventDateSet = $this->getEventDateSet($startDate, $endDate);
        $labels = [];
        $data = [];
        $eventDayFlags = [];

        $eventDaySignups = 0;
        $regularDaySignups = 0;
        $eventDayCount = 0;
        $regularDayCount = 0;

        /** @var Carbon $date */
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dateKey = $date->format('Y-m-d');
            $count = (int) ($registrations[$dateKey] ?? 0);
            $isEventDay = isset($eventDateSet[$dateKey]);

            $labels[] = $date->format('M j');
            $data[] = $count;
            $eventDayFlags[] = $isEventDay;

            if ($isEventDay) {
                $eventDayCount++;
                $eventDaySignups += $count;
            } else {
                $regularDayCount++;
                $regularDaySignups += $count;
            }
        }

        $eventRate = $eventDayCount > 0 ? round($eventDaySignups / $eventDayCount, 2) : 0.0;
        $regularRate = $regularDayCount > 0 ? round($regularDaySignups / $regularDayCount, 2) : 0.0;
        $uplift = ($eventDayCount > 0 && $regularRate > 0)
            ? round((($eventRate - $regularRate) / $regularRate) * 100, 2)
            : null;

        return [
            'labels' => $labels,
            'data' => $data,
            'eventDayFlags' => $eventDayFlags,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'eventRate' => $eventRate,
            'regularRate' => $regularRate,
            'eventDayCount' => $eventDayCount,
            'regularDayCount' => $regularDayCount,
            'eventDaySignups' => $eventDaySignups,
            'regularDaySignups' => $regularDaySignups,
            'upliftPercent' => $uplift,
        ];
    }

    public function render()
    {
        return view('livewire.admin.weekly-registration-traffic', [
            'signupTrendData' => $this->signupTrendData,
        ]);
    }

    /**
     * @return array<string, true>
     */
    protected function getEventDateSet(Carbon $startDate, Carbon $endDate): array
    {
        $events = Event::query()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($nested) use ($startDate, $endDate) {
                        $nested->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get(['start_date', 'end_date']);

        $eventDateSet = [];

        foreach ($events as $event) {
            $eventStart = $event->start_date->copy()->startOfDay();
            $eventEnd = $event->end_date->copy()->startOfDay();

            foreach (CarbonPeriod::create($eventStart, $eventEnd) as $eventDate) {
                $dateKey = $eventDate->format('Y-m-d');
                if ($eventDate->betweenIncluded($startDate, $endDate)) {
                    $eventDateSet[$dateKey] = true;
                }
            }
        }

        return $eventDateSet;
    }
}
