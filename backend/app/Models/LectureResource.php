<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LectureResource extends Model
{
    protected $table = 'tbl_lectures_resources';

    protected $primaryKey = 'lecsrc_id';

    public $timestamps = false;

    protected $casts = [
        'lecsrc_created' => 'datetime',
        'lecsrc_updated' => 'datetime',
        'lecsrc_deleted' => 'datetime',
    ];

    public function lecture(): BelongsTo
    {
        return $this->belongsTo(Lecture::class, 'lecsrc_lecture_id', 'lecture_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('lecsrc_deleted');
    }
}
