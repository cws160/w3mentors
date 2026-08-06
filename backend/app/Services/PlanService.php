<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public const TYPE_LESSON_PLAN_FILE = 33;

    private const MAX_BYTES = 4194304;

    /** @var list<string> */
    private const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'txt', 'doc', 'docx', 'pdf'];

    /**
     * @return array{levels: array<int, string>, max_upload_mb: float, allowed_extensions: list<string>}
     */
    public function formMeta(): array
    {
        return [
            'levels' => [
                1 => 'Beginner',
                2 => 'Upper beginner',
                3 => 'Intermediate',
                4 => 'Upper intermediate',
                5 => 'Advanced',
            ],
            'max_upload_mb' => round(self::MAX_BYTES / 1048576, 2),
            'allowed_extensions' => self::ALLOWED_EXT,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getForTeacher(int $teacherId, int $planId): ?array
    {
        if ($planId < 1) {
            return null;
        }

        $plan = DB::table('tbl_plans')
            ->where('plan_id', $planId)
            ->where('plan_teacher_id', $teacherId)
            ->first(['plan_id', 'plan_title', 'plan_detail', 'plan_level']);

        if (! $plan) {
            return null;
        }

        return [
            'id' => (int) $plan->plan_id,
            'title' => (string) $plan->plan_title,
            'detail' => (string) ($plan->plan_detail ?? ''),
            'level' => (int) $plan->plan_level,
            'files' => $this->filesForPlan($planId),
        ];
    }

    /**
     * @param  array{plan_id?: int, plan_title: string, plan_detail: string, plan_level: int}  $data
     * @param  list<UploadedFile>  $uploads
     */
    public function save(int $teacherId, array $data, array $uploads = []): int
    {
        $planId = (int) ($data['plan_id'] ?? 0);
        $title = trim((string) ($data['plan_title'] ?? ''));
        $detail = trim((string) ($data['plan_detail'] ?? ''));
        $level = (int) ($data['plan_level'] ?? 0);

        if ($title === '' || $detail === '' || $level < 1) {
            throw new \InvalidArgumentException('Title, detail, and level are required.');
        }

        if (strlen($detail) > 500) {
            throw new \InvalidArgumentException('Detail must be 500 characters or fewer.');
        }

        if ($planId > 0) {
            $owner = DB::table('tbl_plans')
                ->where('plan_id', $planId)
                ->value('plan_teacher_id');
            if ((int) $owner !== $teacherId) {
                throw new \InvalidArgumentException('Invalid plan.');
            }
            DB::table('tbl_plans')->where('plan_id', $planId)->update([
                'plan_title' => $title,
                'plan_detail' => $detail,
                'plan_level' => $level,
            ]);
        } else {
            $planId = (int) DB::table('tbl_plans')->insertGetId([
                'plan_teacher_id' => $teacherId,
                'plan_title' => $title,
                'plan_detail' => $detail,
                'plan_level' => $level,
                'plan_tags' => '',
                'plan_links' => '',
            ]);
        }

        foreach ($uploads as $file) {
            $this->storeFile($planId, $file);
        }

        return $planId;
    }

    public function delete(int $teacherId, int $planId): void
    {
        $plan = DB::table('tbl_plans')
            ->where('plan_id', $planId)
            ->where('plan_teacher_id', $teacherId)
            ->first();

        if (! $plan) {
            throw new \InvalidArgumentException('Invalid plan.');
        }

        $files = $this->filesForPlan($planId);
        foreach ($files as $file) {
            $this->removeFileRecord((int) $file['id'], true);
        }

        DB::table('tbl_plans')->where('plan_id', $planId)->delete();
    }

    public function deleteFile(int $teacherId, int $planId, int $fileId): void
    {
        if (! DB::table('tbl_plans')->where('plan_id', $planId)->where('plan_teacher_id', $teacherId)->exists()) {
            throw new \InvalidArgumentException('Invalid plan.');
        }

        $file = DB::table('tbl_attached_files')
            ->where('file_id', $fileId)
            ->where('file_type', self::TYPE_LESSON_PLAN_FILE)
            ->where('file_record_id', $planId)
            ->first();

        if (! $file) {
            throw new \InvalidArgumentException('Invalid file.');
        }

        $this->removeFileRecord($fileId, true);
    }

    /**
     * @return list<array{id: int, name: string, download_url: string}>
     */
    private function filesForPlan(int $planId): array
    {
        return DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_LESSON_PLAN_FILE)
            ->where('file_record_id', $planId)
            ->orderBy('file_order')
            ->get(['file_id', 'file_name'])
            ->map(fn ($row) => [
                'id' => (int) $row->file_id,
                'name' => (string) $row->file_name,
                'download_url' => '/api/v1/image/show-by-id/'.$row->file_id,
            ])
            ->all();
    }

    private function storeFile(int $planId, UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \InvalidArgumentException('File type not allowed: '.$file->getClientOriginalName());
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('File exceeds maximum size: '.$file->getClientOriginalName());
        }

        $uploadRoot = base_path('../user-uploads');
        $relativeDir = date('Y').'/'.date('m').'/';
        $absoluteDir = $uploadRoot.'/'.$relativeDir;
        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $original = preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName()) ?: 'file.'.$ext;
        $fileName = $original;
        while (is_file($absoluteDir.$fileName)) {
            $fileName = time().'-'.$original;
        }

        $relativePath = $relativeDir.$fileName;
        $file->move($absoluteDir, $fileName);

        $order = (int) DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_LESSON_PLAN_FILE)
            ->where('file_record_id', $planId)
            ->max('file_order');

        DB::table('tbl_attached_files')->insert([
            'file_type' => self::TYPE_LESSON_PLAN_FILE,
            'file_lang_id' => 0,
            'file_record_id' => $planId,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_order' => $order + 1,
            'file_added' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function removeFileRecord(int $fileId, bool $unlinkDisk): void
    {
        $row = DB::table('tbl_attached_files')->where('file_id', $fileId)->first();
        if (! $row) {
            return;
        }

        DB::table('tbl_attached_files')->where('file_id', $fileId)->delete();

        if ($unlinkDisk && ! empty($row->file_path)) {
            $path = base_path('../user-uploads/'.$row->file_path);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
