<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminSitemapHtmlService
{
    /** @return array<int, string> */
    public function flatUrls(int $langId): array
    {
        $frontUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');
        $urls = [$frontUrl.'/'];

        foreach ($this->buildGroups($langId, $frontUrl) as $group) {
            foreach ($group['links'] as $link) {
                $urls[] = $link['url'];
            }
        }

        return array_values(array_unique($urls));
    }

    /** @return array{sections: array<int, array{language: ?string, groups: array<int, array{title: string, links: array<int, array{label: string, url: string}>}>}>, public_url: string} */
    public function sections(int $langId): array
    {
        $frontUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');
        $groups = $this->buildGroups($langId, $frontUrl);

        return [
            'sections' => [
                [
                    'language' => null,
                    'groups' => $groups,
                ],
            ],
            'public_url' => $frontUrl.'/sitemap',
        ];
    }

    /** @return array<int, array{title: string, links: array<int, array{label: string, url: string}>}> */
    private function buildGroups(int $langId, string $frontUrl): array
    {
        $groups = [];
        $resolve = fn (string $original): string => $this->resolveUrl($original, $langId, $frontUrl);

        $teachers = DB::table('tbl_users')
            ->where('user_is_teacher', '=', 1)
            ->where('user_active', '=', 1)
            ->whereNull('user_deleted')
            ->where('user_username', '!=', '')
            ->orderBy('user_first_name')
            ->get(['user_username', 'user_first_name', 'user_last_name']);

        if ($teachers->isNotEmpty()) {
            $groups[] = [
                'title' => 'Teachers',
                'links' => $teachers->map(fn ($row) => [
                    'label' => trim($row->user_first_name.' '.($row->user_last_name ?? '')),
                    'url' => $resolve('teachers/view/'.$row->user_username),
                ])->all(),
            ];
        }

        if ($this->groupClassesEnabled()) {
            $classes = DB::table('tbl_group_classes as g')
                ->leftJoin('tbl_group_classes_lang as gl', function ($join) use ($langId) {
                    $join->on('gl.gclang_grpcls_id', '=', 'g.grpcls_id')
                        ->where('gl.gclang_lang_id', '=', $langId);
                })
                ->join('tbl_users as u', 'u.user_id', '=', 'g.grpcls_teacher_id')
                ->where('g.grpcls_status', '=', 1)
                ->where('g.grpcls_parent', '=', 0)
                ->where('g.grpcls_start_datetime', '>', now())
                ->where('g.grpcls_slug', '!=', '')
                ->whereNull('u.user_deleted')
                ->where('u.user_active', '=', 1)
                ->orderBy('g.grpcls_start_datetime')
                ->get([
                    'g.grpcls_slug',
                    DB::raw('COALESCE(NULLIF(gl.grpcls_title, ""), g.grpcls_title) as grpcls_title'),
                ]);

            if ($classes->isNotEmpty()) {
                $groups[] = [
                    'title' => 'Group classes',
                    'links' => $classes->map(fn ($row) => [
                        'label' => (string) $row->grpcls_title,
                        'url' => $resolve('group-classes/view/'.$row->grpcls_slug),
                    ])->all(),
                ];
            }
        }

        if ($this->coursesEnabled()) {
            $courses = DB::table('tbl_courses as course')
                ->join('tbl_users as teacher', 'teacher.user_id', '=', 'course.course_user_id')
                ->leftJoin('tbl_course_details as cd', 'cd.course_id', '=', 'course.course_id')
                ->whereNull('course.course_deleted')
                ->where('course.course_status', '=', 3)
                ->where('course.course_active', '=', 1)
                ->where('teacher.user_username', '!=', '')
                ->whereNull('teacher.user_deleted')
                ->whereNotNull('teacher.user_verified')
                ->where('teacher.user_active', '=', 1)
                ->where('teacher.user_is_teacher', '=', 1)
                ->orderBy('course.course_id')
                ->get(['course.course_slug', DB::raw('COALESCE(NULLIF(cd.course_title, ""), course.course_slug) as course_title')]);

            if ($courses->isNotEmpty()) {
                $groups[] = [
                    'title' => 'Courses',
                    'links' => $courses->map(fn ($row) => [
                        'label' => (string) $row->course_title,
                        'url' => $resolve('courses/view/'.$row->course_slug),
                    ])->all(),
                ];
            }
        }

        $cmsLinks = DB::table('tbl_navigation_links as nlink')
            ->join('tbl_navigations as nav', 'nav.nav_id', '=', 'nlink.nlink_nav_id')
            ->leftJoin('tbl_navigation_links_lang as nll', function ($join) use ($langId) {
                $join->on('nll.nlinklang_nlink_id', '=', 'nlink.nlink_id')
                    ->where('nll.nlinklang_lang_id', '=', $langId);
            })
            ->where('nlink.nlink_deleted', '=', 0)
            ->where('nav.nav_active', '=', 1)
            ->orderBy('nlink.nlink_order')
            ->get([
                'nlink.nlink_type',
                'nlink.nlink_cpage_id',
                'nlink.nlink_url',
                DB::raw('COALESCE(NULLIF(nll.nlink_caption, ""), nlink.nlink_identifier) as link_label'),
            ]);

        $cms = [];
        foreach ($cmsLinks as $link) {
            if ((int) $link->nlink_type === 0 && (int) $link->nlink_cpage_id > 0) {
                $cms[] = [
                    'label' => (string) $link->link_label,
                    'url' => $resolve('cms/view/'.$link->nlink_cpage_id),
                ];
            } elseif ((int) $link->nlink_type === 2 && $link->nlink_url) {
                $url = (string) $link->nlink_url;
                $base = $frontUrl.'/';
                if (str_contains(strtolower($url), '{domain}')) {
                    $url = str_replace(['{DOMAIN}', '{domain}'], $base, $url);
                    $url = ltrim($url, '/');
                    if (! str_starts_with($url, 'http')) {
                        $url = $frontUrl.'/'.ltrim($url, '/');
                    }
                }
                $cms[] = [
                    'label' => (string) $link->link_label,
                    'url' => $url,
                ];
            }
        }

        if ($cms !== []) {
            $groups[] = [
                'title' => 'CMS pages',
                'links' => $cms,
            ];
        }

        return $groups;
    }

    private function resolveUrl(string $original, int $langId, string $frontUrl): string
    {
        $path = DB::table('tbl_seo_urls')
            ->where('seourl_lang_id', '=', $langId)
            ->where('seourl_original', '=', $original)
            ->value('seourl_custom');

        if (! $path) {
            $path = match (true) {
                str_starts_with($original, 'teachers/view/') => 'teachers/'.substr($original, strlen('teachers/view/')),
                str_starts_with($original, 'courses/view/') => 'courses/'.substr($original, strlen('courses/view/')),
                str_starts_with($original, 'group-classes/view/') => 'group-classes/'.substr($original, strlen('group-classes/view/')),
                str_starts_with($original, 'cms/view/') => $this->cmsPath((int) substr($original, strlen('cms/view/')), $langId),
                default => $original,
            };
        }

        return $frontUrl.'/'.ltrim((string) $path, '/');
    }

    private function cmsPath(int $pageId, int $langId): string
    {
        $custom = DB::table('tbl_seo_urls')
            ->where('seourl_lang_id', '=', $langId)
            ->where('seourl_original', '=', 'cms/view/'.$pageId)
            ->value('seourl_custom');

        return $custom ? (string) $custom : 'cms/'.$pageId;
    }

    private function coursesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')->where('conf_name', 'CONF_ENABLE_COURSES')->value('conf_val') === 1;
    }

    private function groupClassesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')->where('conf_name', 'CONF_GROUP_CLASSES_DISABLED')->value('conf_val') === 1;
    }
}
