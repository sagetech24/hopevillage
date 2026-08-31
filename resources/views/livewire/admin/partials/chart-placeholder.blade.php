<div class="card bg-white shadow border border-gray-300">
    <div class="card-body">
        <h2 class="card-title text-gray-800">
            {{ $title ?? 'Loading chart…' }}
        </h2>
        <div class="relative h-[350px] mt-4 flex items-center justify-center">
            <div class="flex flex-col items-center gap-3 text-gray-500">
                <span class="loading loading-spinner loading-md text-orange-500"></span>
                <p class="text-sm">Loading chart data…</p>
            </div>
        </div>
    </div>
</div>
