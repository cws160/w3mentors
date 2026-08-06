<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseProgress extends Model
{
    public const STATUS_PENDING = 1;
    public const STATUS_IN_PROGRESS = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_CANCELLED = 4;

    protected $table = 'tbl_course_progresses';

    protected $primaryKey = 'crspro_id';

    public $timestamps = false;

    protected $fillable = [
        'crspro_ordcrs_id',
        'crspro_lecture_id',
        'crspro_progress',
        'crspro_covered',
        'crspro_started',
        'crspro_status',
        'crspro_completed',
    ];

    protected $casts = [
        'crspro_progress' => 'float',
        'crspro_started' => 'datetime',
        'crspro_completed' => 'datetime',
    ];

    public function orderCourse(): BelongsTo
    {
        return $this->belongsTo(OrderCourse::class, 'crspro_ordcrs_id', 'ordcrs_id');
    }

    public function currentLecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class, 'crspro_lecture_id', 'lecture_id');
    }

    public function coveredLectureIds(): array
    {
        if (empty($this->crspro_covered)) {
            return [];
        }

        $decoded = json_decode($this->crspro_covered, true);

        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    public function setCoveredLectureIds(array $ids): void
    {
        $this->crspro_covered = json_encode(array_values(array_unique($ids)));
    }
}
