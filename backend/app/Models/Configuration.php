<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuration extends Model
{
    protected $table = 'tbl_configurations';

    protected $primaryKey = 'conf_name';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("conf_{$key}", 3600, function () use ($key, $default) {
            $row = static::query()->where('conf_name', $key)->first();

            return $row?->conf_val ?? $default;
        });
    }

    public static function getMany(array $keys): array
    {
        $rows = static::query()->whereIn('conf_name', $keys)->pluck('conf_val', 'conf_name');

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $rows[$key] ?? null;
        }

        return $result;
    }
}
