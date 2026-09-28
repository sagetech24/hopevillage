<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('member.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_merchant_login_does_not_crash_when_password_hash_is_not_bcrypt(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        DB::table('users')->where('id', $user->id)->update([
            'password' => 'not-a-bcrypt-hash',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertStringNotContainsString(
            'This password does not use the Bcrypt algorithm.',
            $response->exception?->getMessage() ?? (string) $response->getContent()
        );
    }

    public function test_merchant_user_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('merchant.dashboard.v2', absolute: false));
    }
}
