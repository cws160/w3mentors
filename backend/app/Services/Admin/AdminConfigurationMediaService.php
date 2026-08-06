<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AdminConfigurationMediaService
{
  private const TYPE_FRONT_LOGO = 6;

  private const TYPE_FAVICON = 12;

  private const TYPE_HOME_BANNER_BACKGROUND = 34;

  private const TYPE_LESSON_PAGE_IMAGE = 44;

  private const TYPE_PWA_APP_ICON = 46;

  private const TYPE_APPLY_TO_TEACH_BANNER = 52;

  private const TYPE_CERTIFICATE_LOGO = 61;

  private const TYPE_AFFILIATE_REGISTRATION_BANNER = 67;

  /** @return list<array<string, mixed>> */
  public function mediaSlots(bool $coursesEnabled): array
  {
    $slots = [
      [
        'file_type' => self::TYPE_FRONT_LOGO,
        'label_key' => 'LBL_WEBSITE_LOGO',
        'label_fallback' => 'Website logo',
        'disclaimer_type' => 'size',
        'disclaimer_width' => 200,
        'disclaimer_height' => 100,
        'preview_size' => 'MEDIUM',
      ],
      [
        'file_type' => self::TYPE_FAVICON,
        'label_key' => 'LBL_WEBSITE_FAVICON',
        'label_fallback' => 'Website favicon',
        'disclaimer_type' => 'size',
        'disclaimer_width' => 200,
        'disclaimer_height' => 100,
        'preview_size' => 'ORIGINAL',
      ],
      [
        'file_type' => self::TYPE_HOME_BANNER_BACKGROUND,
        'label_key' => 'LBL_HOME_BANNER_BACKGROUND',
        'label_fallback' => 'Home banner background',
        'disclaimer_type' => 'dimensions',
        'disclaimer_dimensions' => '2000*600',
        'preview_size' => 'MEDIUM',
      ],
      [
        'file_type' => self::TYPE_LESSON_PAGE_IMAGE,
        'label_key' => 'LBL_LESSON_BANNER',
        'label_fallback' => 'Lesson banner',
        'disclaimer_type' => 'dimensions',
        'disclaimer_dimensions' => '2000*600',
        'preview_size' => 'SMALL',
      ],
      [
        'file_type' => self::TYPE_APPLY_TO_TEACH_BANNER,
        'label_key' => 'LBL_APPLY_TO_TEACH_BANNER',
        'label_fallback' => 'Apply to teach banner',
        'disclaimer_type' => 'dimensions',
        'disclaimer_dimensions' => '2000*900',
        'preview_size' => 'SMALL',
      ],
    ];

    if ($coursesEnabled) {
      $slots[] = [
        'file_type' => self::TYPE_CERTIFICATE_LOGO,
        'label_key' => 'LBL_CERTIFICATE_LOGO',
        'label_fallback' => 'Certificate logo',
        'disclaimer_type' => 'dimensions',
        'disclaimer_dimensions' => '140*47',
        'preview_size' => 'SMALL',
      ];
    }

    $slots[] = [
      'file_type' => self::TYPE_AFFILIATE_REGISTRATION_BANNER,
      'label_key' => 'LBL_AFFILIATE_REGISTRATION_PAGE_BANNER',
      'label_fallback' => 'Affiliate registration page banner',
      'disclaimer_type' => 'dimensions',
      'disclaimer_dimensions' => '2000*900',
      'preview_size' => 'SMALL',
    ];

    return $slots;
  }

  /** @return array<string, mixed> */
  public function mediaForm(int $langId, bool $coursesEnabled): array
  {
    $files = $this->filesForLang($langId);
    $slots = [];

    foreach ($this->mediaSlots($coursesEnabled) as $slot) {
      $fileType = (int) $slot['file_type'];
      $file = $files[$fileType] ?? null;
      $previewSize = (string) ($slot['preview_size'] ?? 'MEDIUM');
      $slots[] = [
        ...$slot,
        'file_id' => $file ? (int) $file->file_id : null,
        'has_custom_file' => $file !== null,
        'preview_url' => $file
          ? $this->imageUrlById((int) $file->file_id, $previewSize)
          : $this->imageUrlByType($fileType, $langId, $previewSize),
      ];
    }

    return [
      'lang_id' => $langId,
      'slots' => $slots,
    ];
  }

  /** @return array{ok: bool, message?: string, slot?: array<string, mixed>} */
  public function upload(int $fileType, int $langId, UploadedFile $file): array
  {
    $allowed = array_column($this->mediaSlots(true), 'file_type');
    if (! in_array($fileType, $allowed, true)) {
      return ['ok' => false, 'message' => 'Invalid media type.'];
    }

    $ext = strtolower($file->getClientOriginalExtension() ?: '');
    if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'ico'], true)) {
      return ['ok' => false, 'message' => 'Invalid file type.'];
    }

    $uploadRoot = base_path('../user-uploads');
    if (! is_dir($uploadRoot)) {
      @mkdir($uploadRoot, 0755, true);
    }

    $relativeDir = date('Y').'/'.date('m').'/';
    $absoluteDir = $uploadRoot.'/'.$relativeDir;
    if (! is_dir($absoluteDir)) {
      @mkdir($absoluteDir, 0755, true);
    }

    $original = preg_replace('/[^a-zA-Z0-9.]/', '', $file->getClientOriginalName()) ?: 'media.'.$ext;
    $fileName = $original;
    while (is_file($absoluteDir.$fileName)) {
      $fileName = time().'-'.$original;
    }

    $relativePath = $relativeDir.$fileName;
    $file->move($absoluteDir, $fileName);

    $existing = DB::table('tbl_attached_files')
      ->where('file_type', $fileType)
      ->where('file_lang_id', $langId)
      ->where('file_record_id', 0)
      ->orderBy('file_id')
      ->get(['file_id', 'file_path']);

    foreach ($existing as $row) {
      if (! empty($row->file_path)) {
        $path = $uploadRoot.'/'.$row->file_path;
        if (is_file($path)) {
          @unlink($path);
        }
      }
      DB::table('tbl_attached_files')->where('file_id', $row->file_id)->delete();
    }

    $fileId = (int) DB::table('tbl_attached_files')->insertGetId([
      'file_type' => $fileType,
      'file_lang_id' => $langId,
      'file_record_id' => 0,
      'file_name' => $fileName,
      'file_path' => $relativePath,
      'file_order' => 0,
      'file_added' => now()->format('Y-m-d H:i:s'),
    ]);

    return [
      'ok' => true,
      'slot' => [
        'file_type' => $fileType,
        'file_id' => $fileId,
        'has_custom_file' => true,
        'preview_url' => $this->imageUrlById($fileId, 'MEDIUM'),
      ],
    ];
  }

  /** @return array{ok: bool, message?: string} */
  public function remove(int $fileType, int $langId): array
  {
    $rows = DB::table('tbl_attached_files')
      ->where('file_type', $fileType)
      ->where('file_lang_id', $langId)
      ->where('file_record_id', 0)
      ->get(['file_id', 'file_path']);

    if ($rows->isEmpty()) {
      return ['ok' => false, 'message' => 'File not found.'];
    }

    $uploadRoot = base_path('../user-uploads');
    foreach ($rows as $row) {
      if (! empty($row->file_path)) {
        $path = $uploadRoot.'/'.$row->file_path;
        if (is_file($path)) {
          @unlink($path);
        }
      }
      DB::table('tbl_attached_files')->where('file_id', $row->file_id)->delete();
    }

    return ['ok' => true];
  }

  /** @return array{ok: bool, message?: string, icon_url?: string} */
  public function uploadPwaIcon(UploadedFile $file): array
  {
    $ext = strtolower($file->getClientOriginalExtension() ?: '');
    if ($ext !== 'png') {
      return ['ok' => false, 'message' => 'PWA icon must be a PNG file.'];
    }

    $result = $this->upload(self::TYPE_PWA_APP_ICON, 0, $file);
    if (! $result['ok']) {
      return $result;
    }

    return [
      'ok' => true,
      'icon_url' => $this->imageUrlById((int) ($result['slot']['file_id'] ?? 0), 'SMALL'),
    ];
  }

  public function pwaIconUrl(): ?string
  {
    $file = DB::table('tbl_attached_files')
      ->where('file_type', self::TYPE_PWA_APP_ICON)
      ->where('file_record_id', 0)
      ->orderByDesc('file_id')
      ->first(['file_id']);

    if (! $file) {
      return null;
    }

    return $this->imageUrlById((int) $file->file_id, 'SMALL');
  }

  /** @return array<int, object> */
  private function filesForLang(int $langId): array
  {
    $types = array_column($this->mediaSlots(true), 'file_type');

    return DB::table('tbl_attached_files')
      ->whereIn('file_type', $types)
      ->where('file_lang_id', $langId)
      ->where('file_record_id', 0)
      ->where('file_path', '!=', '')
      ->get(['file_type', 'file_id', 'file_path'])
      ->keyBy('file_type')
      ->all();
  }

  private function imageUrlById(int $fileId, string $size): string
  {
    return '/api/v1/image/show-by-id/'.$fileId.'/'.$size;
  }

  private function imageUrlByType(int $fileType, int $langId, string $size): string
  {
    return '/api/v1/image/show/'.$fileType.'/0/'.$size.'/'.$langId;
  }
}
