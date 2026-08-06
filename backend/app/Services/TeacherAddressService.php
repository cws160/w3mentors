<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

class TeacherAddressService
{
    public const TYPE_HOME = 1;

    public const TYPE_OFFICE = 2;

    public const TYPE_OTHER = 3;

    public function moduleEnabled(): bool
    {
        return (int) Configuration::getValue('CONF_ENABLE_OFFLINE_SESSIONS', 0) === 1;
    }

    public function teacherOfflineEnabled(int $userId): bool
    {
        if (! $this->moduleEnabled()) {
            return false;
        }

        return (int) DB::table('tbl_users')
            ->where('user_id', $userId)
            ->value('user_offline_sessions') === 1;
    }

    public function defaultAddress(int $userId, int $langId): ?array
    {
        $row = DB::table('tbl_user_addresses as usradd')
            ->join('tbl_states as st', 'st.state_id', '=', 'usradd.usradd_state_id')
            ->leftJoin('tbl_states_lang as stlang', function ($join) use ($langId) {
                $join->on('stlang.stlang_state_id', '=', 'st.state_id')
                    ->where('stlang.stlang_lang_id', '=', $langId);
            })
            ->join('tbl_countries as c', 'c.country_id', '=', 'usradd.usradd_country_id')
            ->leftJoin('tbl_countries_lang as clang', function ($join) use ($langId) {
                $join->on('clang.countrylang_country_id', '=', 'c.country_id')
                    ->where('clang.countrylang_lang_id', '=', $langId);
            })
            ->where('usradd.usradd_user_id', $userId)
            ->where('usradd.usradd_default', 1)
            ->whereNull('usradd.usradd_deleted')
            ->first([
                'usradd.*',
                DB::raw('IFNULL(stlang.state_name, st.state_identifier) as state_name'),
                DB::raw('IFNULL(clang.country_name, c.country_identifier) as country_name'),
            ]);

        return $row ? $this->mapAddress($row, $langId) : null;
    }

    public function googleMapsKey(): string
    {
        return (string) Configuration::getValue('CONF_GOOGLE_API_KEY', '');
    }

    public function index(int $userId, int $langId): array
    {
        $user = DB::table('tbl_users')->where('user_id', $userId)->first(['user_country_id']);
        $countryId = (int) ($user->user_country_id ?? 0);

        return [
            'module_enabled' => $this->moduleEnabled(),
            'google_maps_key' => $this->googleMapsKey(),
            'country_id' => $countryId,
            'address_types' => $this->addressTypes($langId),
            'states' => $countryId > 0 ? $this->stateOptions($langId, $countryId) : [],
            'addresses' => $this->listAddresses($userId, $langId),
        ];
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    public function addressTypes(int $langId): array
    {
        $labels = [
            self::TYPE_HOME => $this->label('LBL_HOME', 'Home', $langId),
            self::TYPE_OFFICE => $this->label('LBL_OFFICE', 'Office', $langId),
            self::TYPE_OTHER => $this->label('LBL_OTHER', 'Other', $langId),
        ];

        return collect($labels)->map(fn ($label, $id) => ['id' => (int) $id, 'label' => $label])->values()->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function stateOptions(int $langId, int $countryId): array
    {
        $rows = DB::table('tbl_states as st')
            ->leftJoin('tbl_states_lang as stlang', function ($join) use ($langId) {
                $join->on('stlang.stlang_state_id', '=', 'st.state_id')
                    ->where('stlang.stlang_lang_id', '=', $langId);
            })
            ->where('st.state_country_id', $countryId)
            ->where('st.state_active', 1)
            ->orderBy('state_name')
            ->get([
                'st.state_id',
                DB::raw('IFNULL(stlang.state_name, st.state_identifier) AS state_name'),
            ]);

        return $rows->map(fn ($row) => [
            'id' => (int) $row->state_id,
            'name' => (string) $row->state_name,
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAddresses(int $userId, int $langId): array
    {
        $rows = DB::table('tbl_user_addresses as usradd')
            ->join('tbl_states as st', 'st.state_id', '=', 'usradd.usradd_state_id')
            ->leftJoin('tbl_states_lang as stlang', function ($join) use ($langId) {
                $join->on('stlang.stlang_state_id', '=', 'st.state_id')
                    ->where('stlang.stlang_lang_id', '=', $langId);
            })
            ->join('tbl_countries as c', 'c.country_id', '=', 'usradd.usradd_country_id')
            ->leftJoin('tbl_countries_lang as clang', function ($join) use ($langId) {
                $join->on('clang.countrylang_country_id', '=', 'c.country_id')
                    ->where('clang.countrylang_lang_id', '=', $langId);
            })
            ->where('usradd.usradd_user_id', $userId)
            ->whereNull('usradd.usradd_deleted')
            ->orderByDesc('usradd.usradd_default')
            ->orderBy('usradd.usradd_id')
            ->get([
                'usradd.*',
                DB::raw('IFNULL(stlang.state_name, st.state_identifier) AS state_name'),
                DB::raw('IFNULL(clang.country_name, c.country_identifier) AS country_name'),
            ]);

        return $rows->map(fn ($row) => $this->mapAddress($row, $langId))->all();
    }

    public function find(int $userId, int $addressId, int $langId): ?array
    {
        $row = DB::table('tbl_user_addresses as usradd')
            ->join('tbl_states as st', 'st.state_id', '=', 'usradd.usradd_state_id')
            ->leftJoin('tbl_states_lang as stlang', function ($join) use ($langId) {
                $join->on('stlang.stlang_state_id', '=', 'st.state_id')
                    ->where('stlang.stlang_lang_id', '=', $langId);
            })
            ->join('tbl_countries as c', 'c.country_id', '=', 'usradd.usradd_country_id')
            ->leftJoin('tbl_countries_lang as clang', function ($join) use ($langId) {
                $join->on('clang.countrylang_country_id', '=', 'c.country_id')
                    ->where('clang.countrylang_lang_id', '=', $langId);
            })
            ->where('usradd.usradd_user_id', $userId)
            ->where('usradd.usradd_id', $addressId)
            ->whereNull('usradd.usradd_deleted')
            ->first([
                'usradd.*',
                DB::raw('IFNULL(stlang.state_name, st.state_identifier) AS state_name'),
                DB::raw('IFNULL(clang.country_name, c.country_identifier) AS country_name'),
            ]);

        return $row ? $this->mapAddress($row, $langId) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(int $userId, int $langId, array $data, ?int $addressId = null): array
    {
        if (! $this->moduleEnabled()) {
            throw new \InvalidArgumentException('Offline sessions module is not enabled.');
        }

        $user = DB::table('tbl_users')->where('user_id', $userId)->first(['user_country_id']);
        $countryId = (int) ($user->user_country_id ?? 0);
        if ($countryId < 1) {
            throw new \InvalidArgumentException('Please select a country in your profile first.');
        }

        $state = DB::table('tbl_states')
            ->where('state_id', (int) $data['state_id'])
            ->where('state_country_id', $countryId)
            ->where('state_active', 1)
            ->first();
        if (! $state) {
            throw new \InvalidArgumentException('Selected state is invalid or inactive.');
        }

        if (empty($data['latitude']) || empty($data['longitude'])) {
            throw new \InvalidArgumentException('Please select a location on the map.');
        }

        $isDefault = ! empty($data['is_default']) ? 1 : 0;
        $now = now()->format('Y-m-d H:i:s');

        if ($isDefault) {
            DB::table('tbl_user_addresses')
                ->where('usradd_user_id', $userId)
                ->whereNull('usradd_deleted')
                ->update(['usradd_default' => 0]);
        }

        $payload = [
            'usradd_phone' => (string) $data['phone'],
            'usradd_address' => (string) $data['address'],
            'usradd_city' => (string) $data['city'],
            'usradd_state_id' => (int) $data['state_id'],
            'usradd_country_id' => $countryId,
            'usradd_zipcode' => (string) $data['zipcode'],
            'usradd_place_id' => (string) ($data['place_id'] ?? ''),
            'usradd_place_name' => (string) ($data['place_name'] ?? ''),
            'usradd_latitude' => (float) $data['latitude'],
            'usradd_longitude' => (float) $data['longitude'],
            'usradd_type' => (int) $data['type'],
            'usradd_default' => $isDefault,
            'usradd_updated' => $now,
        ];

        if ($addressId) {
            $existing = DB::table('tbl_user_addresses')
                ->where('usradd_id', $addressId)
                ->where('usradd_user_id', $userId)
                ->whereNull('usradd_deleted')
                ->first();
            if (! $existing) {
                throw new \InvalidArgumentException('Address not found.');
            }
            if (! $isDefault && ! $this->hasOtherDefault($userId, $addressId)) {
                $payload['usradd_default'] = 1;
            }
            DB::table('tbl_user_addresses')->where('usradd_id', $addressId)->update($payload);

            return $this->find($userId, $addressId, $langId) ?? [];
        }

        if (! $isDefault && ! $this->hasAnyDefault($userId)) {
            $payload['usradd_default'] = 1;
        }
        $payload['usradd_user_id'] = $userId;
        $payload['usradd_created'] = $now;
        $newId = (int) DB::table('tbl_user_addresses')->insertGetId($payload);

        return $this->find($userId, $newId, $langId) ?? [];
    }

    public function remove(int $userId, int $addressId): void
    {
        $address = DB::table('tbl_user_addresses')
            ->where('usradd_id', $addressId)
            ->where('usradd_user_id', $userId)
            ->whereNull('usradd_deleted')
            ->first();

        if (! $address) {
            throw new \InvalidArgumentException('Address not found.');
        }

        if ((int) $address->usradd_default === 1) {
            throw new \InvalidArgumentException('Cannot delete the default address.');
        }

        $inUse = DB::table('tbl_group_classes')
            ->where('grpcls_address_id', $addressId)
            ->where('grpcls_start_datetime', '>', now())
            ->where('grpcls_status', '!=', 3)
            ->exists();

        if ($inUse) {
            throw new \InvalidArgumentException(
                'Cannot delete this address. It is associated with upcoming classes.'
            );
        }

        DB::table('tbl_user_addresses')
            ->where('usradd_id', $addressId)
            ->update(['usradd_deleted' => now()->format('Y-m-d H:i:s')]);
    }

    private function hasAnyDefault(int $userId): bool
    {
        return DB::table('tbl_user_addresses')
            ->where('usradd_user_id', $userId)
            ->where('usradd_default', 1)
            ->whereNull('usradd_deleted')
            ->exists();
    }

    private function hasOtherDefault(int $userId, int $excludeId): bool
    {
        return DB::table('tbl_user_addresses')
            ->where('usradd_user_id', $userId)
            ->where('usradd_default', 1)
            ->where('usradd_id', '!=', $excludeId)
            ->whereNull('usradd_deleted')
            ->exists();
    }

    private function mapAddress(object $row, int $langId): array
    {
        $type = (int) $row->usradd_type;

        return [
            'id' => (int) $row->usradd_id,
            'phone' => (string) $row->usradd_phone,
            'address' => (string) $row->usradd_address,
            'city' => (string) $row->usradd_city,
            'state_id' => (int) $row->usradd_state_id,
            'state_name' => (string) $row->state_name,
            'country_id' => (int) $row->usradd_country_id,
            'country_name' => (string) $row->country_name,
            'zipcode' => (string) $row->usradd_zipcode,
            'place_id' => (string) $row->usradd_place_id,
            'place_name' => (string) $row->usradd_place_name,
            'latitude' => $row->usradd_latitude !== null ? (float) $row->usradd_latitude : null,
            'longitude' => $row->usradd_longitude !== null ? (float) $row->usradd_longitude : null,
            'type' => $type,
            'type_label' => $this->typeLabel($type, $langId),
            'is_default' => (int) $row->usradd_default === 1,
            'formatted' => $this->format($row),
        ];
    }

    private function format(object $row): string
    {
        return implode(', ', array_filter([
            (string) $row->usradd_address,
            (string) $row->usradd_city,
            (string) $row->state_name,
            (string) $row->usradd_zipcode,
            (string) $row->country_name,
        ]));
    }

    private function typeLabel(int $type, int $langId): string
    {
        return match ($type) {
            self::TYPE_OFFICE => $this->label('LBL_OFFICE', 'Office', $langId),
            self::TYPE_OTHER => $this->label('LBL_OTHER', 'Other', $langId),
            default => $this->label('LBL_HOME', 'Home', $langId),
        };
    }

    private function label(string $key, string $fallback, int $langId): string
    {
        $row = DB::table('tbl_language_labels')
            ->where('label_key', $key)
            ->where('label_lang_id', $langId)
            ->value('label_caption');

        return $row ?: $fallback;
    }
}
