<div>
        <x-slot name="header">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                        {{ __('Admin User Permissions') }}
                    </h2>
                </div>
            </div>
        </x-slot>
        <div class="py-12 space-y-6">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg p-6 space-y-6 border border-gray-200">
                    <div class="space-y-2 border-b border-gray-200 pb-8">
                        <label for="selectedUser" class="block text-sm font-medium text-gray-700">
                            Select admin user
                        </label>
                        <select
                            id="selectedUser"
                            wire:model.live="selectedUserId"
                            class="mt-1 block w-full rounded-full text-gray-800 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:max-w-md"
                        >
                            <option value="" class="text-gray-800">-- Choose admin --</option>
                            @foreach ($adminUsers as $admin)
                                <option value="{{ $admin->id }}" class="text-gray-800">{{ $admin->name }} ({{ $admin->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($saveSuccessMessage)
                        <div
                            wire:key="permission-save-banner-{{ $saveSuccessBannerKey }}"
                            x-data="{ show: true }"
                            x-init="setTimeout(() => { show = false }, 2000)"
                            x-show="show"
                            x-transition.opacity.duration.300ms
                            class="rounded-md bg-green-50 p-4"
                        >
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        {{ $saveSuccessMessage }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
            
                    @if ($selectedUserId)
                        <div class="overflow-x-auto">
                            <h1 class="text-xl font-bold text-gray-600">Route Access Permissions</h1>
                            <p class="text-gray-500 mb-4 text-sm">
                                The following table shows the permissions for the selected admin user.
                            </p>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Feature
                                        </th>
                                        @foreach ($actions as $action)
                                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                @if($action == 'view')
                                                    {{ ucfirst('View List') }}
                                                @else
                                                    {{ ucfirst($action) }}
                                                @endif
                                            </th>
                                        @endforeach
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('All') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($models as $model)
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                                @if($model === 'admin-voucher')
                                                    {{ ucfirst('Admin Vouchers') }}
                                                @elseif($model === 'point-system')
                                                    {{ ucfirst('Point System') }}
                                                @elseif($model === 'news-category')
                                                    {{ ucfirst('News Category') }}
                                                @elseif($model === 'announcement')
                                                    {{ ucfirst('Announcement') }}
                                                @elseif($model === 'marketplace')
                                                    {{ __('Marketplace') }}
                                                @else
                                                    {{ ucfirst($model) }}
                                                @endif
                                            </td>
                                            @foreach ($actions as $action)
                                                <td class="px-4 py-2 text-center" wire:key="perm-{{ $model }}-{{ $action }}">
                                                    <input
                                                        type="checkbox"
                                                        wire:model.live.boolean="permissions.{{ $model }}.{{ $action }}"
                                                        class="h-6 w-6 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                                    >
                                                </td>
                                            @endforeach
                                            <td class="px-4 py-2 text-center">
                                                <button
                                                    type="button"
                                                    wire:key="permission-all-toggle-{{ $model }}"
                                                    wire:click="toggleRowAll('{{ $model }}')"
                                                    class="text-xs rounded-full cursor-pointer w-6 h-6 mx-auto flex items-center justify-center shrink-0 border-0 p-0 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-1 {{ $this->modelRowFullyGranted($model) ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-500' }}"
                                                    title="{{ __('Toggle all actions for this feature') }}"
                                                    aria-label="{{ __('Toggle all actions for this feature') }}"
                                                ></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <h1 class="mt-8 text-xl font-bold text-gray-600">Special Permissions</h1>
                            <p class="text-gray-500 mb-4 text-sm">
                                Special permissions are used to grant additional access to the admin user.
                            </p>
                            <table class="min-w-full divide-y divide-gray-200">
                                <tbody class="bg-white divide-y-0 divide-gray-200">
                                    @foreach ($specialPermissions as $permission => $label)
                                        @php
                                            $isLocked = $this->isLockedSpecialPermission($permission);
                                        @endphp
                                        <tr>
                                            <td class="w-1/2 px-4 py-2 text-sm font-medium text-gray-900">
                                                {{ $label }}
                                                @if ($isLocked)
                                                    <p class="mt-1 text-xs font-normal text-gray-400">
                                                        {{ __('You cannot change this permission on your own account.') }}
                                                    </p>
                                                @endif
                                            </td>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    wire:model.live.boolean="permissions.special.{{ $permission }}"
                                                    @disabled($isLocked)
                                                    class="h-6 w-6 text-orange-600 border-gray-300 rounded focus:ring-orange-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
            
                        <div class="pt-4">
                            <button
                                type="button"
                                wire:click="save"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                wire:loading.class.remove="opacity-100 cursor-pointer"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-md font-medium rounded-full shadow-sm text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Save permissions
                            </button>
                        </div>
                    @else
                        <div class="flex justify-center items-center text-center text-gray-500 max-h-48">
                            <div class="flex flex-col items-center justify-center">
                                <p class="text-lg font-medium text-gray-500/60">
                                    Please select an admin user to view their permissions.
                                </p>
                                <p class="text-md font-medium text-gray-300">
                                    You can select an admin user from the dropdown menu above.
                                </p>
                            </div>
                        </div>  
                    @endif
                </div>
            </div>
        </div>
</div>

