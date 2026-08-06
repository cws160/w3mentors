<?php

namespace App\Models;

use App\Services\LegacyPasswordHasher;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'tbl_users';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'user_first_name',
        'user_last_name',
        'user_email',
        'user_username',
        'user_password',
        'user_timezone',
        'user_gender',
        'user_lang_id',
        'user_currency_id',
        'user_country_id',
        'user_is_teacher',
        'user_is_affiliate',
        'user_offline_sessions',
        'user_active',
        'user_verified',
        'user_created',
    ];

    protected $hidden = [
        'user_password',
    ];

    protected $casts = [
        'user_is_teacher' => 'boolean',
        'user_is_affiliate' => 'boolean',
        'user_active' => 'boolean',
        'user_featured' => 'boolean',
        'user_verified' => 'datetime',
        'user_created' => 'datetime',
        'user_deleted' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->user_password;
    }

    public function validateLegacyPassword(string $password): bool
    {
        return LegacyPasswordHasher::check($password, $this->user_password);
    }

    public function scopeActive($query)
    {
        return $query->where('user_active', 1)->whereNull('user_deleted');
    }

    public function scopeTeachers($query)
    {
        return $query->where('user_is_teacher', 1);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('user_verified');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->user_first_name . ' ' . ($this->user_last_name ?? ''));
    }
}
