<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderCourse extends Model
{
    public const STATUS_PENDING = 1;
    public const STATUS_IN_PROGRESS = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_CANCELLED = 4;

    protected $table = 'tbl_order_courses';

    protected $primaryKey = 'ordcrs_id';

    public $timestamps = false;

    protected $casts = [
        'ordcrs_amount' => 'float',
        'ordcrs_updated' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'ordcrs_order_id', 'order_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'ordcrs_course_id', 'course_id');
    }

    public function progress(): HasOne
    {
        return $this->hasOne(CourseProgress::class, 'crspro_ordcrs_id', 'ordcrs_id');
    }

    public function scopeActive($query)
    {
        return $query->where('ordcrs_status', '!=', self::STATUS_CANCELLED);
    }
}
