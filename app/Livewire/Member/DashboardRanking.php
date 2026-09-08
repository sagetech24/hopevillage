<?php

namespace App\Livewire\Member;

use App\Services\MemberRankingService;
use Illuminate\Support\Collection;
use Livewire\Component;

class DashboardRanking extends Component
{
    /**
     * Display variant: "header" (own rank line) or "list" (top members card).
     */
    public string $variant = 'list';

    public function getRankProperty(): int
    {
        $user = auth()->user();

        if (! $user || ! $user->isMember()) {
            return 0;
        }

        return app(MemberRankingService::class)->rankFor($user);
    }

    public function getMemberCountProperty(): int
    {
        return app(MemberRankingService::class)->memberCount();
    }

    /**
     * @return Collection<int, \App\Models\User>
     */
    public function getTopMembersProperty(): Collection
    {
        return app(MemberRankingService::class)->topMembers(5);
    }

    public function render()
    {
        return view('livewire.member.dashboard-ranking');
    }
}
