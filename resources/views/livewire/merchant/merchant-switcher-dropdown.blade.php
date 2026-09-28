<div class="relative w-full">
    <x-dropdown align="right" width="80">
        <x-slot name="trigger">
            <span class="rounded-md">
                <button type="button" class="flex justify-start items-center pl-3 py-2 w-full truncate rounded-md text-sm leading-4 font-medium text-gray-600 transition ease-in-out duration-150">
                    {{ $currentMerchant ? $currentMerchant->name : 'Select Merchant' }}
                    <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                    </svg>
                </button>
            </span>
        </x-slot>

        <x-slot name="content">
            <div class="">
                <div class="block px-4 py-2 text-xs text-gray-400">
                    Switch Merchant
                </div>

                @foreach($merchants as $merchant)
                    <button 
                        type="button"
                        wire:click="switchMerchant({{ $merchant->id }})"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition {{ $currentMerchant && $merchant->id == $currentMerchant->id ? 'bg-indigo-50 text-indigo-900 font-semibold' : '' }}"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium truncate">{{ $merchant->name }}</span>
                            @if($currentMerchant && $merchant->id == $currentMerchant->id)
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </x-slot>
    </x-dropdown>
</div>
