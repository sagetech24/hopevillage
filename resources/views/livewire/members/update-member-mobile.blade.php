<div>
    @if($this->user)
        <div
            x-data="{ open: @entangle('open').live }"
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-4"
            @keydown.escape.window="$wire.close()"
            @click.self="$wire.close()"
            style="display: none;"
        >
            <div
                @click.stop
                class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 relative z-[10000]"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                <button
                    type="button"
                    wire:click="close"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition"
                    aria-label="Close"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">
                    Update Mobile Number
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    Update the mobile number for <span class="font-medium text-gray-700">{{ $this->user->name }}</span>.
                </p>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label for="updateMemberMobile" class="block text-sm font-medium text-gray-700 mb-1">
                            Mobile Number <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center border border-orange-300 rounded-full overflow-hidden focus-within:ring-1 focus-within:ring-orange-500 focus-within:border-orange-500 transition-all duration-300">
                            <span class="pl-4 pr-2 py-3 text-gray-500 bg-gray-50 border-r border-orange-200 select-none font-medium">
                                +65
                            </span>
                            <input
                                type="tel"
                                id="updateMemberMobile"
                                wire:model="mobile_number"
                                class="flex-1 min-w-0 px-3 py-3 border-0 text-gray-700 focus:ring-0 focus:outline-none"
                                placeholder="81234567"
                                inputmode="numeric"
                                autocomplete="tel-national"
                                maxlength="15"
                                required
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Enter the 8-digit number only. If +65 is typed, it will be removed automatically.</p>
                        @error('mobile_number')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            wire:click="close"
                            class="px-4 py-3 border border-gray-300 text-gray-700 rounded-full hover:bg-gray-50 font-medium transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-full font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="save">Update Mobile Number</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-1">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
