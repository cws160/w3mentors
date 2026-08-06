<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lecture extends Model
{
    public const RESOURCE_EXTERNAL_URL = 1;
    public const RESOURCE_UPLOAD_FILE = 2;
    public const RESOURCE_LIBRARY = 3;

    protected $table = 'tbl_lectures';

    protected $primaryKey = 'lecture_id';

    public $timestamps = false;

    protected $casts = [
        'lecture_is_trial' => 'boolean',
        'lecture_archived' => 'boolean',
        'lecture_created' => 'datetime',
        'lecture_updated' => 'datetime',
        'lecture_deleted' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'lecture_course_id', 'course_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'lecture_section_id', 'section_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LectureResource::class, 'lecsrc_lecture_id', 'lecture_id');
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNull('lecture_deleted')
            ->where('lecture_archived', 0);
    }
}
