<div class="card bg-white shadow border border-gray-300">
    <div class="card-body">
        <h2 class="card-title text-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Recent Activity
        </h2>
        <div class="space-y-3">
            @forelse($recentActivities as $activity)
                <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50">
                    <div class="shrink-0">
                        <div class="avatar placeholder">
                            <div class="bg-gray-800 text-white rounded-full w-10 flex items-center justify-center">
                                {{-- helper for the first letter of the user's name --}}
                                <span class="text-md font-bold">{{ substr($activity->user->name ?? 'U', 0, 1) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 font-bold">{{ $activity->user->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-600">{{ $activity->activityType->name ?? 'Activity' }}</p>
                        <p class="text-xs text-gray-600 mt-1">
                            {{ $activity->location?->name ?? 'N/A' }} 
                        </p>
                        <p class="text-gray-500 italic text-xs">{{ $activity->activity_time->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-600 py-4">No recent activity</p>
            @endforelse
        </div>
    </div>
</div>
