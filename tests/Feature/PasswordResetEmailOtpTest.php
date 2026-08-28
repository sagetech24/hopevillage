<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetEmailOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auth.password_reset_skip_delivery' => false,
        ]);
    }

    public function test_email_password_reset_redirects_to_otp_verification_and_sends_mail(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'reset_method' => 'email',
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.reset.verify'));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $this->assertDatabaseHas('otps', [
            'purpose' => Otp::PURPOSE_PASSWORD_RESET,
            'user_id' => $user->id,
            'channel' => 'email',
            'email' => $user->email,
        ]);
    }

    public function test_valid_otp_redirects_to_password_reset_form(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', [
            'reset_method' => 'email',
            'email' => $user->email,
        ])->assertRedirect(route('password.reset.verify'));

        $otp = Otp::query()
            ->where('user_id', $user->id)
            ->where('purpose', Otp::PURPOSE_PASSWORD_RESET)
            ->latest()
            ->first();

        $this->assertNotNull($otp);

        $response = $this->post('/forgot-password/verify-otp', [
            'otp' => $otp->makeVisible(['otp_code'])->otp_code,
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('reset-password', $response->headers->get('Location'));
    }

    public function test_unknown_email_shows_generic_status_without_sending_mail(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        Mail::fake();

        $response = $this->post('/forgot-password', [
            'reset_method' => 'email',
            'email' => 'missing@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $response->assertSessionMissing('errors');

        Mail::assertNothingSent();
    }

    public function test_sms_password_reset_is_temporarily_unavailable(): void
    {
        if (! Features::enabled(Features::resetPasswords())) {
            $this->markTestSkipped('Password updates are not enabled.');
        }

        $user = User::factory()->create([
            'whatsapp_number' => '+6591234567',
        ]);

        $response = $this->post('/forgot-password', [
            'reset_method' => 'sms',
            'sms_number' => '91234567',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('reset');
    }
}
