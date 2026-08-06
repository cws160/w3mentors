<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSlideManageService
{
    private const TYPE_HOME_BANNER_DESKTOP = 49;

    private const TYPE_HOME_BANNER_IPAD = 50;

    private const TYPE_HOME_BANNER_MOBILE = 51;

    /** @return array<string, mixed> */
    public function form(int $slideId): array
    {
        if ($slideId < 1) {
            return [
                'slide' => [
                    'slide_id' => 0,
                    'slide_identifier' => '',
                    'slide_active' => 1,
                ],
            ];
        }

        $row = DB::table('tbl_slides')->where('slide_id', $slideId)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Invalid request');
        }

        return [
            'slide' => [
                'slide_id' => (int) $row->slide_id,
                'slide_identifier' => (string) $row->slide_identifier,
                'slide_active' => (int) $row->slide_active,
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    public function setup(array $payload): int
    {
        $slideId = (int) ($payload['slide_id'] ?? 0);
        $identifier = trim((string) ($payload['slide_identifier'] ?? ''));
        if ($identifier === '') {
            throw new \InvalidArgumentException('Banner identifier is required');
        }

        $duplicate = DB::table('tbl_slides')
            ->where('slide_identifier', $identifier)
            ->when($slideId > 0, fn ($query) => $query->where('slide_id', '!=', $slideId))
            ->exists();
        if ($duplicate) {
            throw new \InvalidArgumentException('Banner identifier already exists');
        }

        $values = [
            'slide_identifier' => $identifier,
            'slide_active' => (int) ($payload['slide_active'] ?? 0) === 1 ? 1 : 0,
        ];

        if ($slideId > 0) {
            if (! DB::table('tbl_slides')->where('slide_id', $slideId)->exists()) {
                throw new \InvalidArgumentException('Invalid request');
            }
            DB::table('tbl_slides')->where('slide_id', $slideId)->update($values);

            return $slideId;
        }

        $maxOrder = (int) DB::table('tbl_slides')->max('slide_order');

        return (int) DB::table('tbl_slides')->insertGetId($values + [
            'slide_record_id' => 0,
            'slide_order' => $maxOrder + 1,
        ]);
    }

    public function changeStatus(int $slideId, int $status): void
    {
        $updated = DB::table('tbl_slides')->where('slide_id', $slideId)->update([
            'slide_active' => $status === 1 ? 1 : 0,
        ]);
        if ($updated === 0 && ! DB::table('tbl_slides')->where('slide_id', $slideId)->exists()) {
            throw new \InvalidArgumentException('Invalid request');
        }
    }

    /** @return array<string, mixed>|null */
    public function mediaForm(int $slideId, int $langId): ?array
    {
        if (! $this->exists($slideId)) {
            return null;
        }

        return [
            'slide_id' => $slideId,
            'lang_id' => $langId,
            'display_types' => $this->displayTypes(),
            'images' => $this->images($slideId, $langId),
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @param array<int, UploadedFile|null> $files @return array{ok: bool, message?: string, id?: int} */
    public function uploadMedia(int $slideId, int $langId, array $files): array
    {
        if (! $this->exists($slideId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $displayTypes = $this->displayTypes();
        $existingImages = $this->images($slideId, $langId);
        foreach (array_keys($displayTypes) as $type) {
            if (empty($existingImages[$type]) && empty($files[$type])) {
                return ['ok' => false, 'message' => 'Please upload all required images'];
            }
        }

        $uploadRoot = base_path('../user-uploads');
        if (! is_dir($uploadRoot)) {
            @mkdir($uploadRoot, 0755, true);
        }

        DB::transaction(function () use ($slideId, $langId, $files, $uploadRoot) {
            foreach ($this->displayTypes() as $type => $display) {
                $file = $files[$type] ?? null;
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $ext = strtolower($file->getClientOriginalExtension());
                if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                    throw new \InvalidArgumentException('Invalid file type');
                }

                if ($file->getSize() > 4 * 1024 * 1024) {
                    throw new \InvalidArgumentException('File is too large');
                }

                $oldFiles = DB::table('tbl_attached_files')
                    ->where('file_type', $type)
                    ->where('file_record_id', $slideId)
                    ->where('file_lang_id', $langId)
                    ->get();

                $fileName = 'slide_'.$slideId.'_'.$langId.'_'.$type.'_'.time().'_'.Str::random(6).'.'.$ext;
                $relativePath = date('Y/m').'/'.$fileName;
                $targetDir = $uploadRoot.'/'.dirname($relativePath);
                if (! is_dir($targetDir)) {
                    @mkdir($targetDir, 0755, true);
                }

                $file->move($targetDir, $fileName);

                DB::table('tbl_attached_files')
                    ->where('file_type', $type)
                    ->where('file_record_id', $slideId)
                    ->where('file_lang_id', $langId)
                    ->delete();

                DB::table('tbl_attached_files')->insert([
                    'file_type' => $type,
                    'file_lang_id' => $langId,
                    'file_record_id' => $slideId,
                    'file_name' => $fileName,
                    'file_path' => $relativePath,
                    'file_order' => 0,
                    'file_added' => now()->format('Y-m-d H:i:s'),
                ]);

                foreach ($oldFiles as $old) {
                    if (! empty($old->file_path)) {
                        $oldPath = $uploadRoot.'/'.$old->file_path;
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                }
            }
        });

        return ['ok' => true, 'id' => $slideId];
    }

    /** @param list<int> $slideIds */
    public function updateOrder(array $slideIds): void
    {
        DB::transaction(function () use ($slideIds) {
            foreach (array_values(array_unique($slideIds)) as $index => $slideId) {
                DB::table('tbl_slides')->where('slide_id', $slideId)->update([
                    'slide_order' => $index + 1,
                ]);
            }
        });
    }

    public function delete(int $slideId): void
    {
        if (! DB::table('tbl_slides')->where('slide_id', $slideId)->exists()) {
            throw new \InvalidArgumentException('Invalid request');
        }

        DB::transaction(function () use ($slideId) {
            DB::table('tbl_attached_files')
                ->where('file_record_id', $slideId)
                ->whereIn('file_type', [49, 50, 51])
                ->delete();
            DB::table('tbl_slides')->where('slide_id', $slideId)->delete();
        });
    }

    /** @return array<int, string> */
    private function displayTypes(): array
    {
        return [
            self::TYPE_HOME_BANNER_DESKTOP => 'Desktop',
            self::TYPE_HOME_BANNER_IPAD => 'Tablet',
            self::TYPE_HOME_BANNER_MOBILE => 'Mobile',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function images(int $slideId, int $langId): array
    {
        return DB::table('tbl_attached_files')
            ->whereIn('file_type', array_keys($this->displayTypes()))
            ->where('file_record_id', $slideId)
            ->where('file_lang_id', $langId)
            ->where('file_path', '!=', '')
            ->get(['file_type', 'file_id', 'file_name', 'file_added'])
            ->mapWithKeys(fn ($row) => [
                (int) $row->file_type => [
                    'file_id' => (int) $row->file_id,
                    'file_name' => (string) $row->file_name,
                    'file_added' => (string) $row->file_added,
                ],
            ])
            ->all();
    }

    /** @return list<array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('tbl_languages')) {
            return [];
        }

        $columns = DB::getSchemaBuilder()->getColumnListing('tbl_languages');
        $idColumn = in_array('language_id', $columns, true) ? 'language_id' : 'lang_id';
        $nameColumn = in_array('language_name', $columns, true) ? 'language_name' : 'lang_name';

        return DB::table('tbl_languages')
            ->orderBy($idColumn)
            ->get([$idColumn.' as id', $nameColumn.' as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function exists(int $slideId): bool
    {
        return DB::table('tbl_slides')->where('slide_id', $slideId)->exists();
    }
}
