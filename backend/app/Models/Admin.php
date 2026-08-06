<?php

namespace App\Models;

use App\Services\LegacyPasswordHasher;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    use HasApiTokens;

    protected $table = 'tbl_admin';

    protected $primaryKey = 'admin_id';

    public $timestamps = false;

    protected $fillable = [
        'admin_username',
        'admin_password',
        'admin_email',
        'admin_name',
        'admin_timezone',
        'admin_active',
    ];

    protected $hidden = [
        'admin_password',
    ];

    protected $casts = [
        'admin_active' => 'boolean',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->admin_password;
    }

    public function validateLegacyPassword(string $password): bool
    {
        return LegacyPasswordHasher::check($password, $this->admin_password);
    }

    public function scopeActive($query)
    {
        return $query->where('admin_active', 1);
    }
}
