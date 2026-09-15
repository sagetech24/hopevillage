<div>
    @php
        $isEditing = filled($eventId);
        $canManageEvent = $isEditing
            ? auth()->user()?->can('event.edit')
            : auth()->user()?->can('event.create');
    @endphp

    @if($canManageEvent)
    <x-slot name="header">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 md:px-0 xl:px-0 px-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $eventId ? __('Update') . ' ' . '"'.trim($title).'"' : __('Create new event') . ' ' . 'in ' . $location->name . ' location' }}
                    </h2>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 md:px-0 xl:px-0 px-3">
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

            <a href="{{ route('admin.locations.events.index', $location->location_code) }}" class="flex items-center gap-1 text-orange-600 hover:text-orange-900 mb-6 md:mx-0 mx-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Events
            </a>
            <form wire:submit="save">
                <div class="flex gap-4 lg:flex-row flex-col md:px-0 px-4">
                    {{-- left side --}}
                    <div class="lg:w-1/3 w-full">
                        <!-- Thumbnail Upload -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Thumbnail</label>
                            
                            @if($existingThumbnail && !$thumbnail)
                                <div class="mb-4 relative">
                                    <img src="{{ $existingThumbnail }}" alt="Current thumbnail" class="w-full h-64 mb-4 object-cover rounded-lg border border-gray-300">
                                    <button 
                                        type="button" 
                                        wire:click="removeThumbnail" 
                                        title="Remove Thumbnail"
                                        wire:confirm="Are you sure you want to remove the thumbnail?"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        wire:loading.class.remove="opacity-100 cursor-pointer"
                                        class="flex gap-1 items-center justify-center absolute -top-1.5 -right-2 hover:scale-105 transition-all duration-300 text-red-600 hover:text-red-800 text-xs border border-red-300 bg-red-100 hover:bg-red-200/75 p-1 rounded-full"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @endif

                            @if($thumbnail)
                                <div class="mt-2">
                                    <img src="{{ $thumbnail->temporaryUrl() }}" alt="Preview" class="w-full h-64 mb-4 object-cover rounded-lg border border-gray-300">
                                </div>
                            @endif
                            @if(!$thumbnail && !$existingThumbnail)
                            <div class="w-full h-64 mb-4 bg-gray-200 rounded-lg flex flex-col items-center justify-center border border-gray-300">
                                    <p class="text-gray-400 text-sm">IMG</p>
                                    <p class="text-gray-400 text-sm">Upload</p>
                                </div>
                            @endif

                            <input 
                                type="file" 
                                wire:model="thumbnail" 
                                accept="image/jpeg,image/png,image/webp"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold bg-gray-200/50 border border-gray-300 rounded-lg file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-100"
                            >
                            @error('thumbnail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="lg:w-2/3 w-full">
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <!-- Title -->
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                <input 
                                    placeholder="Event Title"
                                    type="text" 
                                    id="title"
                                    wire:model.blur="title" 
                                    class="w-full px-4 py-2 border rounded-full focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('title') border-red-500 @enderror"
                                >
                                @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <x-editor wire:model="description" class="text-gray-900" />
                                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Date and Time -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input 
                                            type="date" 
                                            id="start_date"
                                            wire:model.blur="start_date" 
                                            class="w-full px-4 py-2 border rounded-full focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('start_date') border-red-500 @enderror"
                                        >
                                    </div>
                                    @error('start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Start Time <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input 
                                            type="time" 
                                            id="start_time"
                                            wire:model.blur="start_time" 
                                            class="w-full px-4 py-2 border rounded-full focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('start_time') border-red-500 @enderror"
                                        >
                                    </div>
                                    @error('start_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input 
                                            type="date" 
                                            id="end_date"
                                            wire:model.blur="end_date" 
                                            class="w-full px-4 py-2 border rounded-full focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('end_date') border-red-500 @enderror"
                                        >
                                    </div>
                                    @error('end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">End Time <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input 
                                            type="time" 
                                            id="end_time"
                                            wire:model.blur="end_time" 
                                            class="w-full px-4 py-2 border rounded-full focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('end_time') border-red-500 @enderror"
                                        >
                                    </div>
                                    @error('end_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Venue -->
                            <div class="mb-4">
                                <label for="venue" class="block text-sm font-medium text-gray-700 mb-2">Venue</label>
                                <textarea 
                                    placeholder="Event Venue"
                                    id="venue"
                                    wire:model.blur="venue" 
                                    rows="2"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('venue') border-red-500 @enderror"
                                ></textarea>
                                @error('venue') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <!-- Max Participants -->
                                <div>
                                    <label for="max_participants" class="block text-sm font-medium text-gray-700 mb-2">
                                        Max Participants
                                        <span class="text-sm text-gray-400 italic">(leave blank for no limit)</span>
                                    </label>
                                    <input 
                                        placeholder="Leave blank for no limit"
                                        type="number" 
                                        id="max_participants"
                                        wire:model.blur="max_participants" 
                                        min="1"
                                        class="w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('max_participants') border-red-500 @enderror"
                                    >
                                    @error('max_participants') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
            
                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                                    <select 
                                        id="status"
                                        wire:model.blur="status" 
                                        class="w-full px-4 py-2 border rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-700 @error('status') border-red-500 @enderror"
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                    @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <br />
                            <div class="flex justify-end gap-4">
                                <a 
                                    href="{{ route('admin.locations.events.index', $location->location_code) }}" 
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </a>
                                <button 
                                    type="submit" 
                                    class="px-6 py-2 bg-orange-600 text-white rounded-full hover:bg-orange-700 transition"
                                >
                                    {{ $eventId ? 'Update' : 'Create' }} Event
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endif
</div>
