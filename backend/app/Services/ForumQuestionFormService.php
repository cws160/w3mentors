<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ForumQuestionFormService
{
    public const STATUS_DRAFT = 0;

    public const STATUS_PUBLISHED = 1;

    public const STATUS_RESOLVED = 2;

    public const STATUS_SPAMMED = 3;

    public const TITLE_MIN = 10;

    public const TITLE_MAX = 150;

    public const TAGS_LIMIT = 5;

    /**
     * @return array{data: array<string, mixed>, meta: array<string, mixed>}
     */
    public function getForm(int $userId, int $questionId): array
    {
        $languages = DB::table('tbl_languages')
            ->orderBy('language_name')
            ->get(['language_id as value', 'language_name as label'])
            ->map(fn ($row) => ['value' => (int) $row->value, 'label' => (string) $row->label])
            ->all();

        $defaultLangId = (int) (DB::table('tbl_users')->where('user_id', $userId)->value('user_lang_id') ?: 1);

        $data = [
            'id' => 0,
            'title' => '',
            'slug' => '',
            'description' => '',
            'language_id' => $defaultLangId,
            'status' => self::STATUS_DRAFT,
            'comments_allowed' => 0,
            'tags' => [],
            'can_edit_status' => true,
            'status_alert' => null,
        ];

        if ($questionId > 0) {
            $record = DB::table('tbl_forum_questions')
                ->where('fque_id', $questionId)
                ->where('fque_user_id', $userId)
                ->where('fque_deleted', 0)
                ->first([
                    'fque_id',
                    'fque_title',
                    'fque_slug',
                    'fque_description',
                    'fque_lang_id',
                    'fque_status',
                    'fque_comments_allowed',
                ]);

            if (! $record) {
                throw new InvalidArgumentException('Invalid request.');
            }

            $status = (int) $record->fque_status;
            if ($status === self::STATUS_RESOLVED) {
                throw new InvalidArgumentException('Cannot make changes on resolved status.');
            }
            if ($status === self::STATUS_SPAMMED) {
                throw new InvalidArgumentException('Cannot make changes on spammed status.');
            }

            $tags = DB::table('tbl_forum_tags_to_question as ftq')
                ->join('tbl_forum_tags as ftag', 'ftag.ftag_id', '=', 'ftq.ftagque_ftag_id')
                ->where('ftq.ftagque_fque_id', $questionId)
                ->where('ftag.ftag_active', 1)
                ->where('ftag.ftag_deleted', 0)
                ->orderBy('ftag.ftag_name')
                ->get(['ftag.ftag_id', 'ftag.ftag_name'])
                ->map(fn ($row) => ['id' => (int) $row->ftag_id, 'name' => (string) $row->ftag_name])
                ->all();

            $data = [
                'id' => (int) $record->fque_id,
                'title' => (string) $record->fque_title,
                'slug' => (string) $record->fque_slug,
                'description' => (string) $record->fque_description,
                'language_id' => (int) $record->fque_lang_id,
                'status' => $status === self::STATUS_PUBLISHED ? self::STATUS_PUBLISHED : self::STATUS_DRAFT,
                'comments_allowed' => (int) ($record->fque_comments_allowed ?? 0) === 1 ? 1 : 0,
                'tags' => $tags,
                'can_edit_status' => ! in_array($status, [self::STATUS_RESOLVED, self::STATUS_SPAMMED], true),
                'status_alert' => match ($status) {
                    self::STATUS_RESOLVED => 'resolved',
                    self::STATUS_SPAMMED => 'spammed',
                    default => null,
                },
            ];
        }

        return [
            'data' => $data,
            'meta' => [
                'languages' => $languages,
                'default_language_id' => $defaultLangId,
                'title_min' => self::TITLE_MIN,
                'title_max' => self::TITLE_MAX,
                'tags_limit' => self::TAGS_LIMIT,
            ],
        ];
    }

    /**
     * @param  array{
     *     fque_id?: int,
     *     fque_title: string,
     *     fque_slug: string,
     *     fque_description: string,
     *     fque_lang_id: int,
     *     fque_status: int,
     *     fque_comments_allowed?: int,
     *     fque_sel_tags?: string
     * }  $input
     * @return array{id: int}
     */
    public function save(int $userId, array $input): array
    {
        $questionId = (int) ($input['fque_id'] ?? 0);
        $title = $this->sanitizeTitle(trim((string) ($input['fque_title'] ?? '')));
        $slugInput = trim((string) ($input['fque_slug'] ?? ''));
        $description = trim((string) ($input['fque_description'] ?? ''));
        $langId = (int) ($input['fque_lang_id'] ?? 0);
        $status = (int) ($input['fque_status'] ?? 0) === self::STATUS_PUBLISHED
            ? self::STATUS_PUBLISHED
            : self::STATUS_DRAFT;
        $commentsAllowed = (int) ($input['fque_comments_allowed'] ?? 0) === 1 ? 1 : 0;
        $tagIds = $this->parseTagIds((string) ($input['fque_sel_tags'] ?? ''));

        if (strlen($title) < self::TITLE_MIN || strlen($title) > self::TITLE_MAX) {
            throw new InvalidArgumentException(
                'Title must be between '.self::TITLE_MIN.' and '.self::TITLE_MAX.' characters.'
            );
        }

        $slug = $this->seoUrl($slugInput);
        if (strlen($slug) < self::TITLE_MIN || strlen($slug) > self::TITLE_MAX) {
            throw new InvalidArgumentException(
                'Slug must be between '.self::TITLE_MIN.' and '.self::TITLE_MAX.' characters.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException('Description is required.');
        }

        if ($langId < 1) {
            throw new InvalidArgumentException('Please select a language.');
        }

        if (count($tagIds) > self::TAGS_LIMIT) {
            throw new InvalidArgumentException('A maximum of '.self::TAGS_LIMIT.' tags can be bound with a question.');
        }

        $existingPublishedOn = null;
        $existingStatus = null;

        if ($questionId > 0) {
            $existing = DB::table('tbl_forum_questions')
                ->where('fque_id', $questionId)
                ->where('fque_user_id', $userId)
                ->where('fque_deleted', 0)
                ->first(['fque_id', 'fque_status', 'fque_published_on']);

            if (! $existing) {
                throw new InvalidArgumentException('Invalid request.');
            }

            $existingStatus = (int) $existing->fque_status;
            if ($existingStatus === self::STATUS_RESOLVED) {
                throw new InvalidArgumentException('Cannot make changes on resolved status.');
            }
            if ($existingStatus === self::STATUS_SPAMMED) {
                throw new InvalidArgumentException('Cannot make changes on spammed status.');
            }

            $existingPublishedOn = $existing->fque_published_on;
        }

        $slug = $this->resolveUniqueSlug($slug, $questionId);

        $now = now()->format('Y-m-d H:i:s');
        $publishedOn = null;
        if ($status === self::STATUS_PUBLISHED) {
            if ($questionId > 0 && $existingPublishedOn !== null) {
                $publishedOn = (string) $existingPublishedOn;
            } else {
                $publishedOn = $now;
            }
        }

        $payload = [
            'fque_user_id' => $userId,
            'fque_title' => $title,
            'fque_slug' => $slug,
            'fque_description' => $description,
            'fque_lang_id' => $langId,
            'fque_status' => $status,
            'fque_comments_allowed' => $commentsAllowed,
            'fque_published_on' => $publishedOn,
            'fque_updated_on' => $now,
        ];

        if ($questionId > 0) {
            DB::table('tbl_forum_questions')
                ->where('fque_id', $questionId)
                ->where('fque_user_id', $userId)
                ->update($payload);
        } else {
            $payload['fque_added_on'] = $now;
            $questionId = (int) DB::table('tbl_forum_questions')->insertGetId($payload);
        }

        DB::table('tbl_forum_tags_to_question')->where('ftagque_fque_id', $questionId)->delete();
        if ($tagIds !== []) {
            $rows = array_map(
                fn (int $tagId) => ['ftagque_fque_id' => $questionId, 'ftagque_ftag_id' => $tagId],
                $tagIds
            );
            DB::table('tbl_forum_tags_to_question')->insert($rows);
        }

        return ['id' => $questionId];
    }

    public function formatSlug(string $slug): string
    {
        $slug = str_replace("'", '', $slug);
        $slug = preg_replace('/[^\p{L}\p{N}]/u', '_', $slug) ?? $slug;
        $slug = $this->removeUnderscore($slug);

        return $this->removeHyphens($slug);
    }

    /**
     * @return array<int>
     */
    private function parseTagIds(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $ids = array_filter(array_map('intval', explode(',', $raw)));

        return array_values(array_unique($ids));
    }

    private function sanitizeTitle(string $title): string
    {
        $title = preg_replace('/[\s-]+/', ' ', $title) ?? $title;

        return trim($title, ' ');
    }

    private function seoUrl(string $string): string
    {
        $string = trim($string);
        $string = preg_replace('/[\s,<>\/"&#%+?$@=]/', '-', $string) ?? $string;
        $string = preg_replace('/[\s-]+/', '-', $string) ?? $string;
        $string = preg_replace('/-+/', '-', $string) ?? $string;

        return trim($string, '-');
    }

    private function resolveUniqueSlug(string $slug, int $questionId): string
    {
        $slug = $this->seoUrl($slug);
        $existing = DB::table('tbl_forum_questions')
            ->where('fque_slug', $slug)
            ->when($questionId > 0, fn ($q) => $q->where('fque_id', '!=', $questionId))
            ->value('fque_slug');

        if ($existing !== null) {
            $suffix = $questionId > 0 ? (string) $questionId : (string) random_int(1, 100);

            return $this->seoUrl($slug.'-'.$suffix);
        }

        return $slug;
    }

    private function removeHyphens(string $slug): string
    {
        $slug = str_replace('--', '-', $slug);
        if (str_contains($slug, '--')) {
            return $this->removeHyphens($slug);
        }

        return trim($slug, '-');
    }

    private function removeUnderscore(string $slug): string
    {
        $slug = str_replace('__', '_', $slug);
        if (str_contains($slug, '__')) {
            return $this->removeUnderscore($slug);
        }

        return trim($slug, '_');
    }
}
