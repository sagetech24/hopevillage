<div class="card bg-white shadow border border-gray-300">
    <div class="card-body">
        <h2 class="card-title text-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Activity Summary for all the members for the last 30 days
        </h2>
        @if(empty($activityTypesData['datasets']))
            <div class="relative flex h-[350px] items-center justify-center mt-4">
                <p class="text-gray-500 text-center">No activity data found for the last 30 days.</p>
            </div>
        @else
            <div
                class="mt-4"
                x-data="{
                    hidden: {},
                    init() {
                        const canvas = this.$refs.canvas;
                        canvas._chart = new Chart(canvas.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: @js($activityTypesData['labels']),
                                datasets: @js($activityTypesData['datasets'])
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
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
                                            stepSize: 10,
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
                                            maxRotation: 45,
                                            minRotation: 45
                                        }
                                    }
                                },
                                interaction: {
                                    mode: 'nearest',
                                    axis: 'x',
                                    intersect: false
                                }
                            }
                        });
                    },
                    toggle(index) {
                        const chart = this.$refs.canvas._chart;
                        if (!chart) return;
                        const visible = !chart.isDatasetVisible(index);
                        chart.setDatasetVisibility(index, visible);
                        this.hidden = { ...this.hidden, [index]: !visible };
                        chart.update();
                    },
                    destroy() {
                        this.$refs.canvas?._chart?.destroy();
                    }
                }"
            >
                <div class="relative h-[350px]" wire:ignore>
                    <canvas class="h-full w-full" x-ref="canvas"></canvas>
                </div>
                @php
                    $legendCount = count($activityTypesData['datasets']);
                    $legendColumns = max(1, (int) ceil($legendCount / 2));
                @endphp
                <p class="text-sm text-gray-700 mt-4 text-left font-semibold">Legend:</p>
                <div
                    class="mt-2 grid justify-start gap-x-5 gap-y-2 overflow-x-auto"
                    style="grid-template-columns: repeat({{ $legendColumns }}, max-content);"
                >
                    @foreach($activityTypesData['datasets'] as $index => $dataset)
                        <button
                            type="button"
                            class="flex items-center gap-2 text-left text-xs text-gray-700 whitespace-nowrap hover:text-gray-900"
                            :class="{ 'opacity-40 line-through': hidden[{{ $index }}] }"
                            @click="toggle({{ $index }})"
                        >
                            <span
                                class="inline-block size-2.5 shrink-0 rounded-full"
                                style="background-color: {{ $dataset['borderColor'] }}"
                            ></span>
                            {{ $dataset['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
        <p class="text-sm text-gray-700 mt-4 text-left">Tip: Click on the legend to toggle the visibility of the activity types.</p>
    </div>
</div>
