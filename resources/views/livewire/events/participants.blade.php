<div>
    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 mt-12">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div class="flex flex-col gap-1">
                <h3 class="text-xl font-semibold text-gray-800">Recorded Participants</h3>
                <span class="text-sm text-gray-500">
                    Total Attendance:
                    <span class="text-lg font-bold text-gray-900">
                        {{ $registrations->total() }}
                    </span>
                </span>
            </div>
            {{-- i want to add a feature where attended participants can be exported as CSV --}}
            <button
                wire:click="exportCsv"
                class="flex items-center text-xs cursor-pointer gap-1 px-4 py-2 bg-orange-500 text-white rounded-full hover:bg-orange-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export as CSV
            </button>
        </div>

        <!-- Search and Filter -->
        <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search participants (name, email, QR Code)..."
                        class="w-full px-4 py-2 border text-gray-700 border-gray-500 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    >
                </div>
                <div>
                    <select
                        wire:model.live="statusFilter"
                        class="w-full px-4 py-2 border text-gray-700 border-gray-500 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                    >
                        <option value="all">All</option>
                        {{-- <option value="pending">Pending</option> --}}
                        <option value="registered" disabled>Registered Only</option>
                        <option value="attended">Attended</option>
                        {{-- <option value="cancelled">Cancelled</option> --}}
                        {{-- <option value="no_show">No Show</option> --}}
                    </select>
                </div>
            </div>
        </div>

        @if($registrations->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Member
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button
                                    type="button"
                                    wire:click="sortByAccessType"
                                    class="flex items-center gap-1 hover:text-gray-700 transition-colors"
                                    title="Sort by Access Type"
                                >
                                    Access Type
                                    @if($sortField === 'access_type' && $sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button
                                    type="button"
                                    wire:click="sortByAttendedDate"
                                    class="flex items-center gap-1 hover:text-gray-700 transition-colors"
                                    title="Sort by Attended Date"
                                >
                                    Attended Date
                                    @if($sortField === 'attended_at' && $sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($registrations as $registration)
                            @php
                                $statusClass = match($registration->status) {
                                    'attended' => 'bg-green-100 border border-green-500 text-green-800',
                                    'cancelled' => 'bg-red-100 border border-red-500 text-red-800',
                                    'no_show' => 'bg-gray-100 border border-gray-500 text-gray-800',
                                    'pending' => 'bg-yellow-100 border border-yellow-500 text-yellow-800',
                                    default => 'bg-blue-100 border border-blue-500 text-blue-800',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.members.profile', $registration->user->qr_code) }}" class="text-lg font-semibold text-orange-500 capitalize hover:text-orange-600">
                                            {{ $registration->user->name ?? 'N/A' }}
                                        </a>
                                        @if($registration->user)
                                            <div class="text-xs text-gray-500 flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                                </svg>
                                                {{ $registration->user->email ?? '' }}
                                            </div>
                                            @if($registration->user->whatsapp_number)
                                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                                    </svg>
                                                    {{ $registration->user->whatsapp_number ?? '' }}
                                                </div>
                                            @endif
                                            {{-- @if($registration->user->qr_code)
                                                <div class="text-xs text-gray-400 mt-1">
                                                    QR Code: {{ $registration->user->qr_code }}
                                                </div>
                                            @endif --}}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($registration->access_type)
                                        <span class="text-gray-700 font-medium capitalize">
                                            {{ str_replace('_', ' ', $registration->access_type) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-900">
                                    @if($registration->attended_at)
                                        <div class="flex flex-col gap-1">
                                            <span class="text-gray-600 font-medium text-md">
                                                {{ $registration->attended_at->format('d M Y') }}
                                            </span>
                                            <div class="text-gray-400 text-sm">
                                                {{ $registration->attended_at->format('h:i A') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $registration->status)) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $registrations->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 text-gray-400 mx-auto mb-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <p class="text-sm text-gray-500">
                    @if($search || $statusFilter !== 'all')
                        No registrations found matching your filters.
                    @else
                        No registrations yet
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
