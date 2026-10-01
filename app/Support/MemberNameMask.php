<?php

namespace App\Support;

use Normalizer;

class MemberNameMask
{
    private const VISIBLE_GRAPHEMES = 3;

    private const SHORT_NAME_MAX = 5;

    private const MASK = '****';

    private const FALLBACK = 'Member';

    public static function mask(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return self::FALLBACK;
        }

        if (class_exists(Normalizer::class)) {
            $normalized = Normalizer::normalize($name, Normalizer::FORM_C);

            if (is_string($normalized) && $normalized !== '') {
                $name = $normalized;
            }
        }

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return self::FALLBACK;
        }

        return implode(' ', array_map(self::maskWord(...), $words));
    }

    private static function maskWord(string $word): string
    {
        $length = grapheme_strlen($word);

        if ($length === false || $length < 1) {
            return self::MASK;
        }

        $visibleCount = $length > self::SHORT_NAME_MAX ? self::VISIBLE_GRAPHEMES : 1;
        $prefix = grapheme_substr($word, 0, $visibleCount);

        if (! is_string($prefix) || $prefix === '') {
            return self::MASK;
        }

        return $prefix.self::MASK;
    }
}
