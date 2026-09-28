<div class="card bg-white shadow border border-gray-300">
    <div class="card-body">
        <div class="flex justify-between items-center mb-4 flex-col md:flex-row gap-4">
            <h2 class="card-title text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Recent Events Participants
            </h2>
            <div class="w-full md:w-1/2">
                <select 
                    wire:model.live="selectedLocationId"
                    class="select w-full text-gray-800 focus:outline-none rounded-full bg-white border-gray-300 focus:ring-0 focus:ring-gray-500 focus:border-gray-500"
                >
                @foreach($activeLocations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
            </div>
        </div>
        <div class="relative h-[450px] mt-4">
            @if(empty($recentEvents['labels']))
                <div class="flex items-center justify-center h-full">
                    <p class="text-gray-500 text-center">No finished events found for this location.</p>
                </div>
            @else
            <canvas 
                wire:key="recent-events-chart-{{ $selectedLocationId }}"
                x-data="{
                    chart: null,
                    init() {
                        const ctx = this.$el.getContext('2d');
                        this.chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: @js($recentEvents['labels']),
                                datasets: [
                                    {
                                        label: 'Registered',
                                        data: @js($recentEvents['registered']),
                                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                        borderColor: 'rgb(59, 130, 246)',
                                        borderWidth: 2,
                                        borderRadius: 4
                                    },
                                    {
                                        label: 'Attended',
                                        data: @js($recentEvents['attended']),
                                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                                        borderColor: 'rgb(16, 185, 129)',
                                        borderWidth: 2,
                                        borderRadius: 4
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 50,
                                            precision: 0
                                        },
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            maxRotation: 30,
                                            minRotation: 30
                                        }
                                    }
                                },
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                }
                            }
                        });
                    },
                    destroy() {
                        if (this.chart) {
                            this.chart.destroy();
                        }
                    }
                }"
                @destroy.window="destroy()"
            ></canvas>
        @endif
        </div>
    </div>
</div>
