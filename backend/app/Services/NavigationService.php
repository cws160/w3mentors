<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

class NavigationService
{
    public const TYPE_HEADER = 1;
    public const TYPE_FOOTER_ONE = 2;
    public const TYPE_FOOTER_TWO = 3;
    public const TYPE_FOOTER_THREE = 4;

    /** Legacy NavigationLinks::NAVLINK_TYPE_CMS */
    private const NAVLINK_TYPE_CMS = 0;

    public function getHeader(int $langId = 1): array
    {
        return $this->getByType(self::TYPE_HEADER, $langId);
    }

    public function getFooter(int $langId = 1): array
    {
        return [
            'one' => $this->getByType(self::TYPE_FOOTER_ONE, $langId),
            'two' => $this->getByType(self::TYPE_FOOTER_TWO, $langId),
            'three' => $this->getByType(self::TYPE_FOOTER_THREE, $langId),
        ];
    }

    private function getByType(int $type, int $langId): array
    {
        $rows = DB::table('tbl_navigation_links as link')
            ->join('tbl_navigations as nav', 'nav.nav_id', '=', 'link.nlink_nav_id')
            ->leftJoin('tbl_navigation_links_lang as link_l', function ($join) use ($langId) {
                $join->on('link.nlink_id', '=', 'link_l.nlinklang_nlink_id')
                    ->where('link_l.nlinklang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_navigations_lang as nav_l', function ($join) use ($langId) {
                $join->on('nav.nav_id', '=', 'nav_l.navlang_nav_id')
                    ->where('nav_l.navlang_lang_id', '=', $langId);
            })
            ->where('nav.nav_active', 1)
            ->where('nav.nav_type', $type)
            ->where('link.nlink_deleted', 0)
            ->orderBy('link.nlink_order')
            ->select([
                'nav.nav_id',
                DB::raw('COALESCE(nav_l.nav_name, nav.nav_identifier) as nav_name'),
                'link.nlink_id',
                DB::raw('COALESCE(link_l.nlink_caption, link.nlink_identifier) as caption'),
                'link.nlink_url',
                'link.nlink_target',
                'link.nlink_type',
                'link.nlink_cpage_id',
                'link.nlink_login_protected',
            ])
            ->get();

        $groups = [];
        foreach ($rows as $row) {
            $groups[$row->nav_id]['parent'] = $row->nav_name;
            $groups[$row->nav_id]['pages'][] = [
                'id' => $row->nlink_id,
                'caption' => $row->caption,
                'url' => $this->resolveUrl($row->nlink_url, (int) $row->nlink_type, (int) $row->nlink_cpage_id),
                'target' => $row->nlink_target ?: '_self',
                'login_protected' => (int) $row->nlink_login_protected,
            ];
        }

        return array_values($groups);
    }

    private function resolveUrl(string $url, int $type, int $cpageId): string
    {
        $url = str_replace('{domain}', '/', $url);

        if ($type === self::NAVLINK_TYPE_CMS && $cpageId > 0) {
            return $this->cmsPagePath($cpageId);
        }

        if (!str_starts_with($url, '/')) {
            $url = '/' . ltrim($url, '/');
        }

        $map = [
            'teachers' => '/teachers',
            'courses' => '/courses',
            'groupclasses' => '/group-classes',
            'blog' => '/blog',
            'faq' => '/faq',
            'forum' => '/forum',
            'contact' => '/contact',
            'video-content' => '/video-content',
            'videos' => '/video-content',
            'subscriptionplans' => '/subscription-plans',
        ];

        $path = trim($url, '/');

        return $map[$path] ?? $url;
    }

    public function cmsPagePath(int $cpageId): string
    {
        if ($cpageId <= 0) {
            return '/';
        }

        $termsId = (int) Configuration::getValue('CONF_TERMS_AND_CONDITIONS_PAGE', 2);
        $privacyId = (int) Configuration::getValue('CONF_PRIVACY_POLICY_PAGE', 3);

        return match ($cpageId) {
            1 => '/about',
            $termsId => '/terms-and-conditions',
            $privacyId => '/privacy-policy',
            default => "/cms/{$cpageId}",
        };
    }
}
