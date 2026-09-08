<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MemberRankingService
{
    /**
     * Rank among members by total_points DESC, then created_at ASC, then id ASC.
     */
    public function rankFor(User $user): int
    {
        if ($user->user_type !== 'member') {
            return 0;
        }

        $points = (int) ($user->total_points ?? 0);

        return $this->membersQuery()
            ->where(function (Builder $q) use ($user, $points) {
                $q->where('total_points', '>', $points)
                    ->orWhere(function (Builder $q) use ($user, $points) {
                        $q->where('total_points', $points)
                            ->where('created_at', '<', $user->created_at);
                    })
                    ->orWhere(function (Builder $q) use ($user, $points) {
                        $q->where('total_points', $points)
                            ->where('created_at', $user->created_at)
                            ->where('id', '<', $user->id);
                    });
            })
            ->count() + 1;
    }

    public function memberCount(): int
    {
        return $this->membersQuery()->count();
    }

    /**
     * @return Collection<int, User>
     */
    public function topMembers(int $limit = 5): Collection
    {
        return $this->membersQuery()
            ->orderByDesc('total_points')
            ->orderBy('created_at')
            ->orderBy('id')
            ->take($limit)
            ->get();
    }

    protected function membersQuery(): Builder
    {
        return User::query()->where('user_type', 'member');
    }
}
