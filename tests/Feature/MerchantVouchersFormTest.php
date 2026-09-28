<?php

namespace Tests\Feature;

use App\Livewire\Merchant\Vouchers\Form;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantVouchersFormTest extends TestCase
{
    use RefreshDatabase;

    private function actingMerchant(bool $merchantActive = true): array
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => $merchantActive,
        ]);

        $user->merchants()->attach($merchant->id, ['is_default' => true]);
        $user->update(['current_merchant_id' => $merchant->id]);

        return [$user, $merchant];
    }

    private function createVoucher(Merchant $merchant, array $attributes = []): Voucher
    {
        return Voucher::query()->create(array_merge([
            'merchant_id' => $merchant->id,
            'name' => 'Lunch 20% Off',
            'description' => 'Weekday lunch discount',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'usage_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ], $attributes));
    }

    public function test_create_form_renders_v2_layout_and_pending_approval_status(): void
    {
        [$user] = $this->actingMerchant();

        $this->actingAs($user)
            ->get(route('merchant.vouchers.create'))
            ->assertOk()
            ->assertSee('Merchant Portal')
            ->assertSee('Create Voucher')
            ->assertSee('Pending Approval')
            ->assertSee('Admin approval required')
            ->assertSee('Basic Information')
            ->assertSee('Offer Details')
            ->assertSee('Validity Period')
            ->assertSee('Visibility')
            ->assertSee('Claim Limit')
            ->assertSee('Create Voucher', false)
            ->assertDontSee('Manage your vouchers here');
    }

    public function test_edit_form_shows_correct_status_labels(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $active = $this->createVoucher($merchant, [
            'name' => 'Live Offer',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.edit', $active->voucher_code))
            ->assertOk()
            ->assertSee('Live Offer')
            ->assertSee('Active')
            ->assertSee('Save Changes')
            ->assertSee($active->voucher_code)
            ->assertDontSee('Admin approval required');

        $pending = $this->createVoucher($merchant, [
            'name' => 'Awaiting Review',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.edit', $pending->voucher_code))
            ->assertOk()
            ->assertSee('Awaiting Review')
            ->assertSee('Pending Approval')
            ->assertSee('Admin approval required');
    }

    public function test_create_voucher_via_livewire_form_v2(): void
    {
        [$user] = $this->actingMerchant();

        Livewire::actingAs($user)
            ->test(Form::class)
            ->set('name', 'Weekend Special')
            ->set('description', 'Saturday discount')
            ->set('discount_type', 'percentage')
            ->set('discount_value', 15)
            ->set('usage_limit', 50)
            ->call('save')
            ->assertRedirect(route('merchant.vouchers.index'));

        $this->assertDatabaseHas('vouchers', [
            'name' => 'Weekend Special',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'is_active' => false,
        ]);
    }

    public function test_free_item_deal_type_locks_value_to_100_percent(): void
    {
        [$user] = $this->actingMerchant();

        Livewire::actingAs($user)
            ->test(Form::class)
            ->set('name', 'Free Drink')
            ->set('description', 'Complimentary soft drink')
            ->set('discount_type', 'item')
            ->assertSet('discount_value', 100)
            ->assertSee('Read-Only')
            ->call('save')
            ->assertRedirect(route('merchant.vouchers.index'));

        $this->assertDatabaseHas('vouchers', [
            'name' => 'Free Drink',
            'discount_type' => 'item',
            'discount_value' => 100,
            'is_active' => false,
        ]);
    }
}
