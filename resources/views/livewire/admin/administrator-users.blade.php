<div>
    <x-slot name="header">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ __('Administrator Users') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12 space-y-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 space-y-6 border border-gray-200">
                @if (session()->has('message'))
                    <div class="rounded-md bg-green-50 p-4 border border-green-200 text-green-800 text-sm">
                        {{ session('message') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="rounded-md bg-red-50 p-4 border border-red-200 text-red-800 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        {{-- <div>
                            <div class="text-gray-600">
                                {{ __('List of admin users.') }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $adminUsers->total() }} admin users
                            </div>
                        </div> --}}
                        <div class="relative w-full sm:w-96">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="{{ __('Search by name, email, or mobile number...') }}"
                                class="w-full px-4 py-2 {{ $search !== '' ? 'pr-10' : '' }} border text-gray-700 border-gray-500 rounded-full focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                            >
                            @if($search !== '')
                                <button
                                    type="button"
                                    wire:click="$set('search', '')"
                                    class="absolute inset-y-0 right-2 flex items-center px-2 text-orange-400 hover:text-orange-700"
                                    aria-label="{{ __('Clear search') }}"
                                    title="{{ __('Clear search') }}"
                                >
                                    <svg class="w-4 h-4 stroke-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Name') }}
                                    </th>
                                    <th scope="col" class="w-58 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Email') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Mobile Number') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Created Date') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Action') }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($adminUsers as $adminUser)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $adminUser->name }}
                                        </td>
                                        <td class="px-4 py-2 truncate text-sm text-gray-700">
                                            {{ $adminUser->email }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                            {{ $adminUser->whatsapp_number ?: '—' }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                            {{ $adminUser->created_at?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-right">
                                            <div
                                                x-data="{
                                                    open: false,
                                                    position: { top: 0, right: 0 },
                                                    setPosition($el) {
                                                        const rect = $el.getBoundingClientRect();
                                                        this.position = {
                                                            top: rect.bottom + 4,
                                                            right: window.innerWidth - rect.right
                                                        };
                                                    }
                                                }"
                                                class="relative inline-flex items-center justify-end"
                                                @click.away="open = false"
                                            >
                                                <button
                                                    type="button"
                                                    @click="open = !open; $nextTick(() => { if (open) setPosition($el); })"
                                                    class="inline-flex items-center justify-center size-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
                                                    title="{{ __('Actions') }}"
                                                    aria-label="{{ __('Actions') }}"
                                                    aria-haspopup="menu"
                                                    :aria-expanded="open.toString()"
                                                >
                                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                                    </svg>
                                                </button>

                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="transform opacity-0 scale-95"
                                                    x-transition:enter-end="transform opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="transform opacity-100 scale-100"
                                                    x-transition:leave-end="transform opacity-0 scale-95"
                                                    :style="`position: fixed; top: ${position.top}px; right: ${position.right}px;`"
                                                    class="w-64 z-[9999] origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black/5 focus:outline-none"
                                                    role="menu"
                                                >
                                                    <div class="py-1">
                                                        <button
                                                            type="button"
                                                            wire:click="openResetPasswordModal({{ $adminUser->id }})"
                                                            @click="open = false"
                                                            class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                                            role="menuitem"
                                                        >
                                                            <svg class="size-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                            </svg>
                                                            <span>{{ __('Reset Password') }}</span>
                                                        </button>
                                                        @if($adminUser->id !== auth()->id() && ! $adminUser->isSuperAdmin())
                                                            <div class="border-t border-gray-100 my-1"></div>
                                                            <button
                                                                type="button"
                                                                wire:click="removeAsAdmin({{ $adminUser->id }})"
                                                                wire:confirm="Remove this administrator and convert them to an ordinary member? They will lose admin access."
                                                                @click="open = false"
                                                                class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                                                role="menuitem"
                                                            >
                                                                <svg class="size-4 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4.136 20.227A9 9 0 0 1 10.5 15.75h3.379a2.25 2.25 0 0 1 1.59.659l2.122 2.121c.16.16.1.434-.146.543a9.001 9.001 0 1 1-13.31 1.154Z" />
                                                                </svg>
                                                                <span>{{ __('Remove as Admin') }}</span>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                wire:click="convertToMerchantUser({{ $adminUser->id }})"
                                                                wire:confirm="Convert this administrator to a merchant user? They will lose admin access."
                                                                @click="open = false"
                                                                class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                                                role="menuitem"
                                                            >
                                                                <svg class="size-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72L4.318 3.32A3 3 0 0 1 7.502 2.25h9.001a3 3 0 0 1 3.184 1.07l1.932 2.632a3.004 3.004 0 0 1-.621 4.72m-13.5 8.65h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                                                </svg>
                                                                <span>{{ __('Convert to Merchant User') }}</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                            {{ __('No admin users found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($adminUsers->hasPages())
                        <div class="pt-2">
                            {{ $adminUsers->links() }}
                        </div>
                    @endif
            </div>
        </div>
    </div>

    <x-dialog-modal wire:model.live="showPasswordReset" maxWidth="lg">
        <x-slot name="title">
            {{ __('Reset Password') }}
        </x-slot>

        <x-slot name="content">
            @if($selectedUser)
                <form id="reset-admin-password-form" wire:submit="resetPassword" wire:key="reset-admin-password-{{ $selectedUser->id }}" class="space-y-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-4">
                        <div class="text-sm text-gray-700">
                            {{ __('You are about to reset the password of') }}
                        </div>
                        <div class="text-orange-600 font-bold text-xl italic underline">
                            {{ $selectedUser->name }}
                        </div>
                        <div class="text-gray-600 mt-2">
                            Email: {{ $selectedUser->email }}
                            <br>
                            Mobile Number: {{ $selectedUser->whatsapp_number ?: '—' }}
                            <br>
                            Created Date: {{ $selectedUser->created_at?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('New Password') }}
                            </label>
                            <div class="relative" x-data="{ showPassword: false }">
                                <input
                                    type="password"
                                    id="password"
                                    wire:model="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    class="w-full px-4 py-3 pr-10 text-gray-700 border border-gray-300 rounded-full focus:ring-0 focus:outline-none transition-all duration-300 focus:ring-orange-500 focus:border-orange-500"
                                    placeholder="Enter new password"
                                    required
                                >
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                    tabindex="-1"
                                >
                                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('Confirm Password') }}
                            </label>
                            <div class="relative" x-data="{ showPasswordConfirm: false }">
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    wire:model="password_confirmation"
                                    x-bind:type="showPasswordConfirm ? 'text' : 'password'"
                                    class="w-full px-4 py-3 pr-10 text-gray-700 border border-gray-300 rounded-full focus:ring-0 focus:outline-none transition-all duration-300 focus:ring-orange-500 focus:border-orange-500"
                                    placeholder="Confirm new password"
                                    required
                                >
                                <button
                                    type="button"
                                    @click="showPasswordConfirm = !showPasswordConfirm"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                    tabindex="-1"
                                >
                                    <svg x-show="!showPasswordConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg x-show="showPasswordConfirm" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </form>
            @endif
        </x-slot>

        <x-slot name="footer">
            <button
                type="button"
                wire:click="cancelPasswordReset"
                class="px-4 py-2 border border-gray-300 cursor-pointer rounded-full text-center text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors duration-300"
            >
                {{ __('Cancel') }}
            </button>
            <button
                type="submit"
                form="reset-admin-password-form"
                class="ms-3 px-8 py-2 bg-orange-600 cursor-pointer hover:bg-orange-700 text-center text-white rounded-full text-sm font-medium transition-colors duration-300"
            >
                {{ __('Reset Password') }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>

