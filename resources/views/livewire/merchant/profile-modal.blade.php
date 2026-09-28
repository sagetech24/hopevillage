<div>
    @if($open)
        @teleport('body')
            <div
                class="fixed inset-0 z-[9999] bg-black/60 flex items-end sm:items-center justify-center p-4"
                wire:click="close"
                wire:keydown.escape.window="close"
                role="dialog"
                aria-modal="true"
                aria-labelledby="merchant-profile-title"
            >
                <div wire:click.stop class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md relative max-h-[90vh] overflow-y-auto">
                    <button
                        type="button"
                        wire:click="close"
                        class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition-colors"
                        aria-label="Close profile"
                    >
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">Settings</p>
                        <h2 id="merchant-profile-title" class="mt-1 text-lg font-bold text-slate-900">My Profile</h2>

                        <div class="mt-5 flex items-center gap-3">
                            <img src="{{ $profilePhotoUrl }}" alt="" class="size-14 rounded-full object-cover bg-slate-100">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 truncate">{{ $profileName }}</p>
                                <p class="text-sm text-slate-500 truncate">{{ $storeName }}</p>
                            </div>
                        </div>

                        <dl class="mt-5 grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Mobile number</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $mobileNumber ?: 'Not set' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Store</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $storeName }}</dd>
                            </div>
                        </dl>

                        <form wire:submit="updateEmail" class="mt-6 space-y-3">
                            <h3 class="text-sm font-semibold text-slate-800">Email address</h3>
                            <p class="text-xs text-slate-500">This is the address you use to sign in.</p>

                            @if($emailStatus)
                                <p class="rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700">{{ $emailStatus }}</p>
                            @endif

                            <div>
                                <label for="merchant-profile-email" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Email</label>
                                <input
                                    id="merchant-profile-email"
                                    type="email"
                                    wire:model="email"
                                    value="{{ $email }}"
                                    autocomplete="email"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none @error('email') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                                >
                                @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="merchant-profile-email-password" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Current password</label>
                                <input
                                    id="merchant-profile-email-password"
                                    type="password"
                                    wire:model="email_current_password"
                                    autocomplete="current-password"
                                    placeholder="Required when changing email"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none @error('email_current_password') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                                >
                                @error('email_current_password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="updateEmail"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="updateEmail">Update email</span>
                                <span wire:loading wire:target="updateEmail">Saving…</span>
                            </button>
                        </form>

                        <form wire:submit="updatePassword" class="mt-6 space-y-3 border-t border-slate-200 pt-6">
                            <h3 class="text-sm font-semibold text-slate-800">Password</h3>
                            <p class="text-xs text-slate-500">Use at least 8 characters and include a number.</p>

                            @if($passwordStatus)
                                <p class="rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700">{{ $passwordStatus }}</p>
                            @endif

                            <div>
                                <label for="merchant-profile-current-password" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Current password</label>
                                <input
                                    id="merchant-profile-current-password"
                                    type="password"
                                    wire:model="current_password"
                                    autocomplete="current-password"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none @error('current_password') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                                >
                                @error('current_password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="merchant-profile-new-password" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">New password</label>
                                <input
                                    id="merchant-profile-new-password"
                                    type="password"
                                    wire:model="password"
                                    autocomplete="new-password"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none @error('password') border-red-400 focus:border-red-500 focus:ring-red-100 @enderror"
                                >
                                @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="merchant-profile-password-confirmation" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">Confirm new password</label>
                                <input
                                    id="merchant-profile-password-confirmation"
                                    type="password"
                                    wire:model="password_confirmation"
                                    autocomplete="new-password"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 focus:outline-none"
                                >
                            </div>

                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="updatePassword"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-[#3a5870] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#2d4558] disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="updatePassword">Update password</span>
                                <span wire:loading wire:target="updatePassword">Saving…</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
