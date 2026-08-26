<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_user_without_assigned_merchant_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.dashboard'))
            ->assertOk();
    }

    public function test_merchant_user_without_assigned_merchant_can_open_vouchers_without_forbidden(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $response = $this->actingAs($user)
            ->get(route('merchant.vouchers.index'));

        $this->assertNotSame(403, $response->status());
        $this->assertTrue(
            $response->isRedirect(route('merchant.dashboard')) || $response->isOk()
        );
    }

    public function test_non_merchant_user_cannot_access_merchant_dashboard(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.dashboard'))
            ->assertForbidden();
    }
}
