<div>
    <x-slot name="header">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ __('Admin Vouchers Management') }}
                </h2>
                <a href="{{ route('admin.admin-vouchers.create') }}" class="md:block hidden text-orange-500 font-medium hover:text-orange-700 hover:scale-105 transition-all duration-300">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create New Voucher
                    </span>
                </a>
                <a href="{{ route('admin.admin-vouchers.create') }}" class="md:hidden block bg-orange-500 hover:bg-orange-600 hover:scale-105 text-white p-2 rounded-full transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session()->has('message'))
                <div 
                    x-data="{ 
                        show: @entangle('showMessage').live,
                        timeoutId: null
                    }"
                    x-init="
                        $watch('show', value => {
                            if (value && !timeoutId) {
                                timeoutId = setTimeout(() => {
                                    show = false;
                                    timeoutId = null;
                                }, 3000);
                            } else if (!value && timeoutId) {
                                clearTimeout(timeoutId);
                                timeoutId = null;
                            }
                        });
                        if (show) {
                            timeoutId = setTimeout(() => {
                                show = false;
                                timeoutId = null;
                            }, 3000);
                        }
                    "
                    x-show="show"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-out duration-500"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" 
                    role="alert"
                >
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            <!-- Search and Filter -->
            <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 md:mx-0 mx-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Search admin vouchers..." 
                            class="w-full px-4 py-2 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                        >
                    </div>
                    <div class="md:col-span-1">
                        <select 
                            wire:model.live="statusFilter" 
                            class="w-full px-4 py-2 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                        >
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:px-0 px-4">
                @forelse($adminVouchers as $adminVoucher)
                    <div class="w-full bg-white hover:bg-gray-50 overflow-hidden border-2 border-gray-300 flex flex-col rounded-lg group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                        <div class="relative w-full flex md:flex-row flex-col md:justify-between justify-start items-center">
                            {{-- create a label position on the top-left corner stating if it Expired, Full, or On Going --}}
                            @php
                                $statusReason = $adminVoucher->getStatusReason();
                                $isExpired = $statusReason === 'Expired';
                                $isFull = $adminVoucher->usage_limit && $adminVoucher->usage_count >= $adminVoucher->usage_limit;

                                $validFrom = $adminVoucher->valid_from;
                                $validUntil = $adminVoucher->valid_until;
                                $validDays = (int) round($validFrom->diffInDays($validUntil));

                                $isValidityExpired = $validUntil && now()->gt($validUntil);
                                
                                if ($isExpired || $isValidityExpired) {
                                    $statusLabel = 'Expired';
                                    $statusBg = 'bg-red-500';
                                } elseif ($isFull) {
                                    $statusLabel = 'Full';
                                    $statusBg = 'bg-orange-500';
                                } else {
                                    $statusLabel = 'On Going';
                                    $statusBg = 'bg-green-500';
                                }
                            @endphp

                            <div class="absolute top-2 left-0 z-10">
                                <span class="text-xs text-white {{ $statusBg }} px-2 py-1 drop-shadow-lg shadow uppercase rounded-br-lg rounded-tr-lg">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                            <div class="flex md:flex-row flex-col items-start h-full">
                                <div class="w-full md:w-48 flex items-start justify-center md:mr-2 mr-0 overflow-hidden bg-white group-hover:border-orange-500 transition-all duration-300">
                                    @php
                                        $imageUrl = $adminVoucher->getFirstMediaUrl('image');
                                    @endphp
                                    @if($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $adminVoucher->name }}" class="w-full md:h-24 h-40 object-fit bg-center bg-cover mt-4 md:ml-8 ml-4">
                                    @else
                                        <div class="w-full md:h-24 h-40 mt-4 md:ml-8 ml-4 bg-gray-200 flex items-center justify-center border border-gray-300">
                                            <p class="text-gray-400 text-sm">No Image</p>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex md:flex-row flex-col w-full items-start gap-2 p-4 justify-between">
                                    <div class="flex flex-col">
                                        <h3 class="md:text-xl text-2xl font-bold text-gray-600 line-clamp-1">{{ $adminVoucher->name }}</h3>
                                        <span class="flex items-center gap-1 text-gray-600 bg-transparent text-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                            <span class="text-xs">{{ $adminVoucher->voucher_code }}</span>
                                        </span>
                                        <p class="text-sm text-gray-600 my-1">
                                            <span class="text-sm text-gray-600">Description:</span>
                                            {{ $adminVoucher->description ? $adminVoucher->description : 'No description available' }}
                                        </p>
                                        {{-- add the valid from and valid until --}}

                                        @if($adminVoucher->valid_from || $adminVoucher->valid_until)
                                            <div class="flex flex-col mt-1">
                                                <span class="text-sm text-gray-600">
                                                    Validity Period:
                                                    @if($isExpired || $isValidityExpired)
                                                        <span class="text-sm text-red-600 font-semibold">Expired</span>
                                                    @else
                                                        @if($validDays < 1)
                                                            @if($validDays !== null)
                                                                @if($validDays < 1)
                                                                <span class="text-sm text-yellow-600 font-semibold">
                                                                    Within the day only
                                                                </span>
                                                                @else
                                                                    <span class="text-sm text-green-700 font-semibold">
                                                                        {{ $validDays }} days
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        @else
                                                             <span class="text-sm text-green-700 font-semibold">
                                                                {{ $validDays > 1 ? $validDays . ' days' : $validDays . ' day' }}
                                                            </span>       
                                                        @endif
                                                    @endif
                                                </span>
                                                {{-- place the number of days valid based on the valid date difference in days --}}
                                                <span class="text-xs {{ $isValidityExpired ? 'text-red-600' : 'text-gray-600' }}">
                                                    {{ $adminVoucher->valid_from ? $adminVoucher->valid_from->format('d M Y g:i A') : 'N/A' }}
                                                    {{ $adminVoucher->valid_from ? ' - ' : '' }}
                                                    {{ $adminVoucher->valid_until ? $adminVoucher->valid_until->format('d M Y g:i A') : 'N/A' }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="flex flex-col gap-1 mt-2">
                                            <span class="text-xs text-gray-600">Allowed Merchants:</span>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($adminVoucher->merchants as $merchant)
                                                    <span class="flex gap-1">
                                                        <svg class="size-3 stroke-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                                        </svg>
                                                        <span class="text-xs text-gray-600">
                                                            {{$merchant->name }}{{ !$loop->last ? ',' : '' }}
                                                        </span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="pt-4 flex items-start justify-start gap-2">
                                            <a 
                                                href="{{ route('admin.admin-vouchers.profile', $adminVoucher->voucher_code) }}"
                                                title="View Profile"
                                                class="rounded-full p-3 flex items-center hover:scale-110 justify-center bg-yellow-500 hover:bg-yellow-600 text-white transition-all duration-300">
                                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
    
                                            </a>
                                            <button 
                                                wire:click="edit('{{ $adminVoucher->voucher_code }}')"
                                                title="Edit Admin Voucher"
                                                class="rounded-full p-3 flex items-center hover:scale-110 justify-center bg-blue-500 hover:bg-sky-600 text-white transition-all duration-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                </svg>
                                            </button>
                                            <button 
                                                wire:confirm="Are you sure you want to delete this admin voucher?" 
                                                title="Delete Admin Voucher"
                                                wire:click="delete('{{ $adminVoucher->voucher_code }}')"
                                                class="rounded-full p-3 flex items-center hover:scale-110 justify-center bg-red-400 hover:bg-red-500 text-white transition-all duration-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- add card footer with the date created and the date updated --}}
                        <div class="grid grid-cols-4 gap-2 border-t justify-between items-baseline border-gray-300 p-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-gray-600 line-clamp-1">Required Points:</span>
                                <span class="flex md:justify-start justify-center items-center gap-1 text-gray-600 bg-orange-200 rounded-lg py-1 md:px-2 px-1 text-xs font-semibold">
                                    <span class="flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                        </svg>
                                    </span>
                                    <span class="text-sm">{{ number_format($adminVoucher->points_cost) }}</span>
                                    <span class="text-xs line-clamp-1">Points</span>
                                </span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-gray-600 line-clamp-1">Amount Cost:</span>
                                <span class="flex md:justify-start justify-center items-center gap-1 text-gray-600 bg-teal-200 rounded-lg py-1 md:px-2 px-1 text-xs font-semibold">
                                    <span class="flex items-center justify-center">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                        </svg>
                                    </span>
                                    <span class="text-sm">{{ '$'.number_format($adminVoucher->amount_cost, 2) }}</span>
                                </span>
                            </div>
                            @if($adminVoucher->usage_limit)
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-gray-600">Usage limit:</span>
                                    <div class="flex md:justify-start justify-center items-center gap-1 text-gray-600 bg-gray-200 rounded-lg py-1 md:px-2 px-1">
                                        <span class="flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                            </svg>
                                        </span>
                                        <span class="flex gap-1 items-center text-xs text-gray-500">
                                            {{ $adminVoucher->usage_count }}
                                            @if($adminVoucher->usage_limit)
                                                <span class="text-xs text-gray-500">
                                                    {{ '/' . $adminVoucher->usage_limit }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-500">/</span>
                                                <span class="text-lg text-gray-500">
                                                    {{ '∞' }}
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endif
                            <div class="flex flex-col gap-1">
                                @php
                                    $status = $adminVoucher->is_active && $adminVoucher->isValid() ? 'Active' : 'Inactive';
                                    $statusClass = $adminVoucher->is_active && $adminVoucher->isValid() ? 'bg-green-100 text-green-800 border border-green-600/50' : 'bg-red-100 text-red-800 border border-red-300/50';
                                @endphp
                                <span class="text-xs text-gray-600">Status:</span>
                                <span class="flex justify-center items-center gap-1 {{ $statusClass }} rounded-lg px-2.5 py-1 text-xs">
                                    {{ $status }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-gray-300 text-lg py-12 border-dashed border-2 border-gray-200 rounded-lg p-4 bg-white">
                        No admin vouchers found.
                    </div>
                @endforelse
            </div>
            <!-- Pagination -->
            <div class="mt-6 md:px-0 px-4">
                {{ $adminVouchers->links() }}
            </div>
        </div>
    </div>
</div>

