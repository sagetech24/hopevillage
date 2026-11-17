<div>
    @if($memberActivity->activityType?->name === \App\Services\PointsService::ACTIVITY_ADMIN_VOUCHER_VOID)
        <span class="text-xs text-white bg-gray-500 px-3 py-1 tracking-wider rounded-full">
            Void audit
        </span>
    @elseif(($memberActivity->metadata['status'] ?? null) === 'void')
        <button
            title="Undo void"
            wire:confirm="Are you sure you want to undo this void? Points and linked records will be restored when possible."
            type="button"
            wire:click="undoVoid"
            class="text-xs text-white bg-blue-600 hover:bg-blue-700 cursor-pointer px-2 py-1 rounded-full transition-colors"
        >
            Undo void
        </button>
    @else
        <button
            title="Set as Void"
            wire:confirm="Are you sure you want to void this activity? Related points will be reversed and linked records updated."
            type="button"
            wire:click="setAsVoid"
            class="text-xs text-white bg-orange-500 hover:bg-orange-600 cursor-pointer px-2 py-1 rounded-full transition-colors"
        >
            Click to void this activity
        </button>
    @endif

    @if($showMessage)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 1500)"
            x-transition:leave="transition ease-out duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="text-xs text-green-700 mt-1"
        >
            {{ $messageText ?: 'Done.' }}
        </div>
    @endif
</div>
