<?php

namespace Tests\Feature;

use App\Livewire\Merchant\ProfileModal;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantProfileModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_dashboard_includes_profile_modal(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.dashboard.v2'))
            ->assertOk()
            ->assertSeeLivewire(ProfileModal::class);
    }

    public function test_merchant_user_can_open_profile_with_account_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Store Owner',
            'email' => 'owner@example.com',
            'whatsapp_number' => '+6591234567',
            'user_type' => 'merchant_user',
        ]);

        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => true,
        ]);
        $user->merchants()->attach($merchant->id, ['is_default' => true]);
        $user->update(['current_merchant_id' => $merchant->id]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('open')
            ->assertSet('open', true)
            ->assertSet('email', 'owner@example.com')
            ->assertSee('Store Owner')
            ->assertSee('+6591234567')
            ->assertSee('Kampong Grocer')
            ->assertSee('Update email')
            ->assertSee('Update password');
    }

    public function test_merchant_user_can_update_email_with_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'user_type' => 'merchant_user',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('open')
            ->set('email', 'new-owner@example.com')
            ->set('email_current_password', 'password')
            ->call('updateEmail')
            ->assertHasNoErrors()
            ->assertSee('Email address updated.');

        $this->assertSame('new-owner@example.com', $user->fresh()->email);
    }

    public function test_email_update_requires_the_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'user_type' => 'merchant_user',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('open')
            ->set('email', 'new-owner@example.com')
            ->set('email_current_password', 'wrong-password')
            ->call('updateEmail')
            ->assertHasErrors(['email_current_password']);

        $this->assertSame('owner@example.com', $user->fresh()->email);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'user_type' => 'merchant_user',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('open')
            ->set('email', 'taken@example.com')
            ->set('email_current_password', 'password')
            ->call('updateEmail')
            ->assertHasErrors(['email']);

        $this->assertSame('owner@example.com', $user->fresh()->email);
    }

    public function test_merchant_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('open')
            ->set('current_password', 'password')
            ->set('password', 'new-password1')
            ->set('password_confirmation', 'new-password1')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSee('Password updated.');

        $this->assertTrue(Hash::check('new-password1', $user->fresh()->password));
    }

    public function test_password_update_rejects_a_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('open')
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password1')
            ->set('password_confirmation', 'new-password1')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_non_merchant_user_cannot_open_the_profile_modal(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('open')
            ->assertForbidden();
    }
}
