<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Services\AffiliateRegistrationService;
use App\Services\ContactService;
use DateTime;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function contact(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        return response()->json([
            'banner_html' => $this->blockContent(4, $langId),
            'left_html' => $this->blockContent(5, $langId),
        ]);
    }

    public function contactSubmit(Request $request, ContactService $contact): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string'],
        ]);

        try {
            $message = $contact->submit($request->only(['name', 'email', 'phone', 'message']), $request->integer('lang_id', 1));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => $message]);
    }

    /** Apply to teach landing (legacy TeacherRequest/index + ExtraPage TYPE_APPLY_TO_TEACH). */
    public function applyToTeach(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);
        $applyType = 2;
        $faqCategory = 4;

        $blocks = DB::table('tbl_extra_pages as ep')
            ->leftJoin('tbl_extra_pages_lang as epl', function ($join) use ($langId) {
                $join->on('ep.epage_id', '=', 'epl.epagelang_epage_id')
                    ->where('epl.epagelang_lang_id', '=', $langId);
            })
            ->where('ep.epage_type', $applyType)
            ->where('ep.epage_active', 1)
            ->orderBy('ep.epage_order')
            ->get([
                'ep.epage_block_type as block_type',
                DB::raw('COALESCE(NULLIF(epl.epage_content, ""), ep.epage_default_content) as html'),
            ])
            ->map(fn ($row) => [
                'block_type' => (int) $row->block_type,
                'html' => $row->html ?? '',
            ])
            ->filter(fn ($row) => $row['html'] !== '')
            ->values();

        $faqs = DB::table('tbl_faq as f')
            ->join('tbl_faq_lang as fl', function ($join) use ($langId) {
                $join->on('f.faq_id', '=', 'fl.faqlang_faq_id')
                    ->where('fl.faqlang_lang_id', '=', $langId);
            })
            ->where('f.faq_active', 1)
            ->where('f.faq_category', $faqCategory)
            ->orderBy('f.faq_id')
            ->get([
                'f.faq_id as id',
                'fl.faq_title as title',
                'fl.faq_description as description',
            ])
            ->filter(fn ($faq) => $faq->title && $faq->description)
            ->map(fn ($faq) => [
                'id' => (int) $faq->id,
                'title' => $faq->title,
                'description' => $faq->description,
            ])
            ->values();

        $config = Configuration::getMany([
            'CONF_TERMS_AND_CONDITIONS_PAGE',
            'CONF_PRIVACY_POLICY_PAGE',
        ]);

        return response()->json([
            'blocks' => $blocks,
            'faqs' => $faqs,
            'contact_html' => $this->blockContent(26, $langId),
            'terms_page_id' => (int) ($config['CONF_TERMS_AND_CONDITIONS_PAGE'] ?? 0),
            'privacy_page_id' => (int) ($config['CONF_PRIVACY_POLICY_PAGE'] ?? 0),
        ]);
    }

    /** Legacy Afile::TYPE_AFFILIATE_REGISTRATION_BANNER */
    private const AFFILIATE_REGISTRATION_BANNER = 67;

    /** Affiliate signup landing (legacy GuestUser/affiliateSignupForm). */
    public function affiliateSignup(Request $request, AffiliateRegistrationService $affiliates): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);
        $enabled = $affiliates->isEnabled();

        $config = Configuration::getMany([
            'CONF_TERMS_AND_CONDITIONS_PAGE',
            'CONF_PRIVACY_POLICY_PAGE',
        ]);

        $bannerHtml = '';
        if ($enabled) {
            $row = DB::table('tbl_extra_pages as ep')
                ->leftJoin('tbl_extra_pages_lang as epl', function ($join) use ($langId) {
                    $join->on('ep.epage_id', '=', 'epl.epagelang_epage_id')
                        ->where('epl.epagelang_lang_id', '=', $langId);
                })
                ->where('ep.epage_type', 6)
                ->where('ep.epage_block_type', 19)
                ->where('ep.epage_active', 1)
                ->first([
                    DB::raw(
                        'COALESCE(NULLIF(TRIM(epl.epage_content), ""), NULLIF(TRIM(ep.epage_default_content), "")) as content'
                    ),
                ]);
            $bannerHtml = $row->content ?? '';
        }

        return response()->json([
            'enabled' => $enabled,
            'banner_html' => $bannerHtml,
            'banner_image' => '/image/show/'.self::AFFILIATE_REGISTRATION_BANNER.'/0/LARGE/'.$langId,
            'terms_page_id' => (int) ($config['CONF_TERMS_AND_CONDITIONS_PAGE'] ?? 0),
            'privacy_page_id' => (int) ($config['CONF_PRIVACY_POLICY_PAGE'] ?? 0),
            'default_timezone' => (string) Configuration::getValue('CONF_TIMEZONE', 'UTC'),
            'timezones' => $this->timezoneOptions(),
        ]);
    }

    /** @return array<int, array{id: string, label: string}> */
    private function timezoneOptions(): array
    {
        $options = [];
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $offset = (new DateTime('now', new DateTimeZone($tz)))->format('P');
            $options[] = [
                'id' => $tz,
                'label' => "UTC {$offset} {$tz}",
            ];
        }

        return $options;
    }

    /** Legacy Afile::TYPE_CPAGE_BACKGROUND_IMAGE */
    private const CPAGE_BACKGROUND_IMAGE = 27;

    public function cms(Request $request, string $identifier): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        $page = DB::table('tbl_content_pages as c')
            ->leftJoin('tbl_content_pages_lang as cl', function ($join) use ($langId) {
                $join->on('c.cpage_id', '=', 'cl.cpagelang_cpage_id')
                    ->where('cl.cpagelang_lang_id', '=', $langId);
            })
            ->where(function ($q) use ($identifier) {
                if (is_numeric($identifier)) {
                    $q->where('c.cpage_id', (int) $identifier);
                } else {
                    $q->where('c.cpage_identifier', $identifier);
                }
            })
            ->where('c.cpage_deleted', 0)
            ->first([
                'c.cpage_id as id',
                'c.cpage_identifier as identifier',
                DB::raw('COALESCE(cl.cpage_title, c.cpage_identifier) as title'),
                'cl.cpage_content as content',
                'cl.cpage_image_title as image_title',
                'c.cpage_layout as layout',
            ]);

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $pageId = (int) $page->id;
        $layout = (int) $page->layout;
        $blocks = [];

        if ($layout === 1) {
            $blockRows = DB::table('tbl_content_pages_block_lang')
                ->where('cpblocklang_cpage_id', $pageId)
                ->where('cpblocklang_lang_id', $langId)
                ->orderBy('cpblocklang_block_id')
                ->get(['cpblocklang_block_id as block_id', 'cpblocklang_text as html']);

            foreach ($blockRows as $row) {
                $html = trim(html_entity_decode($row->html ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($html !== '') {
                    $blocks[] = [
                        'block_id' => (int) $row->block_id,
                        'html' => $html,
                    ];
                }
            }
        }

        $content = $page->content ?? '';
        if ($content !== '') {
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return response()->json([
            'data' => [
                'id' => $pageId,
                'identifier' => $page->identifier,
                'title' => $page->title,
                'content' => $content,
                'image_title' => $page->image_title ?? '',
                'layout' => $layout,
                'hero_image' => "/image/show/".self::CPAGE_BACKGROUND_IMAGE."/{$pageId}/LARGE/{$langId}",
                'blocks' => $blocks,
            ],
        ]);
    }

    private function blockContent(int $blockType, int $langId): string
    {
        $row = DB::table('tbl_extra_pages as ep')
            ->leftJoin('tbl_extra_pages_lang as epl', function ($join) use ($langId) {
                $join->on('ep.epage_id', '=', 'epl.epagelang_epage_id')
                    ->where('epl.epagelang_lang_id', '=', $langId);
            })
            ->where('ep.epage_block_type', $blockType)
            ->where('ep.epage_active', 1)
            ->first([DB::raw('COALESCE(epl.epage_content, ep.epage_default_content) as content')]);

        $content = $row->content ?? '';
        if ($content !== '') {
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $content;
    }
}
