<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full max-w-6xl mx-auto">
            <div class="flex items-center gap-2">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Admin Dashboard') }}
                </h2>
                <div class="badge bg-green-600 text-white border-none badge-lg gap-2 drop-shadow py-2 px-4 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Administrator
                </div>
            </div>
            @if (auth()->user()?->canAccessUserPermissions())
                <a href="{{ route('admin.user-permissions') }}" class="bg-yellow-600 text-white px-3 py-2 rounded-full flex items-center gap-2 transition-all duration-300 hover:bg-yellow-700 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    User Permissions
                </a>
            @endif
        </div>
    </x-slot>

    <div class="pt-14 pb-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Welcome Section -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Welcome back, {{ auth()->user()->name }}!</h1>
                <p class="text-gray-600 mt-1">Here's what's happening with your community today.</p>
            </div>

            <!-- Stats Cards Grid (eager — above the fold) -->
            <livewire:admin.dashboard-stats />
            <br />
            <livewire:admin.weekly-registration-traffic lazy />

            <br />
            <!-- Entry Scanning Per Location Chart -->
            <livewire:admin.entry-scans-per-location lazy />
            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">

                <!-- Left Column - Charts (2/3 width) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Analytics Charts -->
                    <livewire:admin.dashboard-charts lazy />
                </div>

                <!-- Right Column - Tables & Activity (1/3 width) -->
                <div class="space-y-6">
                    <!-- Upcoming Events -->
                    <livewire:admin.upcoming-events lazy />
                    
                    <!-- Top Members -->
                    <livewire:admin.top-members lazy />

                    <livewire:admin.points-distribution lazy />
                    
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
