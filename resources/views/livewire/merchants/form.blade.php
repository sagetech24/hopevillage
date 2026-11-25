<div>
    <x-slot name="header">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $merchantCode ? __('Edit Merchant') : __('Create Merchant') }}
                </h2>
                <a href="{{ route('admin.merchants.index') }}" class="text-orange-500 font-medium hover:text-orange-700 hover:scale-105 transition-all duration-300 py-2 px-4">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Back to Merchants
                    </span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12"> 

        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
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

            <form wire:submit="save">
                <div class="flex gap-4 lg:flex-row flex-col">
                    <!-- Left Side - Logo Upload -->
                    <div class="lg:w-1/3 w-full">
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Logo</label>
                            
                            @if($existingLogo)
                                <div class="mb-4">
                                    <div class="relative">
                                        <img src="{{ $existingLogo }}" alt="Existing logo" class="w-full h-48 object-contain rounded-lg border border-gray-300">
                                        <button 
                                            type="button" 
                                            wire:click="removeLogo" 
                                            title="Remove Logo"
                                            wire:confirm="Are you sure you want to remove this logo?"
                                            class="absolute -top-1.5 -right-2 hover:scale-105 transition-all duration-300 text-red-600 hover:text-red-800 text-xs border border-red-300 bg-red-100 hover:bg-red-200/75 p-2 rounded-full"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if(!$existingLogo && $logo)
                                <div class="mb-4">
                                    <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="w-full h-48 object-contain rounded-lg border border-gray-300">
                                </div>
                            @endif

                            @if(!$existingLogo && !$logo)
                                <div class="w-full h-48 mb-4 bg-gray-200 rounded-lg flex flex-col items-center justify-center border border-gray-300">
                                    <p class="text-gray-400 text-sm">Logo</p>
                                    <p class="text-gray-400 text-sm">Upload</p>
                                </div>
                            @endif

                            <input 
                                type="file" 
                                wire:model="logo" 
                                accept="image/jpeg,image/png,image/webp"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold bg-gray-200/50 border border-gray-300 rounded-lg file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-100"
                            >
                            @error('logo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Right Side - Form Fields -->
                    <div class="lg:w-2/3 w-full">
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <!-- Name -->
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                                <input 
                                    placeholder="Merchant Name"
                                    type="text" 
                                    id="name"
                                    wire:model.blur="name" 
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('name') border-red-500 @enderror"
                                >
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea 
                                    placeholder="Description"
                                    id="description"
                                    wire:model.blur="description" 
                                    rows="4"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('description') border-red-500 @enderror"
                                ></textarea>
                                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Contact Name -->
                            <div class="mb-4">
                                <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                                <input 
                                    placeholder="Contact Person Name"
                                    type="text" 
                                    id="contact_name"
                                    wire:model.blur="contact_name" 
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('contact_name') border-red-500 @enderror"
                                >
                                @error('contact_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone and Email -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                    <input 
                                        placeholder="Phone Number"
                                        type="text" 
                                        id="phone"
                                        wire:model.blur="phone" 
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('phone') border-red-500 @enderror"
                                    >
                                    @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input 
                                        placeholder="Email Address"
                                        type="email" 
                                        id="email"
                                        wire:model.blur="email" 
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('email') border-red-500 @enderror"
                                    >
                                    @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="mb-4">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <input 
                                    placeholder="Street Address"
                                    type="text" 
                                    id="address"
                                    wire:model.blur="address" 
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('address') border-red-500 @enderror"
                                >
                                @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- City, Province, Postal Code -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                    <input 
                                        placeholder="City"
                                        type="text" 
                                        id="city"
                                        wire:model.blur="city" 
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('city') border-red-500 @enderror"
                                    >
                                    @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="province" class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                                    <input 
                                        placeholder="Province"
                                        type="text" 
                                        id="province"
                                        wire:model.blur="province" 
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('province') border-red-500 @enderror"
                                    >
                                    @error('province') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                    <input 
                                        placeholder="Postal Code"
                                        type="text" 
                                        id="postal_code"
                                        wire:model.blur="postal_code" 
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('postal_code') border-red-500 @enderror"
                                    >
                                    @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Website -->
                            <div class="mb-4">
                                <label for="website" class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                <input 
                                    placeholder="https://example.com"
                                    type="url" 
                                    id="website"
                                    wire:model.blur="website" 
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('website') border-red-500 @enderror"
                                >
                                @error('website') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Is Active / GST Member -->
                            <div class="mb-6 flex flex-wrap gap-6">
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model.blur="is_active" 
                                        class="w-6 h-6 rounded-none border-gray-300 text-gray-700 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                    >
                                    <span class="ml-2 text-md text-gray-700">Active</span>
                                </label>
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model.blur="is_gst_member" 
                                        class="w-6 h-6 rounded-none border-gray-300 text-gray-700 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                    >
                                    <span class="ml-2 text-md text-gray-700">GST Member</span>
                                </label>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex justify-end gap-4">
                                <a 
                                    href="{{ route('admin.merchants.index') }}" 
                                    class="px-6 py-2 border border-gray-300 rounded-full text-gray-700 text-lg font-medium hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </a>
                                <button 
                                    type="submit" 
                                    class="px-6 py-2 bg-orange-500 text-white rounded-full cursor-pointer text-lg font-medium hover:bg-orange-600 transition"
                                >
                                    {{ $merchantCode ? 'Update' : 'Create' }} Merchant
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
