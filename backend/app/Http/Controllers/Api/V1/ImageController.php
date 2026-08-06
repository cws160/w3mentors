<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    private const UPLOADS_ROOT = 'user-uploads';

    private const TYPE_CATEGORY_IMAGE = 64;

    private const TYPE_USER_PROFILE_IMAGE = 4;

    private const TYPE_ADMIN_PROFILE_IMAGE = 15;

    public function flag(string $code): BinaryFileResponse|Response
    {
        $code = strtolower($code);
        if (! preg_match('/^[a-z0-9]{2,3}$/', $code)) {
            abort(404);
        }

        foreach ([
            base_path('../public/flags/'.$code.'.svg'),
            base_path('../public/flags/'.$code.'.png'),
            public_path('flags/'.$code.'.svg'),
            public_path('flags/'.$code.'.png'),
        ] as $file) {
            if (is_file($file)) {
                return response()->file($file, [
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        abort(404);
    }

    /**
     * Serve CMS / editor assets (legacy ImageController::editorImage).
     */
    public function editorImage(string $path): BinaryFileResponse|Response
    {
        $path = str_replace('\\', '/', $path);
        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $staticFirst = preg_match('#^(?:payment-method|forum)/#', $path) === 1;

        $candidates = $staticFirst
            ? [
                base_path('../frontend/public/images/'.$path),
                base_path('../public/images/'.$path),
                public_path('images/'.$path),
                base_path('../'.self::UPLOADS_ROOT.'/editor/'.$path),
            ]
            : [
                base_path('../'.self::UPLOADS_ROOT.'/editor/'.$path),
                base_path('../public/images/'.$path),
                base_path('../frontend/public/image/editor-image/'.$path),
                base_path('../frontend/public/images/'.$path),
                public_path('images/'.$path),
            ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                return response()->file($file, [
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        $fallback = base_path('../public/images/no-image.png');
        if (is_file($fallback)) {
            return response()->file($fallback, [
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        abort(404);
    }

    public function showById(int $fileId, string $size = ''): BinaryFileResponse|Response
    {
        $file = DB::table('tbl_attached_files')->where('file_id', $fileId)->first();
        if (!$file) {
            abort(404);
        }

        $path = base_path('../'.self::UPLOADS_ROOT.'/'.$file->file_path);
        if (!is_file($path)) {
            $path = $this->defaultImagePath((int) $file->file_type);
        }

        if ($path !== null && is_file($path)) {
            return response()->file($path, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        abort(404);
    }

    public function show(Request $request, int $fileType, int $recordId = 0, string $size = '', int $langId = 0): BinaryFileResponse|Response
    {
        $langId = $langId > 0 ? $langId : 1;

        $path = $this->resolveUploadedFilePath($fileType, $recordId, $langId);

        if ($path === null && $fileType === self::TYPE_CATEGORY_IMAGE && $recordId > 0) {
            $path = $this->resolveCategoryImageByIdentifier($recordId);
        }

        if ($path === null) {
            $path = $this->defaultImagePath($fileType);
        }

        if ($path !== null && is_file($path)) {
            return response()->file($path, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        abort(404);
    }

    private function resolveUploadedFilePath(int $fileType, int $recordId, int $langId): ?string
    {
        $query = DB::table('tbl_attached_files')
            ->where('file_type', $fileType)
            ->where('file_record_id', $recordId)
            ->orderBy('file_order');

        $file = (clone $query)->where('file_lang_id', $langId)->first()
            ?? $query->where('file_lang_id', 0)->first();

        if (!$file) {
            return null;
        }

        $path = base_path('../'.self::UPLOADS_ROOT.'/'.$file->file_path);

        return is_file($path) ? $path : null;
    }

    /**
     * Match legacy category icons by identifier slug (e.g. "Mathematics" → Mathematics.png).
     */
    private function resolveCategoryImageByIdentifier(int $recordId): ?string
    {
        $identifier = DB::table('tbl_categories')
            ->where('cate_id', $recordId)
            ->value('cate_identifier');

        if (!is_string($identifier) || $identifier === '') {
            return null;
        }

        $slug = $this->categoryImageSlug($identifier);
        if ($slug === '') {
            return null;
        }

        $names = array_unique([$slug, str_replace(' ', '', $identifier)]);

        foreach ($names as $name) {
            foreach ($this->categoryImageCandidates($name) as $file) {
                if (is_file($file)) {
                    return $file;
                }
            }
        }

        $siblingId = DB::table('tbl_categories as c')
            ->join('tbl_attached_files as f', function ($join) {
                $join->on('f.file_record_id', '=', 'c.cate_id')
                    ->where('f.file_type', '=', self::TYPE_CATEGORY_IMAGE);
            })
            ->where('c.cate_identifier', $identifier)
            ->where('c.cate_id', '!=', $recordId)
            ->orderBy('c.cate_parent')
            ->value('c.cate_id');

        if ($siblingId) {
            return $this->resolveUploadedFilePath(self::TYPE_CATEGORY_IMAGE, (int) $siblingId, 1);
        }

        return null;
    }

    /** @return list<string> */
    private function categoryImageCandidates(string $basename): array
    {
        $exts = ['png', 'jpg', 'jpeg'];

        $dirs = [
            base_path('../'.self::UPLOADS_ROOT.'/2023/03'),
            base_path('../frontend/public/images/categories'),
            base_path('../public/images/categories'),
        ];

        $files = [];
        foreach ($dirs as $dir) {
            foreach ($exts as $ext) {
                $files[] = $dir.'/'.$basename.'.'.$ext;
            }
        }

        return $files;
    }

    private function categoryImageSlug(string $identifier): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $identifier) ?? '';
    }

    private function defaultImagePath(int $fileType): ?string
    {
        $image = match ($fileType) {
            self::TYPE_CATEGORY_IMAGE => 'no-image-catg.png',
            self::TYPE_USER_PROFILE_IMAGE, self::TYPE_ADMIN_PROFILE_IMAGE => 'no-image-user.png',
            default => 'no-image.png',
        };

        foreach ([
            base_path('../public/images/'.$image),
            base_path('../frontend/public/images/'.$image),
            public_path('images/'.$image),
        ] as $file) {
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }
}
