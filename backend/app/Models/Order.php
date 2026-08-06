<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'tbl_orders';

    protected $primaryKey = 'order_id';

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'order_user_id', 'user_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(OrderLesson::class, 'ordles_order_id', 'order_id');
    }
}
