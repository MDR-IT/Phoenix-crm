<?php

namespace Tests\Unit;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    #[DataProvider('phoneProvider')]
    public function test_it_normalizes_kenyan_phones(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNormalizer::normalize($input));
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function phoneProvider(): array
    {
        return [
            'local 07' => ['0712345678', '254712345678'],
            'plus 254' => ['+254712345678', '254712345678'],
            'spaces' => ['07 123 45678', '254712345678'],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }
}
