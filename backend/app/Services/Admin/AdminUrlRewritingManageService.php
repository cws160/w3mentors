<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminUrlRewritingManageService
{
    /** @return array<string, mixed>|null */
    public function form(int $seoUrlId): ?array
    {
        if ($seoUrlId <= 0) {
            return [
                'seourl_id' => 0,
                'seourl_original' => '',
                'seourl_httpcode' => 301,
                'seourl_custom' => [],
                'http_codes' => $this->httpCodes(),
                'languages' => $this->languages(),
            ];
        }

        $original = DB::table('tbl_seo_urls')->where('seourl_id', $seoUrlId)->first();
        if ($original === null) {
            return null;
        }

        $custom = DB::table('tbl_seo_urls')
            ->where('seourl_original', $original->seourl_original)
            ->pluck('seourl_custom', 'seourl_lang_id')
            ->all();

        return [
            'seourl_id' => (int) $original->seourl_id,
            'seourl_original' => (string) $original->seourl_original,
            'seourl_httpcode' => (int) $original->seourl_httpcode,
            'seourl_custom' => array_map('strval', $custom),
            'http_codes' => $this->httpCodes(),
            'languages' => $this->languages(),
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function setup(array $data): void
    {
        $seoUrlId = (int) ($data['seourl_id'] ?? 0);
        $original = trim((string) ($data['seourl_original'] ?? ''));
        $httpCode = (int) ($data['seourl_httpcode'] ?? 0);
        /** @var array<int|string, string> $customUrls */
        $customUrls = (array) ($data['seourl_custom'] ?? []);

        if ($original === '') {
            throw new \InvalidArgumentException('Original URL is required');
        }

        if (! in_array($httpCode, [301, 302], true)) {
            throw new \InvalidArgumentException('HTTP code is required');
        }

        if ($customUrls === []) {
            throw new \InvalidArgumentException('Custom URL is required');
        }

        DB::transaction(function () use ($seoUrlId, $original, $httpCode, $customUrls) {
            if ($seoUrlId > 0) {
                $existing = DB::table('tbl_seo_urls')->where('seourl_id', $seoUrlId)->first();
                if ($existing === null) {
                    throw new \InvalidArgumentException('Invalid request');
                }
                if ($existing->seourl_original !== $original) {
                    DB::table('tbl_seo_urls')
                        ->where('seourl_original', $existing->seourl_original)
                        ->delete();
                }
            }

            DB::table('tbl_seo_urls')->where('seourl_original', $original)->delete();

            foreach ($customUrls as $langId => $url) {
                $custom = $this->seoUrl((string) $url);
                if ($custom === '' || $custom === $original) {
                    throw new \InvalidArgumentException('Custom URL must differ from the original URL');
                }

                DB::table('tbl_seo_urls')->insert([
                    'seourl_lang_id' => (int) $langId,
                    'seourl_original' => $original,
                    'seourl_httpcode' => $httpCode,
                    'seourl_custom' => $custom,
                ]);
            }
        });
    }

    public function delete(int $seoUrlId): bool
    {
        $row = DB::table('tbl_seo_urls')->where('seourl_id', $seoUrlId)->first(['seourl_original']);
        if ($row === null) {
            return false;
        }

        DB::table('tbl_seo_urls')->where('seourl_original', $row->seourl_original)->delete();

        return true;
    }

    /** @return array<int, array{value: int, label: string}> */
    private function httpCodes(): array
    {
        return [
            ['value' => 301, 'label' => '301 Redirect Permanently'],
            ['value' => 302, 'label' => '302 Redirect Temporary'],
        ];
    }

    /** @return array<int, array{id: int, name: string}> */
    private function languages(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function seoUrl(string $string): string
    {
        $string = trim($string);
        $string = preg_replace('/[\s,<>\/"&#%+?$@=]/', '-', $string) ?? $string;
        $string = preg_replace('/[\s-]+/', '-', $string) ?? $string;
        $string = preg_replace('/-+/', '-', $string) ?? $string;

        return trim($string, '-');
    }
}
