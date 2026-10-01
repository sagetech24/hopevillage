<?php

namespace Tests\Unit;

use App\Support\MemberNameMask;
use PHPUnit\Framework\TestCase;

class MemberNameMaskTest extends TestCase
{
    public function test_long_latin_name_shows_four_graphemes_and_a_fixed_mask(): void
    {
        $this->assertSame('Marn****', MemberNameMask::mask('Marnelle'));
        $this->assertSame('Mary****', MemberNameMask::mask('Mary-Jane'));
        $this->assertSame("O'Br****", MemberNameMask::mask("O'Brien"));
    }

    public function test_short_names_reveal_only_the_first_grapheme(): void
    {
        $this->assertSame('L****', MemberNameMask::mask('Li'));
        $this->assertSame('A****', MemberNameMask::mask('Ann'));
    }

    public function test_each_word_is_masked_separately(): void
    {
        $this->assertSame('Marn**** A****', MemberNameMask::mask('Marnelle Apat'));
    }

    public function test_empty_names_use_a_fallback(): void
    {
        $this->assertSame('Member', MemberNameMask::mask(null));
        $this->assertSame('Member', MemberNameMask::mask(''));
        $this->assertSame('Member', MemberNameMask::mask('   '));
    }

    public function test_indic_names_keep_grapheme_clusters_intact(): void
    {
        foreach (['नमस्ते', 'মার্নেল'] as $name) {
            $masked = MemberNameMask::mask($name);

            $this->assertTrue(mb_check_encoding($masked, 'UTF-8'));
            $this->assertStringEndsWith('****', $masked);

            $length = grapheme_strlen($name);
            $visibleCount = $length > 4 ? 4 : 1;
            $expectedPrefix = grapheme_substr($name, 0, $visibleCount);
            $prefix = grapheme_substr($masked, 0, grapheme_strlen($masked) - 4);

            $this->assertSame($expectedPrefix, $prefix);
            $this->assertDoesNotMatchRegularExpression('/[\x{094D}\x{09CD}]$/u', $prefix);
        }
    }
}
