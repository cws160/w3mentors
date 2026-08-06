<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UserProfileMediaService
{
    private const TYPE_USER_PROFILE_IMAGE = 4;

    private const TYPE_OPENGRAPH_IMAGE = 48;

    private const MAX_BYTES = 4194304;

    /** @var list<string> */
    private const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'gif', 'bmp'];

    private const VIDEO_REGEX = '/^(?:https?:)?(?:\/\/)?(?:youtu\.be\/|(?:www\.|m\.)?youtube\.com\/(?:watch|v|embed)(?:\.php)?(?:\?.*v=|\/))([a-zA-Z0-9\_-]{7,15})(?:[\?&][a-zA-Z0-9\_-]+=[a-zA-Z0-9\_-]+)*(?:[&\/\#].*)?$/i';

    public function getPhotosForm(int $userId, bool $isTeacher): array
    {
        $hasImage = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_USER_PROFILE_IMAGE)
            ->where('file_record_id', $userId)
            ->exists();

        $videoLink = '';
        if ($isTeacher) {
            $videoLink = (string) (DB::table('tbl_user_settings')
                ->where('user_id', $userId)
                ->value('user_video_link') ?? '');
        }

        return [
            'has_image' => $hasImage,
            'video_link' => $videoLink,
            'is_teacher' => $isTeacher,
            'max_upload_mb' => round(self::MAX_BYTES / 1048576, 2),
            'allowed_extensions' => self::ALLOWED_EXT,
            'image_urls' => [
                'xlarge' => $this->imageUrl($userId, 'MEDIUM'),
                'large' => $this->imageUrl($userId, 'LARGE'),
                'medium' => $this->imageUrl($userId, 'MEDIUM'),
                'small' => $this->imageUrl($userId, 'SMALL'),
            ],
        ];
    }

    public function saveProfileImage(int $userId, UploadedFile $file, bool $isTeacher): void
    {
        $this->validateImage($file);

        $uploadRoot = base_path('../user-uploads');
        $relativeDir = date('Y').'/'.date('m').'/';
        $absoluteDir = $uploadRoot.'/'.$relativeDir;
        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0777, true);
        }

        $original = preg_replace('/[^a-zA-Z0-9.]/', '', $file->getClientOriginalName()) ?: 'profile.jpg';
        $fileName = $original;
        while (is_file($absoluteDir.$fileName)) {
            $fileName = time().'-'.$original;
        }

        $relativePath = $relativeDir.$fileName;
        $file->move($absoluteDir, $fileName);

        $oldFile = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_USER_PROFILE_IMAGE)
            ->where('file_record_id', $userId)
            ->orderBy('file_id')
            ->first();

        $fileId = DB::table('tbl_attached_files')->insertGetId([
            'file_type' => self::TYPE_USER_PROFILE_IMAGE,
            'file_lang_id' => 0,
            'file_record_id' => $userId,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_order' => 0,
            'file_added' => now()->format('Y-m-d H:i:s'),
        ]);

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_USER_PROFILE_IMAGE)
            ->where('file_record_id', $userId)
            ->where('file_id', '!=', $fileId)
            ->delete();

        if ($oldFile && ! empty($oldFile->file_path)) {
            $oldPath = $uploadRoot.'/'.$oldFile->file_path;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        if ($isTeacher) {
            $this->syncOpenGraphImage($userId, $relativePath, $fileName);
        }
    }

    public function removeProfileImage(int $userId): void
    {
        $files = DB::table('tbl_attached_files')
            ->whereIn('file_type', [self::TYPE_USER_PROFILE_IMAGE, self::TYPE_OPENGRAPH_IMAGE])
            ->where('file_record_id', $userId)
            ->get();

        DB::table('tbl_attached_files')
            ->whereIn('file_type', [self::TYPE_USER_PROFILE_IMAGE, self::TYPE_OPENGRAPH_IMAGE])
            ->where('file_record_id', $userId)
            ->delete();

        $uploadRoot = base_path('../user-uploads');
        foreach ($files as $file) {
            if (! empty($file->file_path)) {
                $path = $uploadRoot.'/'.$file->file_path;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    public function updateVideoLink(int $userId, ?string $videoLink, bool $isTeacher): void
    {
        if (! $isTeacher) {
            return;
        }

        $videoLink = trim((string) $videoLink);
        if ($videoLink !== '' && ! preg_match(self::VIDEO_REGEX, $videoLink)) {
            throw new \InvalidArgumentException('Please enter a valid YouTube video link.');
        }

        DB::table('tbl_user_settings')->updateOrInsert(
            ['user_id' => $userId],
            ['user_id' => $userId, 'user_video_link' => $videoLink]
        );
    }

    private function validateImage(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('File could not be uploaded.');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('File size should be less than 4 MB.');
        }
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \InvalidArgumentException('Invalid file extension.');
        }
    }

    private function syncOpenGraphImage(int $userId, string $relativePath, string $fileName): void
    {
        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_OPENGRAPH_IMAGE)
            ->where('file_record_id', $userId)
            ->delete();

        DB::table('tbl_attached_files')->insert([
            'file_type' => self::TYPE_OPENGRAPH_IMAGE,
            'file_lang_id' => 0,
            'file_record_id' => $userId,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_order' => 0,
            'file_added' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function imageUrl(int $userId, string $size): string
    {
        return "/image/show/".self::TYPE_USER_PROFILE_IMAGE."/{$userId}/{$size}";
    }
}
