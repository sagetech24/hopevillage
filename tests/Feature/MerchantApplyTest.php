<?php

namespace Tests\Feature;

use App\Livewire\Merchant\Apply;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantApplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_apply_page_can_be_rendered(): void
    {
        $this->get(route('merchant.apply'))
            ->assertOk()
            ->assertSeeLivewire(Apply::class);
    }

    public function test_merchant_can_submit_application(): void
    {
        Livewire::test(Apply::class)
            ->set('name', 'Test Kopitiam')
            ->set('description', 'Local coffee shop')
            ->set('contact_name', 'Alice Tan')
            ->set('phone', '91234567')
            ->set('email', 'alice@example.com')
            ->set('address', '7 Kaki Bukit Avenue 3')
            ->set('unitNumber', '01-02')
            ->set('city', 'Singapore')
            ->set('postal_code', '415814')
            ->set('password', 'SecurePass1')
            ->set('password_confirmation', 'SecurePass1')
            ->set('terms', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showSuccess', true);

        $this->assertDatabaseHas('merchants', [
            'name' => 'Test Kopitiam',
            'contact_name' => 'Alice Tan',
            'phone' => '+6591234567',
            'email' => 'alice@example.com',
            'address' => '7 Kaki Bukit Avenue 3 #01-02',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Alice Tan',
            'email' => 'alice@example.com',
            'whatsapp_number' => '+6591234567',
            'user_type' => 'merchant_user',
        ]);

        $merchant = Merchant::where('name', 'Test Kopitiam')->first();
        $user = User::where('whatsapp_number', '+6591234567')->first();

        $this->assertNotNull($merchant);
        $this->assertNotNull($user);
        $this->assertTrue($merchant->users()->where('user_id', $user->id)->exists());
    }

    public function test_merchant_application_requires_core_fields(): void
    {
        Livewire::test(Apply::class)
            ->set('name', '')
            ->set('contact_name', '')
            ->set('phone', '')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->set('terms', false)
            ->call('submit')
            ->assertHasErrors([
                'name',
                'contact_name',
                'phone',
                'password',
                'terms',
            ])
            ->assertSet('showSuccess', false);

        $this->assertDatabaseCount('merchants', 0);
    }

    public function test_merchant_application_rejects_duplicate_phone(): void
    {
        User::factory()->create([
            'whatsapp_number' => '+6591234567',
        ]);

        Livewire::test(Apply::class)
            ->set('name', 'Another Shop')
            ->set('contact_name', 'Bob Lim')
            ->set('phone', '91234567')
            ->set('password', 'SecurePass1')
            ->set('password_confirmation', 'SecurePass1')
            ->set('terms', true)
            ->call('submit')
            ->assertHasErrors(['phone'])
            ->assertSet('showSuccess', false);
    }

    public function test_merchant_application_rejects_mismatched_password(): void
    {
        Livewire::test(Apply::class)
            ->set('name', 'Test Shop')
            ->set('contact_name', 'Carol Ong')
            ->set('phone', '98765432')
            ->set('password', 'SecurePass1')
            ->set('password_confirmation', 'DifferentPass1')
            ->set('terms', true)
            ->call('submit')
            ->assertHasErrors(['password'])
            ->assertSet('showSuccess', false);
    }
}
