<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminThemesManageService
{
  private const THEME_IS_DEFAULT_NO = 0;

  /** @return array<string, mixed> */
  public function form(int $themeId, string $action = 'update'): array
  {
    if ($themeId < 1) {
      throw new \InvalidArgumentException('Invalid theme');
    }

    $columns = $action === 'clone'
      ? [
          'theme_title',
          'theme_primary_color',
          'theme_primary_inverse_color',
          'theme_secondary_color',
          'theme_secondary_inverse_color',
          'theme_footer_color',
          'theme_footer_inverse_color',
          'theme_gradient_primary_color',
          'theme_gradient_secondary_color',
          'theme_borders_style',
          'theme_niche',
        ]
      : ['*'];

    $row = DB::table('tbl_themes')->where('theme_id', $themeId)->first($columns);
    if (!$row) {
      throw new \InvalidArgumentException('Theme not found');
    }

    $data = (array) $row;
    $data['theme_id'] = $action === 'clone' ? 0 : $themeId;

    return [
      'theme' => $data,
      'border_styles' => $this->borderStyles(),
      'action' => $action,
    ];
  }

  /** @param array<string, mixed> $payload */
  public function setup(array $payload): int
  {
    $themeId = (int) ($payload['theme_id'] ?? 0);
    $now = now()->format('Y-m-d H:i:s');

    $values = [
      'theme_title' => trim((string) ($payload['theme_title'] ?? '')),
      'theme_primary_color' => $this->normalizeHex((string) ($payload['theme_primary_color'] ?? '')),
      'theme_primary_inverse_color' => $this->normalizeHex((string) ($payload['theme_primary_inverse_color'] ?? '')),
      'theme_secondary_color' => $this->normalizeHex((string) ($payload['theme_secondary_color'] ?? '')),
      'theme_secondary_inverse_color' => $this->normalizeHex((string) ($payload['theme_secondary_inverse_color'] ?? '')),
      'theme_footer_color' => $this->normalizeHex((string) ($payload['theme_footer_color'] ?? '')),
      'theme_footer_inverse_color' => $this->normalizeHex((string) ($payload['theme_footer_inverse_color'] ?? '')),
      'theme_gradient_primary_color' => $this->normalizeHex((string) ($payload['theme_gradient_primary_color'] ?? '')),
      'theme_gradient_secondary_color' => $this->normalizeHex((string) ($payload['theme_gradient_secondary_color'] ?? '')),
      'theme_borders_style' => (int) ($payload['theme_borders_style'] ?? 1),
      'theme_niche' => (string) ($payload['theme_niche'] ?? 'onlinetutoring'),
    ];

    if ($values['theme_title'] === '') {
      throw new \InvalidArgumentException('Title is required');
    }

    if ($themeId > 0) {
      DB::table('tbl_themes')->where('theme_id', $themeId)->update($values);

      return $themeId;
    }

    $values['theme_is_default'] = self::THEME_IS_DEFAULT_NO;
    $values['theme_created'] = $now;
    $themeId = (int) DB::table('tbl_themes')->insertGetId($values);

    return $themeId;
  }

  public function activate(int $themeId): void
  {
    if ($themeId < 1 || ! DB::table('tbl_themes')->where('theme_id', $themeId)->exists()) {
      throw new \InvalidArgumentException('Invalid theme');
    }

    DB::table('tbl_configurations')
      ->where('conf_name', 'CONF_ACTIVE_THEME')
      ->update(['conf_val' => (string) $themeId]);
  }

  public function delete(int $themeId): void
  {
    if ($themeId < 1) {
      throw new \InvalidArgumentException('Invalid theme');
    }

    $row = DB::table('tbl_themes')->where('theme_id', $themeId)->first(['theme_id', 'theme_is_default']);
    if (! $row) {
      throw new \InvalidArgumentException('Theme not found');
    }

    $activeThemeId = (int) DB::table('tbl_configurations')
      ->where('conf_name', 'CONF_ACTIVE_THEME')
      ->value('conf_val');

    if ($activeThemeId === $themeId) {
      throw new \InvalidArgumentException('Cannot delete active theme');
    }

    if ((int) $row->theme_is_default !== self::THEME_IS_DEFAULT_NO) {
      throw new \InvalidArgumentException('Cannot delete default theme');
    }

    DB::table('tbl_themes')->where('theme_id', $themeId)->delete();
  }

  /** @return array<int, array{value: int, label: string}> */
  private function borderStyles(): array
  {
    return [
      ['value' => 1, 'label' => 'Sharp edges'],
      ['value' => 2, 'label' => 'Round edges (slightly rounded)'],
      ['value' => 3, 'label' => 'Pill style (fully rounded)'],
      ['value' => 4, 'label' => 'Leaf style'],
    ];
  }

  private function normalizeHex(string $value): string
  {
    return ltrim(trim($value), '#');
  }
}
