<?php

namespace Tests\Unit;

use App\Support\PasswordResetDeliveryErrors;
use PHPUnit\Framework\TestCase;

class PasswordResetDeliveryErrorsTest extends TestCase
{
    public function test_maps_twilio_landline_error(): void
    {
        $message = PasswordResetDeliveryErrors::forSmsResult([
            'success' => false,
            'code' => 21635,
            'message' => "'To' number cannot be a landline",
        ], 'twilio');

        $this->assertStringContainsString('landline', $message);
    }

    public function test_maps_twilio_geo_permission_error(): void
    {
        $message = PasswordResetDeliveryErrors::forSmsResult([
            'success' => false,
            'code' => 21408,
        ], 'twilio');

        $this->assertStringContainsString('region', $message);
    }

    public function test_maps_infobip_not_configured_error(): void
    {
        $message = PasswordResetDeliveryErrors::forSmsResult([
            'success' => false,
            'message' => 'SMS service is not configured.',
        ], 'infobip');

        $this->assertStringContainsString('not configured', strtolower($message));
    }
}
