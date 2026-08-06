<?php

namespace App\Services\Admin\Listings;

use App\Services\Admin\AdminGdprRequestService;
use App\Services\Admin\AdminOrderHelper;
use App\Services\Admin\AdminWithdrawRequestService;
use App\Services\Admin\Listings\AdminGroupClassListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminGenericListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}|null */
    public function search(string $module, Request $request): ?array
    {
        return match ($module) {
            'teacher-requests' => $this->teacherRequests($request),
            'withdraw-requests' => $this->withdrawRequests($request),
            'gdpr-requests' => $this->gdprRequests($request),
            'admin-users' => $this->adminUsers($request),
            'group-classes' => $this->groupClasses($request),
            'package-classes' => $this->packageClasses($request),
            'content-pages' => $this->contentPages($request),
            'faq' => $this->faq($request),
            'faq-categories' => $this->faqCategories($request),
            'blog-post-categories' => $this->blogPostCategories($request),
            'blog-comments' => $this->blogComments($request),
            'blog-contributions' => $this->blogContributions($request),
            'countries' => $this->countries($request),
            'states' => $this->states($request),
            'testimonials' => $this->testimonials($request),
            'email-templates' => $this->emailTemplates($request),
            'slides' => $this->slides($request),
            'categories' => $this->categories($request),
            'questions' => $this->questions($request),
            'quizzes' => $this->quizzes($request),
            'lessons' => $this->lessonOrders($request),
            'subscriptions' => $this->subscriptionOrders($request),
            'classes' => $this->classOrders($request),
            'course-orders' => $this->courseOrders($request),
            'packages' => $this->packageOrders($request),
            'giftcards' => $this->giftcardOrders($request),
            'wallet' => $this->walletOrders($request),
            'reported-issues' => app(AdminReportedIssuesListingService::class)->search($request),
            'preferences' => app(AdminTeacherPreferencesListingService::class)->preferences($request),
            'speak-language' => app(AdminTeacherPreferencesListingService::class)->speakLanguages($request),
            'speak-language-levels' => app(AdminTeacherPreferencesListingService::class)->speakLanguageLevels($request),
            'teach-language' => app(AdminTeacherPreferencesListingService::class)->teachLanguages($request),
            'issue-report-options' => app(AdminTeacherPreferencesListingService::class)->issueReportOptions($request),
            'video-content' => $this->videoContent($request),
            'abusive-words' => $this->abusiveWords($request),
            'meta-tags' => $this->metaTags($request),
            'url-rewriting' => $this->urlRewriting($request),
            'forum' => $this->forumQuestions($request),
            'forum-tags' => $this->forumTags($request),
            'forum-reported-questions' => $this->forumReportedQuestions($request),
            'forum-tag-requests' => $this->forumTagRequests($request),
            'forum-report-issue-reasons' => $this->forumReportReasons($request),
            'subscription-plans' => $this->subscriptionPlans($request),
            'course-requests' => $this->courseRequests($request),
            'course-edit-requests' => $this->courseEditRequests($request),
            'course-refund-requests' => $this->courseRefundRequests($request),
            'course-languages' => $this->courseLanguages($request),
            'rating-reviews' => $this->ratingReviews($request),
            'certificates' => $this->certificates($request),
            'navigations' => $this->navigations($request),
            'content-block' => $this->contentBlocks($request),
            'label' => $this->languageLabels($request),
            'affiliate-commission' => $this->affiliateCommissions($request),
            'order-subscription-plans' => $this->orderSubscriptionPlans($request),
            default => null,
        };
    }

  private function teacherRequests(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_teacher_requests as tr')
            ->join('tbl_users as u', 'u.user_id', '=', 'tr.tereq_user_id')
            ->where('tr.tereq_step', 5)
            ->select([
                'tr.tereq_id as id',
                'tr.tereq_user_id as user_id',
                'tr.tereq_reference as reference',
                DB::raw('TRIM(CONCAT(tr.tereq_first_name, " ", COALESCE(tr.tereq_last_name, ""))) as full_name'),
                'u.user_email as email',
                DB::raw("IFNULL(tr.tereq_comments, '') as comments"),
                'tr.tereq_status as status',
                'tr.tereq_date as created_at',
            ]);
        $this->applyKeyword($request, $query, [
            'tr.tereq_reference',
            'tr.tereq_first_name',
            'tr.tereq_last_name',
            'u.user_email',
            'u.user_username',
        ]);

        $status = $request->query('status');
        if ($status !== null && $status !== '') {
            $query->where('tr.tereq_status', '=', (int) $status);
        }

        $dateFrom = trim((string) $request->query('date_from', ''));
        if ($dateFrom !== '') {
            $query->where('tr.tereq_date', '>=', $dateFrom);
        }

        $dateTo = trim((string) $request->query('date_to', ''));
        if ($dateTo !== '') {
            $query->where('tr.tereq_date', '<=', $dateTo.' 23:59:59');
        }

        return $this->runQuery($request, $query, 'tr.tereq_id');
    }

    private function withdrawRequests(Request $request): array
    {
        $query = DB::table('tbl_user_withdrawal_requests as wr')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'wr.withdrawal_user_id')
            ->join('tbl_payment_methods as pm', 'pm.pmethod_id', '=', 'wr.withdrawal_payment_method_id')
            ->select([
                'wr.withdrawal_id as id',
                'wr.withdrawal_user_id as user_id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'u.user_email as email',
                'u.user_deleted as user_deleted',
                'wr.withdrawal_transaction_fee as transaction_fee',
                'wr.withdrawal_amount as amount',
                'wr.withdrawal_status as status',
                'wr.withdrawal_request_date as created_at',
                'wr.withdrawal_bank as bank_name',
                'wr.withdrawal_account_holder_name as account_holder_name',
                'wr.withdrawal_account_number as account_number',
                'wr.withdrawal_ifc_swift_code as ifsc_swift_code',
                'wr.withdrawal_bank_address as bank_address',
                'wr.withdrawal_paypal_email_id as paypal_email',
                'wr.withdrawal_comments as comments',
                'pm.pmethod_code as payment_method_code',
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $first = strtoupper(mb_substr($keyword, 0, 1, 'UTF-8'));
            if ($first === '#') {
                $id = (int) ltrim(str_replace('#', '', $keyword), '0');
                if ($id > 0) {
                    $query->where('wr.withdrawal_id', $id);
                }
            } else {
                $query->where(function ($q) use ($keyword) {
                    $q->where(DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))'), 'like', "%{$keyword}%")
                        ->orWhere('u.user_email', 'like', "%{$keyword}%")
                        ->orWhere('wr.withdrawal_id', 'like', "%{$keyword}%");
                });
            }
        }

        $minPrice = (float) $request->query('minprice', 0);
        if ($minPrice > 0) {
            $query->where('wr.withdrawal_amount', '>=', $minPrice);
        }

        $maxPrice = (float) $request->query('maxprice', 0);
        if ($maxPrice > 0) {
            $query->where('wr.withdrawal_amount', '<=', $maxPrice);
        }

        $status = $request->query('status', '');
        if ($status !== '' && $status !== null && (int) $status >= 1) {
            $query->where('wr.withdrawal_status', (int) $status);
        }

        $dateFrom = trim((string) $request->query('date_from', ''));
        if ($dateFrom !== '') {
            $query->where('wr.withdrawal_request_date', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('date_to', ''));
        if ($dateTo !== '') {
            $query->where('wr.withdrawal_request_date', '<=', $dateTo.' 23:59:59');
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $service = app(AdminWithdrawRequestService::class);
        $rows = $query
            ->orderByDesc('wr.withdrawal_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) use ($service) {
                $data = (array) $row;
                $data['request_number'] = $service->formatRequestNumber((int) $data['id']);
                $data['status_label'] = $service->statusLabel((int) $data['status']);
                $data['account_details'] = $this->formatWithdrawAccountDetails($data);

                return $data;
            })
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    /** @param array<string, mixed> $row */
    private function formatWithdrawAccountDetails(array $row): string
    {
        $parts = [];
        $code = (string) ($row['payment_method_code'] ?? '');

        if ($code === AdminWithdrawRequestService::BANK_PAYOUT) {
            if (! empty($row['bank_name'])) {
                $parts[] = '<strong>Bank name: </strong>'.e((string) $row['bank_name']);
            }
            if (! empty($row['account_holder_name'])) {
                $parts[] = '<strong>Account holder name: </strong>'.e((string) $row['account_holder_name']);
            }
            if (! empty($row['account_number'])) {
                $parts[] = '<strong>Account number: </strong>'.e((string) $row['account_number']);
            }
            if (! empty($row['ifsc_swift_code'])) {
                $parts[] = '<strong>IFSC/SWIFT code: </strong>'.e((string) $row['ifsc_swift_code']);
            }
            if (! empty($row['bank_address'])) {
                $parts[] = '<strong>Bank address: </strong>'.nl2br(e((string) $row['bank_address']));
            }
        } elseif ($code === AdminWithdrawRequestService::PAYPAL_PAYOUT && ! empty($row['paypal_email'])) {
            $parts[] = '<strong>PayPal Email: </strong>'.e((string) $row['paypal_email']);
        }

        if (! empty($row['comments'])) {
            $parts[] = '<strong>Comments: </strong>'.nl2br(e((string) $row['comments']));
        }

        return implode('<br>', $parts);
    }

    private function gdprRequests(Request $request): array
    {
        $query = DB::table('tbl_gdpr_requests as g')
            ->join('tbl_users as u', 'u.user_id', '=', 'g.gdpreq_user_id')
            ->select([
                'g.gdpreq_id as id',
                'g.gdpreq_user_id as user_id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'u.user_email as email',
                'u.user_deleted as user_deleted',
                'g.gdpreq_reason as reason',
                'g.gdpreq_type as type',
                'g.gdpreq_status as status',
                'g.gdpreq_added_on as created_at',
                'g.gdpreq_updated_on as updated_at',
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where(DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))'), 'like', "%{$keyword}%")
                    ->orWhere('u.user_email', 'like', "%{$keyword}%")
                    ->orWhere('g.gdpreq_reason', 'like', "%{$keyword}%");
            });
        }

        $status = $request->query('status', '');
        if ($status !== '' && $status !== null) {
            $query->where('g.gdpreq_status', (int) $status);
        }

        $dateFrom = trim((string) ($request->query('added_on_from', $request->query('date_from', ''))));
        if ($dateFrom !== '') {
            $query->where('g.gdpreq_added_on', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) ($request->query('added_on_to', $request->query('date_to', ''))));
        if ($dateTo !== '') {
            $query->where('g.gdpreq_added_on', '<=', $dateTo.' 23:59:59');
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $service = app(AdminGdprRequestService::class);
        $rows = $query
            ->orderByDesc('g.gdpreq_added_on')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) use ($service) {
                $data = (array) $row;
                $data['status_label'] = $service->statusLabel((int) $data['status']);
                $reason = (string) ($data['reason'] ?? '');
                $data['reason_short'] = mb_strlen($reason) > 50 ? mb_substr($reason, 0, 50).'...' : $reason;

                return $data;
            })
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function adminUsers(Request $request): array
    {
        $query = DB::table('tbl_admin')
            ->select([
                'admin_id as id',
                'admin_username as username',
                'admin_name as full_name',
                'admin_email as email',
                'admin_active as active',
            ]);
        $this->applyKeyword($request, $query, ['admin_username', 'admin_name', 'admin_email']);

        return $this->runQuery($request, $query, 'admin_active', 'desc');
    }

    private function groupClasses(Request $request): array
    {
        return app(AdminGroupClassListingService::class)->groupClasses($request);
    }

    private function packageClasses(Request $request): array
    {
        return app(AdminGroupClassListingService::class)->packageClasses($request);
    }

    private function contentPages(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_content_pages as p')
            ->leftJoin('tbl_content_pages_lang as pl', function ($join) use ($langId) {
                $join->on('pl.cpagelang_cpage_id', '=', 'p.cpage_id')
                    ->where('pl.cpagelang_lang_id', '=', $langId);
            })
            ->where('p.cpage_deleted', '=', 0)
            ->select([
                'p.cpage_id as id',
                'p.cpage_identifier as identifier',
                DB::raw('IFNULL(pl.cpage_title, p.cpage_identifier) as title'),
            ]);
        $this->applyKeyword($request, $query, ['p.cpage_identifier', 'pl.cpage_title']);

        return $this->runQuery($request, $query, 'p.cpage_id');
    }

    private function faq(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_faq as f')
            ->leftJoin('tbl_faq_lang as fl', function ($join) use ($langId) {
                $join->on('fl.faqlang_faq_id', '=', 'f.faq_id')
                    ->where('fl.faqlang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_faq_categories as fc', 'fc.faqcat_id', '=', 'f.faq_category')
            ->leftJoin('tbl_faq_categories_lang as fcl', function ($join) use ($langId) {
                $join->on('fcl.faqcatlang_faqcat_id', '=', 'fc.faqcat_id')
                    ->where('fcl.faqcatlang_lang_id', '=', $langId);
            })
            ->select([
                'f.faq_id as id',
                'f.faq_identifier as identifier',
                'fl.faq_title as title',
                DB::raw('IFNULL(fcl.faqcat_name, fc.faqcat_identifier) as category_name'),
                'f.faq_active as active',
            ]);
        $this->applyKeyword($request, $query, ['f.faq_identifier', 'fl.faq_title']);

        return $this->runQuery($request, $query, 'f.faq_active', 'desc');
    }

    private function faqCategories(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_faq_categories as fc')
            ->leftJoin('tbl_faq_categories_lang as fcl', function ($join) use ($langId) {
                $join->on('fcl.faqcatlang_faqcat_id', '=', 'fc.faqcat_id')
                    ->where('fcl.faqcatlang_lang_id', '=', $langId);
            })
            ->select([
                'fc.faqcat_id as id',
                'fc.faqcat_identifier',
                DB::raw('IFNULL(fcl.faqcat_name, fc.faqcat_identifier) as faqcat_name'),
                'fc.faqcat_active as active',
                'fc.faqcat_order',
            ])
            ->where('fc.faqcat_deleted', 0)
            ->orderByDesc('fc.faqcat_active')
            ->orderBy('fc.faqcat_order');
        $this->applyKeyword($request, $query, ['fc.faqcat_identifier', 'fcl.faqcat_name']);

        $rows = $query
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => max(1, count($rows)),
                'total' => count($rows),
                'last_page' => 1,
            ],
        ];
    }

    private function blogPostCategories(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_blog_post_categories as c')
            ->leftJoin('tbl_blog_post_categories_lang as cl', function ($join) use ($langId) {
                $join->on('cl.bpcategorylang_bpcategory_id', '=', 'c.bpcategory_id')
                    ->where('cl.bpcategorylang_lang_id', '=', $langId);
            })
            ->where('c.bpcategory_deleted', 0)
            ->select([
                'c.bpcategory_id as id',
                'c.bpcategory_identifier as identifier',
                DB::raw('IFNULL(cl.bpcategory_name, c.bpcategory_identifier) as title'),
                'c.bpcategory_active as active',
            ]);
        $this->applyKeyword($request, $query, ['c.bpcategory_identifier', 'cl.bpcategory_name']);

        return $this->runQuery($request, $query, 'c.bpcategory_id');
    }

    private function blogComments(Request $request): array
    {
        $query = DB::table('tbl_blog_post_comments as c')
            ->join('tbl_users as u', 'u.user_id', '=', 'c.bpcomment_user_id')
            ->select([
                'c.bpcomment_id as id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'c.bpcomment_content as title',
                'c.bpcomment_active as active',
                'c.bpcomment_added_on as created_at',
            ]);
        $this->applyKeyword($request, $query, ['c.bpcomment_content', 'u.user_email']);

        return $this->runQuery($request, $query, 'c.bpcomment_id');
    }

    private function blogContributions(Request $request): array
    {
        $query = DB::table('tbl_blog_contributions as c')
            ->select([
                'c.bcontributions_id as id',
                'c.bcontributions_author_name as full_name',
                'c.bcontributions_author_email as email',
                'c.bcontributions_status as status',
                'c.bcontributions_added_on as created_at',
            ]);
        $this->applyKeyword($request, $query, ['c.bcontributions_author_name', 'c.bcontributions_author_email']);

        return $this->runQuery($request, $query, 'c.bcontributions_id');
    }

    private function countries(Request $request): array
    {
        $langId = $this->langId($request);
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $query = DB::table('tbl_countries as c')
            ->leftJoin('tbl_countries_lang as cl', function ($join) use ($langId) {
                $join->on('cl.countrylang_country_id', '=', 'c.country_id')
                    ->where('cl.countrylang_lang_id', '=', $langId);
            })
            ->select([
                'c.country_id as id',
                'c.country_identifier as identifier',
                'c.country_identifier as country_identifier',
                DB::raw('IFNULL(cl.country_name, c.country_identifier) as title'),
                DB::raw('IFNULL(cl.country_name, c.country_identifier) as country_name'),
                'c.country_code',
                'c.country_dial_code',
                'c.country_active as active',
            ]);
        $this->applyKeyword($request, $query, ['c.country_code', 'cl.country_name', 'c.country_identifier']);

        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('c.country_active')
            ->orderByRaw('IFNULL(cl.country_name, c.country_identifier) ASC')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function states(Request $request): array
    {
        $langId = $this->langId($request);
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $query = DB::table('tbl_states as s')
            ->leftJoin('tbl_states_lang as sl', function ($join) use ($langId) {
                $join->on('sl.stlang_state_id', '=', 's.state_id')
                    ->where('sl.stlang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_countries as c', 'c.country_id', '=', 's.state_country_id')
            ->leftJoin('tbl_countries_lang as cl', function ($join) use ($langId) {
                $join->on('cl.countrylang_country_id', '=', 'c.country_id')
                    ->where('cl.countrylang_lang_id', '=', $langId);
            })
            ->select([
                's.state_id as id',
                's.state_identifier as identifier',
                's.state_identifier',
                's.state_code',
                DB::raw('IFNULL(sl.state_name, s.state_identifier) as title'),
                DB::raw('IFNULL(sl.state_name, s.state_identifier) as state_name'),
                DB::raw('IFNULL(cl.country_name, c.country_identifier) as country_identifier'),
                's.state_country_id',
                's.state_active as active',
            ]);
        $this->applyKeyword($request, $query, ['sl.state_name', 's.state_identifier', 's.state_code']);
        $countryId = (int) $request->query('state_country_id', 0);
        if ($countryId > 0) {
            $query->where('s.state_country_id', $countryId);
        }

        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('s.state_active')
            ->orderByRaw('IFNULL(sl.state_name, s.state_identifier) ASC')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function testimonials(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_testimonials as t')
            ->leftJoin('tbl_testimonials_lang as tl', function ($join) use ($langId) {
                $join->on('tl.testimoniallang_testimonial_id', '=', 't.testimonial_id')
                    ->where('tl.testimoniallang_lang_id', '=', $langId);
            })
            ->select([
                't.testimonial_id as id',
                't.testimonial_identifier as identifier',
                't.testimonial_identifier',
                't.testimonial_user_name',
                'tl.testimonial_text',
                't.testimonial_active as active',
                't.testimonial_added_on',
            ])
            ->where('t.testimonial_deleted', '=', '0')
            ->orderByDesc('t.testimonial_active')
            ->orderBy('t.testimonial_id');
        $this->applyKeyword($request, $query, ['t.testimonial_identifier', 't.testimonial_user_name', 'tl.testimonial_text']);

        return $this->runQuery($request, $query, 't.testimonial_id', 'asc');
    }

    private function emailTemplates(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_email_templates as e')
            ->where('e.etpl_lang_id', $langId)
            ->select([
                'e.etpl_code as id',
                'e.etpl_code as identifier',
                'e.etpl_name',
                'e.etpl_subject',
                'e.etpl_lang_id',
                'e.etpl_status as active',
            ]);
        $this->applyKeyword($request, $query, ['e.etpl_code', 'e.etpl_name', 'e.etpl_subject']);

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('e.etpl_status')
            ->orderBy('e.etpl_name')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function slides(Request $request): array
    {
        $query = DB::table('tbl_slides as s')
            ->select([
                's.slide_id as id',
                's.slide_identifier as identifier',
                's.slide_identifier as title',
                's.slide_active as active',
                's.slide_order as display_order',
            ]);
        $this->applyKeyword($request, $query, ['s.slide_identifier']);

        $rows = $query
            ->orderByDesc('s.slide_active')
            ->orderBy('s.slide_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->paginateResult($request, $rows, count($rows));
    }

    private function categories(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_categories as c')
            ->leftJoin('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('cl.catlang_cat_id', '=', 'c.cat_id')
                    ->where('cl.catlang_lang_id', '=', $langId);
            })
            ->where('c.cat_type', 1)
            ->select([
                'c.cat_id as id',
                'c.cat_identifier as identifier',
                DB::raw('IFNULL(cl.cat_name, c.cat_identifier) as title'),
                'c.cat_active as active',
            ]);
        $this->applyKeyword($request, $query, ['c.cat_identifier', 'cl.cat_name']);

        return $this->runQuery($request, $query, 'c.cat_id');
    }

    private function questions(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_questions as q')
            ->leftJoin('tbl_questions_lang as ql', function ($join) use ($langId) {
                $join->on('ql.queslang_ques_id', '=', 'q.ques_id')
                    ->where('ql.queslang_lang_id', '=', $langId);
            })
            ->where('q.ques_deleted', 0)
            ->select([
                'q.ques_id as id',
                'q.ques_identifier as identifier',
                'ql.ques_title as title',
                'q.ques_type as type',
                'q.ques_active as active',
            ]);
        $this->applyKeyword($request, $query, ['q.ques_identifier', 'ql.ques_title']);

        return $this->runQuery($request, $query, 'q.ques_id');
    }

    private function quizzes(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_quizzes as q')
            ->leftJoin('tbl_quizzes_lang as ql', function ($join) use ($langId) {
                $join->on('ql.quizlang_quiz_id', '=', 'q.quiz_id')
                    ->where('ql.quizlang_lang_id', '=', $langId);
            })
            ->where('q.quiz_deleted', 0)
            ->select([
                'q.quiz_id as id',
                'q.quiz_identifier as identifier',
                'ql.quiz_title as title',
                'q.quiz_active as active',
            ]);
        $this->applyKeyword($request, $query, ['q.quiz_identifier', 'ql.quiz_title']);

        return $this->runQuery($request, $query, 'q.quiz_id');
    }

    private function lessonOrders(Request $request): array
    {
        $query = DB::table('tbl_order_lessons as ol')
            ->join('tbl_orders as o', 'o.order_id', '=', 'ol.ordles_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'o.order_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ol.ordles_teacher_id')
            ->select([
                'ol.ordles_id as id',
                'o.order_id as order_id',
                DB::raw('CONCAT(learner.user_first_name, " ", COALESCE(learner.user_last_name, "")) as learner_name'),
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as teacher_name'),
                'ol.ordles_amount as amount',
                'ol.ordles_status as status',
                'ol.ordles_lesson_starttime as start_at',
            ]);
        $this->applyKeyword($request, $query, ['learner.user_email', 'teacher.user_email']);

        return $this->runQuery($request, $query, 'ol.ordles_id');
    }

    private function subscriptionOrders(Request $request): array
    {
        $query = DB::table('tbl_order_subscriptions as os')
            ->join('tbl_orders as o', 'o.order_id', '=', 'os.ordsub_order_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
            ->select([
                'os.ordsub_id as id',
                'o.order_id as order_id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'os.ordsub_amount as amount',
                'os.ordsub_status as status',
                'os.ordsub_startdate as start_at',
            ]);
        $this->applyKeyword($request, $query, ['u.user_email']);

        return $this->runQuery($request, $query, 'os.ordsub_id');
    }

    private function classOrders(Request $request): array
    {
        $query = DB::table('tbl_order_classes as oc')
            ->join('tbl_orders as o', 'o.order_id', '=', 'oc.ordcls_order_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
            ->select([
                'oc.ordcls_id as id',
                'o.order_id as order_id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'oc.ordcls_amount as amount',
                'oc.ordcls_status as status',
            ]);
        $this->applyKeyword($request, $query, ['u.user_email']);

        return $this->runQuery($request, $query, 'oc.ordcls_id');
    }

    private function courseOrders(Request $request): array
    {
        $query = DB::table('tbl_order_courses as oc')
            ->join('tbl_orders as o', 'o.order_id', '=', 'oc.ordcrs_order_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
            ->select([
                'oc.ordcrs_id as id',
                'o.order_id as order_id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'oc.ordcrs_amount as amount',
                'oc.ordcrs_status as status',
            ]);
        $this->applyKeyword($request, $query, ['u.user_email']);

        return $this->runQuery($request, $query, 'oc.ordcrs_id');
    }

    private function packageOrders(Request $request): array
    {
        $query = DB::table('tbl_order_packages as op')
            ->join('tbl_orders as o', 'o.order_id', '=', 'op.ordpkg_order_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
            ->select([
                'op.ordpkg_id as id',
                'o.order_id as order_id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'op.ordpkg_amount as amount',
                'op.ordpkg_status as status',
            ]);
        $this->applyKeyword($request, $query, ['u.user_email']);

        return $this->runQuery($request, $query, 'op.ordpkg_id');
    }

    private function giftcardOrders(Request $request): array
    {
        if (! DB::getSchemaBuilder()->hasTable('tbl_order_giftcards')) {
            return $this->paginateResult($request, [], 0);
        }
        $query = DB::table('tbl_order_giftcards as og')
            ->join('tbl_orders as o', 'o.order_id', '=', 'og.ordgift_order_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
            ->select([
                'og.ordgift_id as id',
                'o.order_id as order_id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'og.ordgift_amount as amount',
                'og.ordgift_status as status',
            ]);
        $this->applyKeyword($request, $query, ['u.user_email']);

        return $this->runQuery($request, $query, 'og.ordgift_id');
    }

    private function walletOrders(Request $request): array
    {
        $query = DB::table('tbl_orders as o')
            ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
            ->where('o.order_type', AdminOrderHelper::TYPE_WALLET)
            ->select([
                'o.order_id as id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'o.order_net_amount as amount',
                'o.order_payment_status as payment_status',
                'o.order_addedon as created_at',
            ]);
        $this->applyKeyword($request, $query, ['u.user_email']);

        return $this->runQuery($request, $query, 'o.order_id');
    }

    private function videoContent(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_bible_content as v')
            ->leftJoin('tbl_bible_content_lang as vl', function ($join) use ($langId) {
                $join->on('vl.biblecontentlang_biblecontent_id', '=', 'v.biblecontent_id')
                    ->where('vl.biblecontentlang_lang_id', '=', $langId);
            })
            ->select([
                'v.biblecontent_id as id',
                'v.biblecontent_title as identifier',
                'v.biblecontent_title',
                DB::raw('IFNULL(vl.biblecontentlang_biblecontent_title, v.biblecontent_title) as title'),
                'v.biblecontent_url',
                'v.biblecontent_order as display_order',
                'v.biblecontent_active as active',
            ]);
        $this->applyKeyword($request, $query, ['v.biblecontent_title', 'vl.biblecontentlang_biblecontent_title']);
        $active = $request->query('biblecontent_active');
        if ($active !== null && $active !== '') {
            $query->where('v.biblecontent_active', (int) $active);
        }

        $rows = $query
            ->orderByDesc('v.biblecontent_active')
            ->orderBy('v.biblecontent_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => max(1, count($rows)),
                'total' => count($rows),
                'last_page' => 1,
            ],
        ];
    }

    private function abusiveWords(Request $request): array
    {
        $query = DB::table('tbl_abusive_words')
            ->select(['abusive_id as id', 'abusive_keyword']);
        $keyword = trim((string) ($request->query('abusive_keyword', $request->query('keyword', ''))));
        if ($keyword !== '') {
            $query->where('abusive_keyword', 'like', "%{$keyword}%");
        }

        return $this->runQuery($request, $query, 'abusive_id');
    }

    private function metaTags(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_meta_tags as m')
            ->leftJoin('tbl_meta_tags_lang as ml', function ($join) use ($langId) {
                $join->on('ml.metatlang_meta_id', '=', 'm.meta_id')
                    ->where('ml.metatlang_lang_id', '=', $langId);
            })
            ->select([
                'm.meta_id as id',
                'm.meta_identifier as identifier',
                'ml.meta_title as title',
            ]);
        $this->applyKeyword($request, $query, ['m.meta_identifier', 'ml.meta_title']);

        return $this->runQuery($request, $query, 'm.meta_id');
    }

    private function urlRewriting(Request $request): array
    {
        $query = DB::table('tbl_seo_urls as su')
            ->leftJoin('tbl_languages as l', 'l.language_id', '=', 'su.seourl_lang_id')
            ->select([
                'su.seourl_id as id',
                'su.seourl_original',
                'su.seourl_custom',
                'su.seourl_httpcode',
                'su.seourl_lang_id',
                DB::raw('IFNULL(l.language_name, l.language_code) as language_name'),
            ]);

        $this->applyKeyword($request, $query, ['su.seourl_original', 'su.seourl_custom']);

        $langId = $request->integer('seourl_lang_id', 0);
        if ($langId > 0) {
            $query->where('su.seourl_lang_id', '=', $langId);
        }

        return $this->runQuery($request, $query, 'su.seourl_id');
    }

    private function forumQuestions(Request $request): array
    {
        $query = DB::table('tbl_forum_questions as fq')
            ->join('tbl_users as u', 'u.user_id', '=', 'fq.fque_user_id')
            ->where('fq.fque_deleted', 0)
            ->select([
                'fq.fque_id as id',
                'fq.fque_title as title',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'fq.fque_status as status',
                'fq.fque_added_on as created_at',
            ]);
        $this->applyKeyword($request, $query, ['fq.fque_title', 'u.user_email']);

        return $this->runQuery($request, $query, 'fq.fque_id');
    }

    private function forumTags(Request $request): array
    {
        $query = DB::table('tbl_forum_tags')
            ->select(['ftag_id as id', 'ftag_name as title', 'ftag_active as active']);
        $this->applyKeyword($request, $query, ['ftag_name']);

        return $this->runQuery($request, $query, 'ftag_id');
    }

    private function forumReportedQuestions(Request $request): array
    {
        $query = DB::table('tbl_forum_question_reported as fr')
            ->join('tbl_forum_questions as fq', 'fq.fque_id', '=', 'fr.fqr_fque_id')
            ->select([
                'fr.fqr_id as id',
                'fq.fque_title as title',
                'fr.fqr_status as status',
                'fr.fqr_added_on as created_at',
            ]);

        return $this->runQuery($request, $query, 'fr.fqr_id');
    }

    private function forumTagRequests(Request $request): array
    {
        $query = DB::table('tbl_forum_tag_requests as ftr')
            ->join('tbl_users as u', 'u.user_id', '=', 'ftr.ftr_user_id')
            ->select([
                'ftr.ftr_id as id',
                'ftr.ftr_tag_name as title',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'ftr.ftr_status as status',
                'ftr.ftr_added_on as created_at',
            ]);

        return $this->runQuery($request, $query, 'ftr.ftr_id');
    }

    private function forumReportReasons(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_forum_report_issue_reasons as r')
            ->leftJoin('tbl_forum_report_issue_reasons_lang as rl', function ($join) use ($langId) {
                $join->on('rl.frireasonlang_frireason_id', '=', 'r.frireason_id')
                    ->where('rl.frireasonlang_lang_id', '=', $langId);
            })
            ->select([
                'r.frireason_id as id',
                DB::raw('IFNULL(rl.frireason_title, r.frireason_identifier) as title'),
                'r.frireason_active as active',
            ]);

        return $this->runQuery($request, $query, 'r.frireason_id');
    }

    private function subscriptionPlans(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_subscription_plans as sp')
            ->leftJoin('tbl_subscription_plans_lang as spl', function ($join) use ($langId) {
                $join->on('spl.subplang_subplan_id', '=', 'sp.subplan_id')
                    ->where('spl.subplang_lang_id', '=', $langId);
            })
            ->select([
                'sp.subplan_id as id',
                DB::raw('IFNULL(spl.subplan_title, sp.subplan_identifier) as title'),
                'sp.subplan_active as active',
                'sp.subplan_price as amount',
            ]);

        return $this->runQuery($request, $query, 'sp.subplan_id');
    }

    private function courseRequests(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_course_requests as cr')
            ->join('tbl_courses as c', 'c.course_id', '=', 'cr.crsrequest_course_id')
            ->leftJoin('tbl_courses_lang as cl', function ($join) use ($langId) {
                $join->on('cl.course_id', '=', 'c.course_id')->where('cl.course_lang_id', '=', $langId);
            })
            ->select([
                'cr.crsrequest_id as id',
                DB::raw('IFNULL(cl.course_title, c.course_slug) as title'),
                'cr.crsrequest_status as status',
                'cr.crsrequest_added_on as created_at',
            ]);

        return $this->runQuery($request, $query, 'cr.crsrequest_id');
    }

    private function courseEditRequests(Request $request): array
    {
        $query = DB::table('tbl_course_edit_requests as cer')
            ->join('tbl_courses as c', 'c.course_id', '=', 'cer.cedireq_course_id')
            ->select([
                'cer.cedireq_id as id',
                'c.course_slug as title',
                'cer.cedireq_status as status',
                'cer.cedireq_added_on as created_at',
            ]);

        return $this->runQuery($request, $query, 'cer.cedireq_id');
    }

    private function courseRefundRequests(Request $request): array
    {
        $query = DB::table('tbl_course_refund_requests as crr')
            ->join('tbl_users as u', 'u.user_id', '=', 'crr.crsrefund_user_id')
            ->select([
                'crr.crsrefund_id as id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'crr.crsrefund_status as status',
                'crr.crsrefund_amount as amount',
                'crr.crsrefund_added_on as created_at',
            ]);

        return $this->runQuery($request, $query, 'crr.crsrefund_id');
    }

    private function courseLanguages(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_course_languages as cl')
            ->leftJoin('tbl_course_languages_lang as cll', function ($join) use ($langId) {
                $join->on('cll.clanglang_clang_id', '=', 'cl.clang_id')
                    ->where('cll.clanglang_lang_id', '=', $langId);
            })
            ->select([
                'cl.clang_id as id',
                'cl.clang_identifier as identifier',
                DB::raw('IFNULL(cll.clang_name, cl.clang_identifier) as title'),
                'cl.clang_active as active',
            ]);

        return $this->runQuery($request, $query, 'cl.clang_id');
    }

    private function ratingReviews(Request $request): array
    {
        $reviewType = $request->query('ratrev_type', $request->query('type', ''));
        if ($reviewType === 'course') {
            $reviewType = 3;
        }

        $query = DB::table('tbl_rating_reviews as rr')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'rr.ratrev_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'rr.ratrev_teacher_id')
            ->select([
                'rr.ratrev_id as id',
                DB::raw('CONCAT(learner.user_first_name, " ", COALESCE(learner.user_last_name, "")) as learner_name'),
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as teacher_name'),
                'rr.ratrev_title as title',
                'rr.ratrev_overall as rating',
                'rr.ratrev_status as status',
                'rr.ratrev_created as created_at',
                'rr.ratrev_type as review_type',
            ]);

        if ($reviewType !== '' && $reviewType !== null) {
            $query->where('rr.ratrev_type', '=', (int) $reviewType);
        } else {
            $query->where('rr.ratrev_type', '!=', 3);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where(DB::raw('CONCAT(learner.user_first_name, " ", COALESCE(learner.user_last_name, ""))'), 'like', "%{$keyword}%")
                    ->orWhere(DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, ""))'), 'like', "%{$keyword}%")
                    ->orWhere('rr.ratrev_title', 'like', "%{$keyword}%");
            });
        }

        $status = $request->query('status', '');
        if ($status !== '' && $status !== null) {
            $query->where('rr.ratrev_status', (int) $status);
        }

        $dateFrom = trim((string) $request->query('date_from', ''));
        if ($dateFrom !== '') {
            $query->where('rr.ratrev_created', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('date_to', ''));
        if ($dateTo !== '') {
            $query->where('rr.ratrev_created', '<=', $dateTo.' 23:59:59');
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $statusLabels = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Declined',
        ];
        $rows = $query
            ->orderBy('rr.ratrev_status')
            ->orderByDesc('rr.ratrev_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) use ($statusLabels) {
                $data = (array) $row;
                $data['status_label'] = $statusLabels[(int) $data['status']] ?? (string) $data['status'];

                return $data;
            })
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function certificates(Request $request): array
    {
        $langId = $this->langId($request);
        $defaultLangId = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_DEFAULT_LANG')
            ->value('conf_val') ?: 1;
        $languageIds = array_values(array_unique([$langId, $defaultLangId]));

        $rows = DB::table('tbl_certificate_templates')
            ->whereNull('certpl_deleted')
            ->whereIn('certpl_lang_id', $languageIds)
            ->orderByRaw("FIELD(certpl_code, 'course_completion_certificate', 'course_evaluation_certificate', 'evaluation_certificate')")
            ->orderByRaw('certpl_lang_id = ? desc', [$langId])
            ->orderBy('certpl_id')
            ->get([
                'certpl_id',
                'certpl_type',
                'certpl_lang_id',
                'certpl_code',
                'certpl_name',
                'certpl_status',
            ])
            ->unique('certpl_code')
            ->values()
            ->map(fn ($row) => [
                'id' => (string) $row->certpl_code,
                'certpl_id' => (int) $row->certpl_id,
                'certpl_type' => (int) $row->certpl_type,
                'certpl_lang_id' => (int) $row->certpl_lang_id,
                'certpl_code' => (string) $row->certpl_code,
                'certpl_name' => (string) $row->certpl_name,
                'active' => (int) $row->certpl_status,
            ])
            ->all();

        return $this->paginateResult($request, $rows, count($rows));
    }

    private function navigations(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_navigations as n')
            ->leftJoin('tbl_navigations_lang as nl', function ($join) use ($langId) {
                $join->on('nl.navlang_nav_id', '=', 'n.nav_id')->where('nl.navlang_lang_id', '=', $langId);
            })
            ->where('n.nav_deleted', '=', 0)
            ->select([
                'n.nav_id as id',
                'n.nav_identifier as identifier',
                DB::raw('IFNULL(nl.nav_name, n.nav_identifier) as title'),
                'n.nav_type as type',
                'n.nav_active as active',
            ]);
        $this->applyKeyword($request, $query, ['n.nav_identifier', 'nl.nav_name']);

        $rows = $query
            ->orderByDesc('n.nav_active')
            ->orderBy('n.nav_id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->paginateResult($request, $rows, count($rows));
    }

    private function contentBlocks(Request $request): array
    {
        $langId = $this->langId($request);
        $type = max(1, (int) $request->query('type', 1));
        $query = DB::table('tbl_extra_pages as b')
            ->leftJoin('tbl_extra_pages_lang as bl', function ($join) use ($langId) {
                $join->on('bl.epagelang_epage_id', '=', 'b.epage_id')
                    ->where('bl.epagelang_lang_id', '=', $langId);
            })
            ->where('b.epage_type', '=', $type)
            ->select([
                'b.epage_id as id',
                'b.epage_identifier as identifier',
                DB::raw('IFNULL(bl.epage_label, b.epage_identifier) as title'),
                'b.epage_active as active',
                'b.epage_type as type',
                'b.epage_editable as editable',
                'b.epage_order as display_order',
            ]);
        $this->applyKeyword($request, $query, ['b.epage_identifier', 'bl.epage_label']);

        $rows = $query
            ->orderByDesc('b.epage_active')
            ->orderBy('b.epage_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => max(1, count($rows)),
                'total' => count($rows),
                'last_page' => 1,
            ],
        ];
    }

    private function languageLabels(Request $request): array
    {
        $langId = $this->langId($request);
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $query = DB::table('tbl_language_labels as lbl')
            ->join('tbl_languages as lang', function ($join) {
                $join->on('lbl.label_lang_id', '=', 'lang.language_id')
                    ->where('lang.language_active', '=', 1);
            })
            ->where('lbl.label_lang_id', '=', $langId)
            ->select([
                DB::raw('MAX(lbl.label_id) as id'),
                'lbl.label_key',
                DB::raw('MAX(lbl.label_caption) as label_caption'),
            ])
            ->groupBy('lbl.label_key');
        $this->applyKeyword($request, $query, ['lbl.label_key', 'lbl.label_caption']);

        $total = DB::query()->fromSub(clone $query, 'labels')->count();
        $rows = $query
            ->orderBy('lbl.label_key')
            ->orderByRaw('MAX(lbl.label_id) DESC')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function affiliateCommissions(Request $request): array
    {
        $query = DB::table('tbl_affiliate_commissions')
            ->select(['afcommsetting_id as id', 'afcommsetting_fees as amount', 'afcommsetting_active as active']);

        return $this->runQuery($request, $query, 'afcommsetting_id');
    }

    private function orderSubscriptionPlans(Request $request): array
    {
        if (! DB::getSchemaBuilder()->hasTable('tbl_order_subscription_plans')) {
            return $this->paginateResult($request, [], 0);
        }
        $query = DB::table('tbl_order_subscription_plans as osp')
            ->join('tbl_orders as o', 'o.order_id', '=', 'osp.ordsplan_order_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
            ->select([
                'osp.ordsplan_id as id',
                'o.order_id as order_id',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                'osp.ordsplan_amount as amount',
                'osp.ordsplan_status as status',
            ]);

        return $this->runQuery($request, $query, 'osp.ordsplan_id');
    }
}
