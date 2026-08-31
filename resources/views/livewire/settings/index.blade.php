<div>
    @can('settings.view')
        <x-slot name="header">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ __('Settings Management') }}
                </h2>
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
                @if (session()->has('error'))
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
                        class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" 
                        role="alert"
                    >
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-7 gap-6">
                    <!-- Left Side: List (3/4) -->
                    <div class="lg:col-span-5 space-y-4">
                        <!-- Search and Filter -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                <div class="md:col-span-3">
                                    <input 
                                        type="text" 
                                        wire:model.live.debounce.300ms="search" 
                                        placeholder="Search settings by name, key, value, or description..." 
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
                                <div class="md:col-span-1">
                                    <button 
                                        @disabled(!auth()->user()->can('settings.create'))
                                        wire:click="createNew"
                                        class="disabled:opacity-70 disabled:cursor-not-allowed w-full text-md text-white hover:text-indigo-100 bg-orange-500 hover:bg-orange-600 hover:-translate-y-0.5 duration-300 transition-all cursor-pointer px-1 py-2 rounded-full font-medium flex justify-center items-center gap-1"
                                        title="Create New Setting"
                                    >
                                        <svg class="w-4 h-4 stroke-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Create New
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 md:mx-0 mx-4">
                            @forelse($settings as $setting)
                                <div class="w-full bg-white hover:bg-gray-50 overflow-hidden border-2 border-gray-300 flex flex-col rounded-lg group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 {{ $settingId == $setting->id ? 'ring-2 ring-orange-500 border-orange-500' : '' }}">
                                    <div class="p-6">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex md:flex-row flex-col md:items-center items-start gap-2 mb-2">
                                                    <div class="flex-1 min-w-0">
                                                        <h3 class="text-lg font-bold text-gray-700 truncate flex items-center gap-2">
                                                            {{ $setting->name ?? $setting->key }}
                                                            @php
                                                                $status = $setting->status ? 'Active' : 'Inactive';
                                                                $statusClass = $setting->status ? 'bg-green-100 text-green-800 border border-green-400' : 'bg-red-100 text-red-800 border border-red-400';
                                                            @endphp
                                                            <span class="flex items-center gap-1 {{ $statusClass }} rounded-lg px-2 py-0.5 text-xs shrink-0">
                                                                {{ $status }}
                                                            </span>
                                                        </h3>
                                                        @if($setting->description)
                                                            <p class="text-sm text-gray-600 my-2 line-clamp-2">{{ $setting->description }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        @can('settings.edit')
                                                            <button 
                                                                wire:click="edit({{ $setting->id }})"
                                                                title="Edit Setting"
                                                                class="rounded-full cursor-pointer p-4 flex items-center hover:scale-110 justify-center bg-blue-500 hover:bg-sky-600 text-white transition-all duration-300">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                                </svg>
                                                            </button>
                                                        @endcan
                                                        @can('settings.delete')
                                                            <button 
                                                                wire:confirm="Are you sure you want to delete this setting?" 
                                                                title="Delete Setting"
                                                                wire:click="delete({{ $setting->id }})"
                                                                class="rounded-full cursor-pointer p-4 flex items-center hover:scale-110 justify-center bg-red-400 hover:bg-red-500 text-white transition-all duration-300">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 items-center">
                                                    <div>
                                                        <p class="text-md text-gray-500 mb-1">Key:</p>
                                                        <p class="overflow-x-auto text-sm min-w-xs text-gray-700 font-mono bg-gray-100 border border-gray-200 rounded px-4 py-2 inline-block">
                                                            {{ $setting->key }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-md text-gray-500 mb-1">Value:</p>
                                                        <p class="overflow-x-auto text-sm min-w-xs text-gray-800 bg-gray-100 border border-gray-200 rounded px-2 py-2 inline-block">
                                                            {{ Str::limit($setting->value ?? '(empty)', 50) }}
                                                        </p>
                                                    </div>
                                                </div>

                                                {{-- add the timestamp and added by user --}}
                                                <div class="mt-4 flex md:flex-row flex-col md:items-center items-start md:justify-between justify-start">
                                                    <p class="text-xs text-gray-500">
                                                        Added on: {{ $setting->created_at->format('d M Y g:i A') }}@if($setting->addedBy) by {{ $setting->addedBy->name }}@endif
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        Last updated: {{ $setting->updated_at->format('d M Y g:i A') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-gray-300 text-lg py-12 border-dashed border-2 border-gray-200 rounded-lg p-4 bg-white">
                                    No settings found.
                                </div>
                            @endforelse
                        </div>
                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $settings->links() }}
                        </div>
                    </div>

                    <!-- Right Side: Form (1/4) -->
                    <div class="lg:col-span-2 space-y-4 sticky top-4">
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 space-y-3">
                            <h3 class="font-semibold text-gray-800">{{ __('Admin shortcuts') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('Jump to permission-controlled areas.') }}</p>
                            <div class="flex flex-col gap-2">
                                @can('update_user_permissions')
                                    <a
                                        href="{{ route('admin.user-permissions') }}"
                                        class="text-sm font-medium text-orange-600 hover:text-orange-800"
                                    >
                                        {{ __('Admin User Permissions') }}
                                    </a>
                                @endcan
                                @if (auth()->user()?->canAccessAdminMarketplace())
                                    <a
                                        href="{{ route('admin.marketplace.index') }}"
                                        class="text-sm font-medium text-orange-600 hover:text-orange-800"
                                    >
                                        {{ __('Marketplace (admin)') }}
                                    </a>
                                @elseif (auth()->user()?->canAccessMarketplaceCashier())
                                    <a
                                        href="{{ route('admin.marketplace.cashier') }}"
                                        class="text-sm font-medium text-orange-600 hover:text-orange-800"
                                    >
                                        {{ __('Marketplace Cashier') }}
                                    </a>
                                @endif
                                @unless (auth()->user()?->can('update_user_permissions') || auth()->user()?->canAccessAdminMarketplace() || auth()->user()?->canAccessMarketplaceCashier())
                                    <p class="text-sm text-gray-400">{{ __('No additional shortcuts for your account.') }}</p>
                                @endunless
                            </div>
                        </div>
                        <div 
                            id="settings-form"
                            class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 sticky top-4"
                            x-data="{ 
                                scrollToForm() {
                                    this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }
                            }"
                            @scroll-to-form.window="scrollToForm()"
                        >
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-semibold text-lg text-gray-800">
                                    {{ $settingId ? __('Edit Setting') : __('Create New Setting') }}
                                </h3>
                            </div>

                            <form wire:submit="save" class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        placeholder="Setting Name"
                                        type="text" 
                                        id="name"
                                        wire:model.blur="name" 
                                        class="w-full px-4 py-2 text-sm border rounded-full focus:ring-1 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('name') border-red-500 @enderror"
                                    >
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea 
                                        placeholder="Setting description"
                                        id="description"
                                        wire:model.blur="description" 
                                        rows="4"
                                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-1 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('description') border-red-500 @enderror"
                                    ></textarea>
                                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Key -->
                                <div>
                                    <label for="key" class="block text-sm font-medium text-gray-700 mb-1">
                                        Key <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        placeholder="setting_key_name"
                                        type="text" 
                                        id="key"
                                        wire:model.blur="key" 
                                        @if($settingId) disabled @endif
                                        class="w-full px-4 py-2 text-sm border rounded-full focus:ring-1 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('key') border-red-500 @enderror @if($settingId) bg-gray-100 cursor-not-allowed @endif"
                                    >
                                    @error('key') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Value -->
                                <div>
                                    <label for="value" class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                                    <textarea 
                                        placeholder="Setting value"
                                        id="value"
                                        wire:model.blur="value" 
                                        rows="4"
                                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-1 text-gray-700 focus:ring-orange-500 focus:border-orange-500 @error('value') border-red-500 @enderror"
                                    ></textarea>
                                    @error('value') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model.blur="status" 
                                            class="w-6 h-6 rounded-full  p-3 text-orange-600 focus:border-orange-500 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-md text-gray-700">Active</span>
                                    </label>
                                </div>

                                <!-- Submit Button -->
                                <button 
                                    type="submit" 
                                    @disabled(!auth()->user()->can('settings.create'))
                                    class="disabled:opacity-70 disabled:cursor-not-allowed mt-4 cursor-pointer w-full px-4 py-2 bg-orange-500 text-white rounded-full text-lg hover:bg-orange-600 transition"
                                >
                                    {{ $settingId ? 'Update' : 'Create' }} Setting
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endcan
</div>
