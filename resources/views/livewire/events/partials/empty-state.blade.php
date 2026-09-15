@if ($search !== '' || $statusFilter !== 'all' || (isset($locationFilter) && $locationFilter !== ''))
    {{ __('No events match your filters.') }}
@else
    {{ __('No events yet.') }}
    @isset($location)
        @can('event.create')
            <div class="mt-4">
                <a href="{{ route('admin.locations.events.create', $location->location_code) }}" class="inline-flex items-center gap-1 text-sm bg-orange-600 hover:bg-orange-700 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium hover:text-orange-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ __('Add event') }}</span>
                </a>
            </div>
        @endcan
    @else
        <div class="mt-4">
            <a href="{{ route('admin.locations.index') }}" class="inline-flex items-center gap-1 text-sm bg-slate-600 hover:bg-slate-700 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium hover:text-slate-100">
                <span>{{ __('View Locations') }}</span>
            </a>
        </div>
    @endisset
@endif
