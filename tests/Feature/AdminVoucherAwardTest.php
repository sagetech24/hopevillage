<?php

namespace Tests\Feature;

use App\Livewire\AdminVouchers\Profile;
use App\Models\AdminVoucher;
use App\Models\PointLog;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVoucherAwardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected AdminVoucher $voucher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $this->member = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 500,
        ]);

        $this->voucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-TEST001',
            'name' => 'Volunteer Reward',
            'description' => 'Thank you voucher',
            'points_cost' => 100,
            'amount_cost' => 10.00,
            'is_active' => true,
            'usage_limit' => 5,
            'usage_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_admin_can_award_voucher_to_member_without_deducting_points(): void
    {
        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openAwardModal')
            ->set('selectedMemberIds', [$this->member->id])
            ->set('awardReason', 'Volunteered at community event')
            ->call('awardToMembers')
            ->assertDispatched('notify', type: 'success');

        $this->member->refresh();
        $this->voucher->refresh();

        $this->assertTrue(
            $this->member->adminVouchers()
                ->where('admin_vouchers.id', $this->voucher->id)
                ->wherePivot('status', 'claimed')
                ->exists()
        );
        $this->assertSame(1, $this->voucher->usage_count);
        $this->assertSame(500, $this->member->total_points);

        $log = PointLog::query()
            ->where('user_id', $this->member->id)
            ->whereHas('activityType', fn ($q) => $q->where('name', PointsService::ACTIVITY_ADMIN_AWARD_ADMIN_VOUCHER))
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(0, $log->points);
        $this->assertStringContainsString($this->voucher->voucher_code, $log->description);
        $this->assertStringContainsString('Volunteered at community event', $log->description);
        $this->assertStringContainsString($this->admin->name, $log->description);
    }

    public function test_admin_can_bulk_award_voucher_to_multiple_members(): void
    {
        $memberTwo = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 200,
        ]);
        $memberThree = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 300,
        ]);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openAwardModal')
            ->set('selectedMemberIds', [$this->member->id, $memberTwo->id, $memberThree->id])
            ->set('awardReason', 'Event volunteers')
            ->call('awardToMembers')
            ->assertDispatched('notify', type: 'success');

        $this->voucher->refresh();
        $this->assertSame(3, $this->voucher->usage_count);

        foreach ([$this->member, $memberTwo, $memberThree] as $member) {
            $member->refresh();
            $this->assertTrue(
                $member->adminVouchers()
                    ->where('admin_vouchers.id', $this->voucher->id)
                    ->wherePivot('status', 'claimed')
                    ->exists()
            );
            $this->assertSame(
                1,
                PointLog::query()
                    ->where('user_id', $member->id)
                    ->where('points', 0)
                    ->whereHas('activityType', fn ($q) => $q->where('name', PointsService::ACTIVITY_ADMIN_AWARD_ADMIN_VOUCHER))
                    ->count()
            );
        }

        $this->assertSame(500, $this->member->total_points);
        $this->assertSame(200, $memberTwo->total_points);
        $this->assertSame(300, $memberThree->total_points);
    }

    public function test_award_rejects_when_usage_limit_reached(): void
    {
        $this->voucher->update([
            'usage_limit' => 1,
            'usage_count' => 1,
        ]);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openAwardModal')
            ->set('selectedMemberIds', [$this->member->id])
            ->assertSet('selectedMemberIds', [])
            ->assertDispatched('notify', type: 'error');

        $this->assertFalse(
            $this->member->adminVouchers()->where('admin_vouchers.id', $this->voucher->id)->exists()
        );
    }

    public function test_bulk_award_rejects_when_not_enough_remaining_slots(): void
    {
        $memberTwo = User::factory()->create(['user_type' => 'member']);

        $this->voucher->update([
            'usage_limit' => 1,
            'usage_count' => 0,
        ]);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openAwardModal')
            ->set('selectedMemberIds', [$this->member->id, $memberTwo->id])
            ->assertSet('selectedMemberIds', [$this->member->id])
            ->assertDispatched('notify', type: 'error');

        $this->assertFalse(
            $this->member->adminVouchers()->where('admin_vouchers.id', $this->voucher->id)->exists()
        );
        $this->assertFalse(
            $memberTwo->adminVouchers()->where('admin_vouchers.id', $this->voucher->id)->exists()
        );
        $this->assertSame(0, $this->voucher->fresh()->usage_count);
    }

    public function test_award_rejects_duplicate_attach(): void
    {
        $this->member->adminVouchers()->attach($this->voucher->id, [
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openAwardModal')
            ->set('selectedMemberIds', [$this->member->id])
            ->call('awardToMembers')
            ->assertDispatched('notify', type: 'error');
    }

    public function test_award_rejects_non_member_user_type(): void
    {
        $merchantUser = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openAwardModal')
            ->set('selectedMemberIds', [$merchantUser->id])
            ->call('awardToMembers')
            ->assertDispatched('notify', type: 'error');

        $this->assertFalse(
            $merchantUser->adminVouchers()->where('admin_vouchers.id', $this->voucher->id)->exists()
        );
    }

    public function test_selection_is_capped_to_remaining_vouchers(): void
    {
        $memberTwo = User::factory()->create(['user_type' => 'member']);
        $memberThree = User::factory()->create(['user_type' => 'member']);

        $this->voucher->update([
            'usage_limit' => 2,
            'usage_count' => 1,
        ]);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openAwardModal')
            ->set('selectedMemberIds', [$this->member->id, $memberTwo->id, $memberThree->id])
            ->assertSet('selectedMemberIds', [$this->member->id])
            ->assertDispatched('notify', type: 'error');
    }
}
