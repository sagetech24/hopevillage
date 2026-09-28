<div>
    @can('voucher.profile')
        <x-slot name="header">
            <div class="flex md:flex-row flex-col md:gap-0 gap-4 justify-between items-center">
                <div class="flex items-center gap-4">
                    <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                        {{ $voucher->name }} - Profile
                    </h2>
                </div>
                @can('voucher.edit')
                    <div class="flex gap-2">
                        <a href="{{ route('admin.vouchers.edit', $voucher->voucher_code) }}" class="bg-slate-600 md:text-base text-xs hover:bg-slate-700 text-white font-normal py-2 px-4 rounded-lg">
                            Edit Voucher
                        </a>
                    </div>
                @endcan
            </div>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <a href="{{ route('admin.vouchers.index') }}" class="text-gray-600 hover:text-gray-900 md:mx-0 mx-4">
                    ← Back to Vouchers
                </a>
                
                <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6 md:mx-0 mx-4">
                    <!-- Left Column - Voucher Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Voucher Information Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Voucher Information</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Name</label>
                                    <p class="text-gray-900">{{ $voucher->name }}</p>
                                </div>
                                
                                @if($voucher->description)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Description</label>
                                    <p class="text-gray-900">{{ $voucher->description }}</p>
                                </div>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Voucher Code</label>
                                        <p class="text-gray-900 font-mono">{{ $voucher->voucher_code }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Status</label>
                                        <p>
                                            @php
                                                $isValid = $voucher->is_active && $voucher->isValid();
                                                $statusClass = $isValid ? 'bg-green-100 text-green-800 border border-green-500' : 'bg-red-100 text-red-800 border border-red-500';
                                            @endphp
                                            <span class="px-3 py-1 inline-flex text-md leading-5 font-semibold rounded-full {{ $statusClass }}">
                                                {{ $isValid ? 'Active' : 'Inactive' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Discount Type</label>
                                        <p class="text-gray-900 capitalize">{{ $voucher->discount_type }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Discount Value</label>
                                        <p class="text-gray-900 font-semibold text-lg">
                                            @if($voucher->discount_type === 'percentage')
                                                {{ $voucher->discount_value }}%
                                            @elseif($voucher->discount_type === 'item')
                                                Free Item
                                            @else
                                                ${{ number_format($voucher->discount_value, 2) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                @if($voucher->min_purchase)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Minimum Purchase</label>
                                    <p class="text-gray-900">${{ number_format($voucher->min_purchase, 2) }}</p>
                                </div>
                                @endif

                                @if($voucher->max_discount)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Maximum Discount</label>
                                    <p class="text-gray-900">${{ number_format($voucher->max_discount, 2) }}</p>
                                </div>
                                @endif

                                @if($voucher->valid_from || $voucher->valid_until)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if($voucher->valid_from)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Valid From</label>
                                        <p class="text-gray-900">{{ $voucher->valid_from->format('M d, Y g:i A') }}</p>
                                    </div>
                                    @endif
                                    @if($voucher->valid_until)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Valid Until</label>
                                        <p class="text-gray-900">{{ $voucher->valid_until->format('M d, Y g:i A') }}</p>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                @if($voucher->usage_limit)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Usage</label>
                                    <p class="text-gray-900">{{ $voucher->usage_count }} / {{ $voucher->usage_limit }}</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Merchant Information Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Merchant Information</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Merchant</label>
                                    <p class="text-gray-900">
                                        <a href="{{ route('admin.merchants.profile', $voucher->merchant->merchant_code) }}" class="text-indigo-600 hover:text-indigo-800">
                                            {{ $voucher->merchant->name }}
                                        </a>
                                    </p>
                                </div>
                                
                                @if($voucher->merchant->email)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Merchant Email</label>
                                    <p class="text-gray-900">{{ $voucher->merchant->email }}</p>
                                </div>
                                @endif

                                @if($voucher->merchant->phone)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Merchant Phone</label>
                                    <p class="text-gray-900">{{ $voucher->merchant->phone }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Quick Actions -->
                    <div class="space-y-6">
                        <!-- Quick Actions Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Quick Actions</h3>
                            
                            <div class="space-y-3">
                                @can('voucher.edit')
                                    <a href="{{ route('admin.vouchers.edit', $voucher->voucher_code) }}" class="flex items-center justify-center gap-2 w-full bg-indigo-500 hover:bg-indigo-600 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                        Edit Voucher
                                    </a>
                                @endcan
                                <a href="{{ route('admin.merchants.profile', $voucher->merchant->merchant_code) }}" class="flex items-center justify-center gap-2 w-full bg-gray-600 hover:bg-gray-700 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                    View Merchant
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endcan
</div>
