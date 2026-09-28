<div>
    @if(($memberActivity->metadata['status'] ?? null) !== 'void' && $memberActivity->activityType?->name !== \App\Services\PointsService::ACTIVITY_ADMIN_VOUCHER_VOID)
        <button
            title="Set as Void"
            wire:confirm="Are you sure you want to void this activity? Related points will be reversed and linked records updated."
            type="button"
            wire:click="setAsVoid"
            class="text-xs text-white bg-orange-500 hover:bg-orange-600 cursor-pointer px-2 py-1 rounded-full transition-colors"
        >
            Click to void this activity
        </button>
    @else
        <span class="text-xs text-white bg-gray-500 px-3 py-1 tracking-wider rounded-full">
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
