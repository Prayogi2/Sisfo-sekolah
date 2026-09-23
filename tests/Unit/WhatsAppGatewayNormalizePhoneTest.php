<?php

namespace Tests\Unit;

use App\Services\WhatsAppGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WhatsAppGatewayNormalizePhoneTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function phoneProvider(): array
    {
        return [
            'leading zero becomes 62' => ['081234567890', '6281234567890'],
            'already has country code' => ['6281234567890', '6281234567890'],
            'plus prefix with spaces and dashes' => ['+62 812-3456-7890', '6281234567890'],
            'no country code and no leading zero' => ['81234567890', '6281234567890'],
            'null is null' => [null, null],
            'empty string is null' => ['', null],
            'only symbols is null' => ['+-- ', null],
        ];
    }

    #[DataProvider('phoneProvider')]
    public function test_it_normalizes_phone_numbers_to_the_62_country_code_format(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, (new WhatsAppGateway)->normalizePhone($input));
    }
}
