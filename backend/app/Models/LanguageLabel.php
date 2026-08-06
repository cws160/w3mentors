<?php

namespace App\Models;

use App\Support\Branding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LanguageLabel extends Model
{
    protected $table = 'tbl_language_labels';

    protected $primaryKey = 'label_id';

    public $timestamps = false;

    public static function forLanguage(int $langId = 1): array
    {
        return Cache::remember("labels_{$langId}", 3600, function () use ($langId) {
            $labels = static::query()
                ->where('label_lang_id', $langId)
                ->pluck('label_caption', 'label_key')
                ->all();

            return Branding::applyToLabels($labels);
        });
    }
}
