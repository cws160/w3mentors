<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

class ThemeService
{
    private const BORDER_ROUNDED = '2';

    /**
     * Theme tokens for React: radius, gradients, footer (primary/secondary stay in compiled SCSS brand).
     */
    public function cssRadiusVariables(?int $themeId = null): string
    {
        if ($themeId === null) {
            $themeId = (int) (Configuration::getValue('CONF_ACTIVE_THEME') ?: 1);
        }

        $theme = DB::table('tbl_themes')
            ->where('theme_id', $themeId)
            ->first([
                'theme_gradient_primary_color',
                'theme_gradient_secondary_color',
                'theme_footer_color',
                'theme_footer_inverse_color',
                'theme_borders_style',
            ]);

        if (!$theme) {
            return '';
        }

        $lines = array_merge(
            $this->radiusVariables($themeId, $theme),
            [
                '--color-gradient-1' => '#'.($theme->theme_gradient_primary_color ?: 'ffffff'),
                '--color-gradient-2' => '#'.($theme->theme_gradient_secondary_color ?: 'ffffff'),
                '--color-dark-blue' => '#'.$theme->theme_footer_color,
                '--color-dark-blue-inverse' => '#'.($theme->theme_footer_inverse_color ?: 'ffffff'),
            ]
        );

        if ($lines === []) {
            return '';
        }

        return $this->formatRootBlock($lines);
    }

    public function cssVariables(?int $themeId = null): string
    {
        if ($themeId === null) {
            $themeId = (int) (Configuration::getValue('CONF_ACTIVE_THEME') ?: 1);
        }

        $theme = DB::table('tbl_themes')
            ->where('theme_id', $themeId)
            ->first([
                'theme_primary_color',
                'theme_primary_inverse_color',
                'theme_secondary_color',
                'theme_secondary_inverse_color',
                'theme_footer_color',
                'theme_footer_inverse_color',
                'theme_gradient_primary_color',
                'theme_gradient_secondary_color',
                'theme_borders_style',
            ]);

        if (!$theme) {
            return '';
        }

        $vars = [
            '--color-primary' => '#'.$theme->theme_primary_color,
            '--color-secondary' => '#'.$theme->theme_secondary_color,
            '--color-primary-inverse' => '#'.($theme->theme_primary_inverse_color ?: 'ffffff'),
            '--color-secondary-inverse' => '#'.($theme->theme_secondary_inverse_color ?: 'ffffff'),
            '--color-dark-blue' => '#'.$theme->theme_footer_color,
            '--color-dark-blue-inverse' => '#'.($theme->theme_footer_inverse_color ?: 'ffffff'),
            '--color-gradient-1' => '#'.($theme->theme_gradient_primary_color ?: 'ffffff'),
            '--color-gradient-2' => '#'.($theme->theme_gradient_secondary_color ?: 'ffffff'),
        ];

        return $this->formatRootBlock(array_merge($vars, $this->radiusVariables($themeId, $theme)));
    }

    /**
     * @return array<string, string>
     */
    private function radiusVariables(?int $themeId = null, ?object $theme = null): array
    {
        if ($themeId === null) {
            $themeId = (int) (Configuration::getValue('CONF_ACTIVE_THEME') ?: 1);
        }

        $theme ??= DB::table('tbl_themes')
            ->where('theme_id', $themeId)
            ->first(['theme_borders_style']);

        if (!$theme) {
            return [];
        }

        return match ((string) $theme->theme_borders_style) {
            '1' => [
                '--radius' => '0px',
                '--radius-sm' => '0px',
                '--radius-md' => '0px',
                '--radius-lg' => '0px',
                '--radius-xl' => '0px',
                '--radius-xxxl' => '0px',
            ],
            '3' => [
                '--radius' => '24px',
                '--radius-sm' => '12px',
                '--radius-md' => '24px',
                '--radius-lg' => '30px',
                '--radius-xl' => '70px',
                '--radius-xxxl' => '100px',
            ],
            '4' => [
                '--radius' => '8px',
                '--radius-sm' => '4px',
                '--radius-md' => '12px',
                '--radius-lg' => '16px',
                '--radius-xl' => '20px',
                '--radius-xxxl' => '24px',
            ],
            default => [
                '--radius' => '12px',
                '--radius-sm' => '8px',
                '--radius-md' => '16px',
                '--radius-lg' => '20px',
                '--radius-xl' => '24px',
                '--radius-xxxl' => '32px',
            ],
        };
    }

    /**
     * @param array<string, string> $lines
     */
    private function formatRootBlock(array $lines): string
    {
        $css = ":root {\n";
        foreach ($lines as $name => $value) {
            $css .= "\t{$name}: {$value};\n";
        }
        $css .= '}';

        return $css;
    }
}
