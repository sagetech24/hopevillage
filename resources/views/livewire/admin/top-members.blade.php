<div class="card bg-white shadow border border-gray-300">
    <style>
        .top-members-rank-row td {
            background-color: rgb(249 115 22 / var(--rank-hl)) !important;
        }
        .top-members-rank-row:hover td {
            background-color: rgb(249 115 22 / var(--rank-hl)) !important;
        }
    </style>
    <div class="card-body" style="padding:0px !important;">
        <h2 class="card-title text-gray-800 px-4 pt-4 pb-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 stroke-current">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
            </svg>
            Top Ranked Members
        </h2>
        <div class="overflow-x-auto scrollbar-hide">
            <table class="table">
                <tbody>
                    @forelse($topMembers as $index => $member)
                        @php
                            $rank = $index + 1;
                            // Rank 1 is strongest (~24% orange); rank 10 is fully transparent.
                            $highlight = number_format(max(0, (10 - $rank) / 9) * 0.24, 3);
                        @endphp
                        <tr class="top-members-rank-row" style="--rank-hl: {{ $highlight }};">
                            <td>
                                <p class="bg-gray-500 text-white px-2 py-1.5 flex items-center justify-center text-sm rounded-full">{{ $rank }}</p>
                            </td>
                            <td>
                                <a href="{{ route('admin.members.profile', $member->qr_code) }}" class="font-medium transition-all duration-300 hover:underline text-orange-600 hover:text-orange-700 cursor-pointer">{{ $member->name }}</a>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 stroke-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-gray-500 text-sm font-semibold">{{ number_format($member->total_points ?? 0) }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-base-content/70">No members found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
