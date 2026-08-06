<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TeacherDashboardResourcesService
{
    public function search(int $teacherId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = DB::table('tbl_resources')
            ->where('resrc_user_id', $teacherId)
            ->whereNull('resrc_deleted')
            ->select([
                'resrc_id as id',
                'resrc_name as name',
                'resrc_type as type',
                'resrc_size as size',
                'resrc_created as created_at',
            ])
            ->orderByDesc('resrc_id');

        if (! empty($filters['keyword'])) {
            $query->where('resrc_name', 'like', '%'.trim($filters['keyword']).'%');
        }

        return $query->paginate($perPage);
    }

    public function formatRow(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'type' => strtolower((string) $row->type),
            'size' => $row->size,
            'created_at' => $row->created_at,
            'icon' => $this->fileIcon((string) $row->type),
        ];
    }

    private function fileIcon(string $type): string
    {
        return match (strtolower($type)) {
            'png' => 'png-attachment',
            'jpg', 'jpeg' => 'jpg-attachment',
            'gif' => 'gif-attachment',
            'txt' => 'txt-attachment',
            'pdf' => 'pdf-attachment',
            'doc', 'docx' => 'doc-attachment',
            'zip' => 'zip-attachment',
            default => 'attach',
        };
    }
}
