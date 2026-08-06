<?php

namespace App\Support;

final class Branding
{
    public const NAME = 'w3mentors';

    public const LOGO_URL = '/images/logo.svg';

    /** @var list<string> */
    private const SEARCH = [
        'Yo' . '!Coach',
        'Yo' . 'Coach',
        'Yo' . '-Coach',
        'yo' . '-coach',
        'W3Mentors',
        'W3 Mentors',
    ];

    public static function apply(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        return str_replace(self::SEARCH, self::NAME, $text);
    }

    /** @param  array<string, string>  $labels */
    public static function applyToLabels(array $labels): array
    {
        foreach ($labels as $key => $caption) {
            $labels[$key] = self::apply($caption) ?? $caption;
        }

        return $labels;
    }
}
