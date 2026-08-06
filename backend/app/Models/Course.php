<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    public const STATUS_DRAFT = 1;
    public const STATUS_SUBMITTED = 2;
    public const STATUS_PUBLISHED = 3;

    public const TYPE_FREE = 1;
    public const TYPE_PAID = 2;

    protected $table = 'tbl_courses';

    protected $primaryKey = 'course_id';

    public $timestamps = false;

    protected $casts = [
        'course_price' => 'float',
        'course_ratings' => 'float',
        'course_active' => 'boolean',
        'course_certificate' => 'boolean',
        'course_created' => 'datetime',
        'course_updated' => 'datetime',
        'course_deleted' => 'datetime',
    ];

    public function details(): HasOne
    {
        return $this->hasOne(CourseDetail::class, 'course_id', 'course_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'course_user_id', 'user_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'section_course_id', 'course_id');
    }

    public function lectures(): HasMany
    {
        return $this->hasMany(Lecture::class, 'lecture_course_id', 'course_id');
    }

    public function intendedLearners(): HasMany
    {
        return $this->hasMany(IntendedLearner::class, 'coinle_course_id', 'course_id');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('course_active', 1)
            ->whereNull('course_deleted')
            ->where('course_status', self::STATUS_PUBLISHED);
    }

    public function isFree(): bool
    {
        return (int) $this->course_type === self::TYPE_FREE;
    }
}
