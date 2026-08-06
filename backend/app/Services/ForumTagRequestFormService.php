<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ForumTagRequestFormService
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_REJECTED = 2;

    /**
     * @return array{data: array<string, mixed>, meta: array<string, mixed>}
     */
    public function getForm(int $userId, int $requestId): array
    {
        $languages = DB::table('tbl_languages')
            ->orderBy('language_name')
            ->get(['language_id as value', 'language_name as label'])
            ->map(fn ($row) => ['value' => (int) $row->value, 'label' => (string) $row->label])
            ->all();

        $data = [
            'id' => 0,
            'name' => '',
            'language_id' => 0,
        ];

        $defaultLangId = (int) (DB::table('tbl_users')->where('user_id', $userId)->value('user_lang_id') ?: 1);

        if ($requestId > 0) {
            $record = DB::table('tbl_forum_tag_requests')
                ->where('ftagreq_id', $requestId)
                ->where('ftagreq_user_id', $userId)
                ->first(['ftagreq_id', 'ftagreq_name', 'ftagreq_language_id', 'ftagreq_status']);

            if (! $record) {
                throw new InvalidArgumentException('Invalid request.');
            }

            $status = (int) $record->ftagreq_status;
            if (in_array($status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
                throw new InvalidArgumentException('This tag request can no longer be edited.');
            }

            $data = [
                'id' => (int) $record->ftagreq_id,
                'name' => (string) $record->ftagreq_name,
                'language_id' => (int) $record->ftagreq_language_id,
            ];
        } else {
            $data['language_id'] = $defaultLangId;
        }

        return [
            'data' => $data,
            'meta' => [
                'languages' => $languages,
                'default_language_id' => (int) (DB::table('tbl_users')
                    ->where('user_id', $userId)
                    ->value('user_lang_id') ?: 1),
            ],
        ];
    }

    /**
     * @return array{id: int}
     */
    public function save(int $userId, int $requestId, string $name, int $langId): array
    {
        $name = $this->sanitizeName(trim($name));
        if (strlen($name) < 2 || strlen($name) > 50) {
            throw new InvalidArgumentException('Tag name must be between 2 and 50 characters.');
        }
        if ($langId < 1) {
            throw new InvalidArgumentException('Please select a language.');
        }

        $existingTag = DB::table('tbl_forum_tags')
            ->where('ftag_name', $name)
            ->where('ftag_language_id', $langId)
            ->exists();
        if ($existingTag) {
            throw new InvalidArgumentException('This forum tag is already available.');
        }

        if (! $this->validateRequest($userId, $requestId, $name, $langId)) {
            throw new InvalidArgumentException($this->lastError);
        }

        $payload = [
            'ftagreq_user_id' => $userId,
            'ftagreq_language_id' => $langId,
            'ftagreq_name' => $name,
            'ftagreq_status' => self::STATUS_PENDING,
        ];

        if ($requestId > 0) {
            DB::table('tbl_forum_tag_requests')
                ->where('ftagreq_id', $requestId)
                ->where('ftagreq_user_id', $userId)
                ->update($payload);

            return ['id' => $requestId];
        }

        $payload['ftagreq_added_on'] = now()->format('Y-m-d H:i:s');
        $id = DB::table('tbl_forum_tag_requests')->insertGetId($payload);

        return ['id' => (int) $id];
    }

    private string $lastError = '';

    private function validateRequest(int $userId, int $requestId, string $name, int $langId): bool
    {
        $existing = DB::table('tbl_forum_tag_requests')
            ->where('ftagreq_name', $name)
            ->where('ftagreq_language_id', $langId)
            ->first(['ftagreq_id', 'ftagreq_status', 'ftagreq_user_id']);

        if ($existing) {
            $status = (int) $existing->ftagreq_status;
            $ownerId = (int) $existing->ftagreq_user_id;
            $existingId = (int) $existing->ftagreq_id;

            if ($status === self::STATUS_APPROVED) {
                $this->lastError = 'This tag request has already been approved.';

                return false;
            }
            if ($status === self::STATUS_PENDING && $ownerId !== $userId) {
                $this->lastError = 'A tag request is already pending approval for this name.';

                return false;
            }
            if ($requestId === 0 && $status === self::STATUS_PENDING && $ownerId === $userId) {
                $this->lastError = 'You have already submitted this tag request and it is pending approval.';

                return false;
            }
            if ($requestId !== $existingId && $ownerId === $userId) {
                $this->lastError = 'You have already submitted a request for this tag.';

                return false;
            }
            if ($status === self::STATUS_REJECTED) {
                $this->lastError = 'This tag request was already rejected.';

                return false;
            }
        }

        if ($requestId > 0) {
            $record = DB::table('tbl_forum_tag_requests')
                ->where('ftagreq_id', $requestId)
                ->where('ftagreq_user_id', $userId)
                ->first(['ftagreq_status']);

            if (! $record) {
                $this->lastError = 'Invalid request.';

                return false;
            }

            $status = (int) $record->ftagreq_status;
            if ($status === self::STATUS_APPROVED) {
                $this->lastError = 'Your tag request has already been approved.';

                return false;
            }
            if ($status === self::STATUS_REJECTED) {
                $this->lastError = 'Your tag request has already been rejected.';

                return false;
            }
        }

        return true;
    }

    public function sanitizeName(string $name): string
    {
        $name = preg_replace('/[\s-]+/', ' ', $name) ?? $name;
        $name = preg_replace('/[\s_]/', '-', $name) ?? $name;

        return strtolower($name);
    }
}
