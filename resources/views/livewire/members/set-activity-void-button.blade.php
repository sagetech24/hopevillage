<div>
    @if(($memberActivity->metadata['status'] ?? null) !== 'void')
        <button
            title="Set as Void"
            wire:confirm="Are you sure you want to void this activity? Related points will be reversed and linked records updated."
            type="button"
            wire:click="setAsVoid"
            class="text-xs text-gray-600 hover:text-gray-800 bg-blue-100 hover:bg-blue-300 cursor-pointer border border-gray-300 px-2 py-1 rounded-full transition-colors"
        >
            Click to Set Void
        </button>
    @else
        <span class="text-xs text-gray-600 bg-yellow-100 border border-gray-300 px-2 py-0.5 tracking-wider rounded-full">
            Void
        </span>
    @endif

    @if($showMessage)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 1000)"
            x-transition:leave="transition ease-out duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="text-xs text-green-700"
        >
            Activity voided successfully...
        </div>
    @endif
</div>
