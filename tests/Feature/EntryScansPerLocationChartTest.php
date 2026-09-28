<?php

namespace Tests\Feature;

use App\Livewire\Admin\EntryScansPerLocation;
use App\Models\ActivityType;
use App\Models\AdminVoucher;
use App\Models\Location;
use App\Models\MemberActivity;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EntryScansPerLocationChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_includes_merchant_and_admin_voucher_redemptions(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $member = User::factory()->create(['user_type' => 'member']);

        $this->createEntryActivity($member, now());

        $merchantVoucher = $this->createMerchantVoucher();
        $member->vouchers()->attach($merchantVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now(),
        ]);

        $adminVoucher = $this->createAdminVoucher($admin);
        $member->claimAdminVoucherAssignment($adminVoucher, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now(),
        ]);

        $data = Livewire::test(EntryScansPerLocation::class)
            ->viewData('activityTypesData');

        $voucherDataset = $this->datasetByLabel($data, 'Voucher redemption');
        $this->assertNotNull($voucherDataset);
        $this->assertSame(2, $this->todayCount($voucherDataset));
        $this->assertGreaterThan(0, $this->todayCount($this->datasetByLabel($data, 'entry')));
    }

    public function test_chart_excludes_claimed_and_out_of_range_redemptions(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $member = User::factory()->create(['user_type' => 'member']);

        $claimed = $this->createMerchantVoucher('VOU-CLAIM01');
        $member->vouchers()->attach($claimed->id, [
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

        $old = $this->createMerchantVoucher('VOU-OLD001');
        $member->vouchers()->attach($old->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDays(45),
            'redeemed_at' => now()->subDays(40),
        ]);

        $inRange = $this->createAdminVoucher($admin, 'AVOU-IN001');
        $member->claimAdminVoucherAssignment($inRange, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDays(3),
            'redeemed_at' => now()->subDays(2),
        ]);

        $data = Livewire::test(EntryScansPerLocation::class)
            ->viewData('activityTypesData');

        $voucherDataset = $this->datasetByLabel($data, 'Voucher redemption');
        $this->assertNotNull($voucherDataset);
        $this->assertSame(0, $this->todayCount($voucherDataset));
        $this->assertSame(1, array_sum($voucherDataset['data']));
        $this->assertSame(1, $this->countOnDate($voucherDataset, now()->subDays(2)));
    }

    public function test_chart_does_not_duplicate_member_redeem_voucher_activity_type(): void
    {
        $member = User::factory()->create(['user_type' => 'member']);

        $redeemType = ActivityType::query()->create([
            'name' => PointsService::ACTIVITY_VOUCHER_REDEEM,
            'description' => 'Member redeemed voucher',
            'is_active' => true,
        ]);

        $location = Location::query()->create([
            'name' => 'Test Location',
            'is_active' => true,
            'location_code' => 'LOC-CHART01',
        ]);

        MemberActivity::query()->create([
            'user_id' => $member->id,
            'activity_type_id' => $redeemType->id,
            'location_id' => $location->id,
            'activity_time' => now(),
        ]);

        $voucher = $this->createMerchantVoucher();
        $member->vouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now(),
        ]);

        $data = Livewire::test(EntryScansPerLocation::class)
            ->viewData('activityTypesData');

        $labels = array_column($data['datasets'], 'label');
        $this->assertContains('Voucher redemption', $labels);
        $this->assertNotContains(PointsService::ACTIVITY_VOUCHER_REDEEM, $labels);
        $this->assertSame(1, $this->todayCount($this->datasetByLabel($data, 'Voucher redemption')));
    }

    /**
     * @param  array{labels: array<int, string>, datasets: array<int, array<string, mixed>>}  $data
     * @return array<string, mixed>|null
     */
    private function datasetByLabel(array $data, string $label): ?array
    {
        foreach ($data['datasets'] as $dataset) {
            if (($dataset['label'] ?? null) === $label) {
                return $dataset;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function todayCount(array $dataset): int
    {
        return $this->countOnDate($dataset, now());
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function countOnDate(array $dataset, $day): int
    {
        $target = $day->copy()->format('Y-m-d');

        for ($i = 29; $i >= 0; $i--) {
            if (now()->subDays($i)->format('Y-m-d') === $target) {
                return (int) ($dataset['data'][29 - $i] ?? 0);
            }
        }

        return 0;
    }

    private function createEntryActivity(User $member, $activityTime): void
    {
        $activityType = ActivityType::query()->create([
            'name' => 'entry',
            'description' => 'entry',
            'is_active' => true,
        ]);

        $location = Location::query()->create([
            'name' => 'Test Location',
            'is_active' => true,
            'location_code' => 'LOC-CHART02',
        ]);

        MemberActivity::query()->create([
            'user_id' => $member->id,
            'activity_type_id' => $activityType->id,
            'location_id' => $location->id,
            'activity_time' => $activityTime,
        ]);
    }

    private function createMerchantVoucher(string $code = 'VOU-CHART1'): Voucher
    {
        $merchant = Merchant::query()->create([
            'name' => 'Chart Merchant',
            'email' => 'chart-merchant-'.uniqid().'@example.com',
            'is_active' => true,
        ]);

        return Voucher::query()->create([
            'voucher_code' => $code,
            'name' => 'Chart Merchant Voucher',
            'merchant_id' => $merchant->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);
    }

    private function createAdminVoucher(User $admin, string $code = 'AVOU-CHART1'): AdminVoucher
    {
        return AdminVoucher::query()->create([
            'voucher_code' => $code,
            'name' => 'Chart Admin Voucher',
            'points_cost' => 50,
            'amount_cost' => 5.00,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
    }
}
