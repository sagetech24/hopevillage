<div>
    @if(auth()->user()->merchants->count() > 1)
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Switch Merchant</label>
                    <p class="text-xs text-gray-600">You have access to multiple merchants. Select which one to manage.</p>
                </div>
                <form wire:submit="switchMerchant" class="flex items-center gap-3">
                    <select 
                        wire:model="selectedMerchantId"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        @foreach($merchants as $merchant)
                            <option value="{{ $merchant->id }}" @selected($currentMerchant && $merchant->id == $currentMerchant->id)>
                                {{ $merchant->name }}
                            </option>
                        @endforeach
                    </select>
                    <button 
                        type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition"
                    >
                        Switch
                    </button>
                </form>
            </div>
            
            <!-- Merchant Tags -->
            <div class="mt-4 pt-4 border-t border-blue-300">
                <p class="text-xs font-medium text-gray-700 mb-2">Your Merchants:</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($merchants as $merchant)
                        <span class="px-3 py-1 text-xs rounded-lg font-semibold {{ $currentMerchant && $merchant->id == $currentMerchant->id ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-700 border border-indigo-300' }}">
                            {{ $merchant->name }}
                            @if($currentMerchant && $merchant->id == $currentMerchant->id)
                                <span class="ml-1">(Current)</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif(auth()->user()->merchants->count() > 0 && $currentMerchant)
        <div class="mb-6 flex items-center gap-2">
            <span class="text-sm text-gray-600">Current Merchant:</span>
            <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-lg font-semibold text-sm">
                {{ $currentMerchant->name }}
            </span>
        </div>
    @endif

    @if (session()->has('merchant-switched'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 2000)"
            x-show="show"
            x-transition:leave="transition ease-out duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
            role="alert"
        >
            <span class="block sm:inline">{{ session('merchant-switched') }}</span>
        </div>
    @endif

    @if (session()->has('merchant-switch-error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('merchant-switch-error') }}</span>
        </div>
    @endif
</div>
