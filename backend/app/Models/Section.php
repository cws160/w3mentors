<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $table = 'tbl_sections';

    protected $primaryKey = 'section_id';

    public $timestamps = false;

    protected $casts = [
        'section_created' => 'datetime',
        'section_updated' => 'datetime',
        'section_deleted' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'section_course_id', 'course_id');
    }

    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class, 'lecture_section_id', 'section_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('section_deleted');
    }
}
