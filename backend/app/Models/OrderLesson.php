<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLesson extends Model
{
    protected $table = 'tbl_order_lessons';

    protected $primaryKey = 'ordles_id';

    public $timestamps = false;

    protected $casts = [
        'ordles_lesson_starttime' => 'datetime',
        'ordles_lesson_endtime' => 'datetime',
        'ordles_amount' => 'float',
        'ordles_offline' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordles_teacher_id', 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'ordles_order_id', 'order_id');
    }
}
