<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntendedLearner extends Model
{
    public const TYPE_LEARNING = 1;
    public const TYPE_REQUIREMENTS = 2;
    public const TYPE_LEARNERS = 3;

    protected $table = 'tbl_courses_intended_learners';

    protected $primaryKey = 'coinle_id';

    public $timestamps = false;

    protected $casts = [
        'coinle_created' => 'datetime',
        'coinle_updated' => 'datetime',
        'coinle_deleted' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'coinle_course_id', 'course_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('coinle_deleted');
    }
}
