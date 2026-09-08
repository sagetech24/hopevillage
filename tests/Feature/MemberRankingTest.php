<?php

namespace Tests\Feature;

use App\Livewire\Member\DashboardRanking;
use App\Models\User;
use App\Services\MemberRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_higher_points_ranks_above_lower_points(): void
    {
        $low = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 10,
            'created_at' => now()->subDays(10),
        ]);

        $high = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 100,
            'created_at' => now()->subDay(),
        ]);

        $ranking = app(MemberRankingService::class);

        $this->assertSame(1, $ranking->rankFor($high));
        $this->assertSame(2, $ranking->rankFor($low));
    }

    public function test_same_points_earlier_registration_ranks_higher(): void
    {
        $first = User::factory()->create([
            'user_type' => 'member',
            'name' => 'First',
            'total_points' => 50,
            'created_at' => now()->subDays(4),
        ]);

        $second = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Second',
            'total_points' => 50,
            'created_at' => now()->subDays(3),
        ]);

        $third = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Third',
            'total_points' => 50,
            'created_at' => now()->subDays(2),
        ]);

        $fourth = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Fourth',
            'total_points' => 50,
            'created_at' => now()->subDay(),
        ]);

        $ranking = app(MemberRankingService::class);

        $this->assertSame(1, $ranking->rankFor($first));
        $this->assertSame(2, $ranking->rankFor($second));
        $this->assertSame(3, $ranking->rankFor($third));
        $this->assertSame(4, $ranking->rankFor($fourth));

        $top = $ranking->topMembers(5);
        $this->assertSame(
            [$first->id, $second->id, $third->id, $fourth->id],
            $top->pluck('id')->all()
        );
    }

    public function test_admins_and_merchant_users_are_not_counted(): void
    {
        $member = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 20,
            'created_at' => now()->subDay(),
        ]);

        User::factory()->create([
            'user_type' => 'admin',
            'total_points' => 9999,
            'created_at' => now()->subDays(30),
        ]);

        User::factory()->create([
            'user_type' => 'merchant_user',
            'total_points' => 9999,
            'created_at' => now()->subDays(30),
        ]);

        $ranking = app(MemberRankingService::class);

        $this->assertSame(1, $ranking->memberCount());
        $this->assertSame(1, $ranking->rankFor($member));
        $this->assertCount(1, $ranking->topMembers(5));
    }

    public function test_dashboard_shows_authenticated_member_rank(): void
    {
        User::factory()->create([
            'user_type' => 'member',
            'total_points' => 80,
            'created_at' => now()->subDays(5),
        ]);

        $member = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 50,
            'created_at' => now()->subDays(2),
        ]);

        User::factory()->create([
            'user_type' => 'member',
            'total_points' => 50,
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($member);

        $this->get(route('member.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(DashboardRanking::class);

        Livewire::test(DashboardRanking::class, ['variant' => 'header'])
            ->assertSet('variant', 'header')
            ->assertSee('#2')
            ->assertSee('of 3');

        Livewire::test(DashboardRanking::class, ['variant' => 'list'])
            ->assertSee('Top Members')
            ->assertSee('You: #2');
    }
}
