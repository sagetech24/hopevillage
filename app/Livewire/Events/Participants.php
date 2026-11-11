<?php

namespace App\Livewire\Events;

use App\Models\ActivityType;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MemberActivity;
use App\Services\PointsService;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Livewire\WithPagination;

class Participants extends Component
{
    use WithPagination;

    public $eventCode;
    public $event;
    public $search = '';
    public $statusFilter = 'attended';
    public $sortField = 'attended_at';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'tailwind';

    public function mount($event_code)
    {
        $this->eventCode = $event_code;
        $this->loadEvent();
    }

    public function loadEvent()
    {
        $this->event = Event::where('event_code', $this->eventCode)->firstOrFail();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function sortByAccessType()
    {
        $this->toggleSort('access_type');
    }

    public function sortByAttendedDate()
    {
        $this->toggleSort('attended_at');
    }

    private function toggleSort(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'attended_at' ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function exportCsv()
    {
        $attendedRegistrations = EventRegistration::query()
            ->where('event_id', $this->event->id)
            ->where('status', 'attended')
            ->with('user')
            ->orderByDesc('attended_at')
            ->get();

        $accessTypesByUserId = $this->accessTypesByUserId(
            $attendedRegistrations->pluck('user_id')->all()
        );

        $filename = 'event-attendees-' . ($this->event->event_code ?? $this->event->id) . '-' . now()->format('Y-m-d-His') . '.csv';

        return Response::streamDownload(function () use ($attendedRegistrations, $accessTypesByUserId) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'name',
                'whatsapp_number',
                'event_name',
                'access_type',
                'status',
                'date_time',
            ]);

            foreach ($attendedRegistrations as $registration) {
                fputcsv($handle, [
                    $registration->user?->name ?? '',
                    $registration->user?->whatsapp_number ?? '',
                    $this->event->title ?? '',
                    $accessTypesByUserId[$registration->user_id] ?? '',
                    $registration->status ?? '',
                    $registration->attended_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function render()
    {
        $attendActivityTypeId = ActivityType::query()
            ->where('name', PointsService::ACTIVITY_EVENT_ATTEND)
            ->value('id');

        $query = EventRegistration::query()
            ->where('event_registrations.event_id', $this->event->id)
            ->where('event_registrations.status', 'attended')
            ->with('user');

        // Apply search filter
        if ($this->search !== '') {
            $searchTerm = '%' . $this->search . '%';
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('fin', 'like', $searchTerm);
            });
        }

        // Apply status filter
        // if ($this->statusFilter !== 'all') {
        //     $query->where('status', $this->statusFilter);
        // }

        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        if ($this->sortField === 'access_type' && $attendActivityTypeId) {
            $query->leftJoin('member_activities', function ($join) use ($attendActivityTypeId) {
                $join->on('member_activities.user_id', '=', 'event_registrations.user_id')
                    ->on('member_activities.event_id', '=', 'event_registrations.event_id')
                    ->where('member_activities.activity_type_id', '=', $attendActivityTypeId);
            })
                ->select('event_registrations.*')
                ->orderByRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(member_activities.metadata, '$.access_type')) {$sortDirection}"
                )
                ->orderByDesc('event_registrations.attended_at');
        } else {
            $query->orderBy('event_registrations.attended_at', $sortDirection);
        }

        $registrations = $query->paginate(10);

        $accessTypesByUserId = $this->accessTypesByUserId(
            $registrations->getCollection()->pluck('user_id')->all(),
            $attendActivityTypeId
        );

        $registrations->getCollection()->transform(function (EventRegistration $registration) use ($accessTypesByUserId) {
            $registration->access_type = $accessTypesByUserId[$registration->user_id] ?? null;

            return $registration;
        });

        return view('livewire.events.participants', [
            'registrations' => $registrations,
            'event' => $this->event,
        ]);
    }

    /**
     * @param  array<int, int|string|null>  $userIds
     * @return array<int, string>
     */
    private function accessTypesByUserId(array $userIds, int|string|null $attendActivityTypeId = null): array
    {
        $userIds = array_values(array_filter($userIds));

        if ($userIds === []) {
            return [];
        }

        $attendActivityTypeId ??= ActivityType::query()
            ->where('name', PointsService::ACTIVITY_EVENT_ATTEND)
            ->value('id');

        if (! $attendActivityTypeId) {
            return [];
        }

        return MemberActivity::query()
            ->where('event_id', $this->event->id)
            ->where('activity_type_id', $attendActivityTypeId)
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'metadata'])
            ->mapWithKeys(function (MemberActivity $activity) {
                $accessType = data_get($activity->metadata, 'access_type');

                return $accessType
                    ? [(int) $activity->user_id => (string) $accessType]
                    : [];
            })
            ->all();
    }
}
