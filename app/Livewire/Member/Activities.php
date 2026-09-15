<?php

namespace App\Livewire\Member;

use App\Models\MemberActivity;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class Activities extends Component
{
    public const PAGE_SIZE = 15;

    public string $search = '';

    public string $dateFilter = 'all'; // all | today | week | month

    public int $perPage = self::PAGE_SIZE;

    public function loadMore(): void
    {
        $this->perPage += self::PAGE_SIZE;
    }

    public function updatingSearch(): void
    {
        $this->perPage = self::PAGE_SIZE;
    }

    public function updatingDateFilter(): void
    {
        $this->perPage = self::PAGE_SIZE;
    }

    public function setDateFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'today', 'week', 'month'], true)) {
            return;
        }

        $this->dateFilter = $filter;
        $this->perPage = self::PAGE_SIZE;
    }

    public function dateHeading(string $date): string
    {
        $day = Carbon::parse($date)->startOfDay();

        if ($day->isToday()) {
            return 'Today';
        }

        if ($day->isYesterday()) {
            return 'Yesterday';
        }

        if ($day->isCurrentYear()) {
            return $day->format('l, M j');
        }

        return $day->format('M j, Y');
    }

    public function render(): View
    {
        $query = $this->activitiesQuery();

        $totalCount = $query->clone()->count();

        $activities = $query->clone()
            ->orderByDesc('activity_time')
            ->orderByDesc('id')
            ->limit($this->perPage)
            ->get();

        /** @var Collection<string, Collection<int, MemberActivity>> $groupedActivities */
        $groupedActivities = $activities->groupBy(
            fn (MemberActivity $activity) => $activity->activity_time->toDateString()
        );

        return view('livewire.member.activities', [
            'groupedActivities' => $groupedActivities,
            'loadedCount' => $activities->count(),
            'totalCount' => $totalCount,
            'hasMore' => $activities->count() < $totalCount,
        ])->layout('layouts.app', [
            'title' => 'My Activities',
        ]);
    }

    private function activitiesQuery(): Builder
    {
        $query = MemberActivity::query()
            ->where('user_id', auth()->id())
            ->with(['activityType', 'location', 'amenity', 'event', 'pointLog']);

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', $s)
                    ->orWhere('metadata->voucher_code', 'like', $s)
                    ->orWhereHas('activityType', function ($typeQuery) use ($s) {
                        $typeQuery->where('name', 'like', $s)
                            ->orWhere('description', 'like', $s);
                    })
                    ->orWhereHas('location', function ($locQuery) use ($s) {
                        $locQuery->where('name', 'like', $s);
                    })
                    ->orWhereHas('amenity', function ($amenityQuery) use ($s) {
                        $amenityQuery->where('name', 'like', $s);
                    })
                    ->orWhereHas('event', function ($eventQuery) use ($s) {
                        $eventQuery->where('title', 'like', $s);
                    });
            });
        }

        if ($this->dateFilter === 'today') {
            $query->whereDate('activity_time', today());
        } elseif ($this->dateFilter === 'week') {
            $query->where('activity_time', '>=', now()->subWeek());
        } elseif ($this->dateFilter === 'month') {
            $query->where('activity_time', '>=', now()->subMonth());
        }

        return $query;
    }
}
