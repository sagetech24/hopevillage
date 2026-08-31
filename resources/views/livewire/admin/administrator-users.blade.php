<div>
    <x-slot name="header">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ __('Administrator Users') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12 space-y-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 space-y-6 border border-gray-200">
                @if($showPasswordReset && $selectedUser)
                    {{-- "Modal" card for resetting a specific admin password --}}
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ __('Reset Password') }}
                        </h2>
                        <button
                            type="button"
                            wire:click="cancelPasswordReset"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                            title="{{ __('Cancel') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-6 mt-4 mb-6">
                        <div class="text-sm text-gray-700">
                            {{ __('You are about to reset the password of') }}
                        </div>
                        <div class="text-orange-600 font-bold text-xl italic underline">
                            {{ $selectedUser->name }}
                        </div>
                        <div class="text-gray-600 mt-1">
                            {{ $selectedUser->email }}
                        </div>
                    </div>

                    @if (session()->has('message'))
                        <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200 text-green-800 text-sm">
                            {{ session('message') }}
                        </div>
                    @endif

                    <form wire:submit="resetPassword" class="space-y-4">
                        <div class="flex justify-center items-center gap-4">
                            <div class="w-full">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('New Password') }}
                                </label>
                                <div class="relative" x-data="{ showPassword: false }">
                                    <input
                                        type="password"
                                        id="password"
                                        wire:model="password"
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        class="w-full px-4 py-3 pr-10 text-gray-700 border border-orange-300 rounded-full focus:ring-1 transition-all duration-300 focus:ring-orange-500 focus:border-orange-500"
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
    
                            <div class="w-full">
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('Confirm Password') }}
                                </label>
                                <div class="relative" x-data="{ showPasswordConfirm: false }">
                                    <input
                                        type="password"
                                        id="password_confirmation"
                                        wire:model="password_confirmation"
                                        x-bind:type="showPasswordConfirm ? 'text' : 'password'"
                                        class="w-full px-4 py-3 pr-10 text-gray-700 border border-orange-300 rounded-full focus:ring-1 transition-all duration-300 focus:ring-orange-500 focus:border-orange-500"
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

                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                wire:click="cancelPasswordReset"
                                class="px-4 py-3 border border-gray-300 cursor-pointer rounded-full text-center text-md font-medium text-gray-700 hover:bg-gray-100 transition-colors duration-300"
                            >
                                {{ __('Cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="px-8 py-3 bg-orange-600 cursor-pointer hover:bg-orange-700 text-center text-white rounded-full text-lg font-medium transition-colors duration-300"
                            >
                                {{ __('Reset Password') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex justify-between items-center gap-4">
                        <div class="text-gray-600">
                            {{ __('List of admin users.') }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $adminUsers->count() }} admin users
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Name') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Email') }}
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ __('Created') }}
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
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                            {{ $adminUser->email }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                            {{ optional($adminUser->created_at)->format('Y-m-d') }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-right">
                                            <button
                                                type="button"
                                                wire:click="openResetPasswordModal({{ $adminUser->id }})"
                                                class="inline-flex items-center justify-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-full text-xs font-semibold transition-colors"
                                            >
                                                {{ __('Reset Password') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
                                            {{ __('No admin users found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

