<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeacherDemoGroupClassesSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('demo.teacher.email');
        $teacher = DB::table('tbl_users')->where('user_email', $email)->first();
        if (! $teacher) {
            return;
        }

        $teacherId = (int) $teacher->user_id;
        $tlangId = (int) (DB::table('tbl_teach_languages')->value('tlang_id') ?? 24);

        $classes = [
            [
                'title' => 'Learn Numbers, Time & Date',
                'description' => 'Practice numbers, telling time, and calendar dates in a fun group setting.',
                'start' => '+2 days 22:10:00',
                'duration' => 60,
                'entry_fee' => 28.50,
                'total_seats' => 27,
                'booked_seats' => 0,
                'offline' => 0,
                'status' => 1,
                'parent' => 203,
            ],
            [
                'title' => 'English Vocabulary: Upgrade your speaking',
                'description' => 'Build vocabulary and speaking confidence with guided group activities.',
                'start' => '+4 days 14:20:00',
                'duration' => 45,
                'entry_fee' => 53.00,
                'total_seats' => 14,
                'booked_seats' => 0,
                'offline' => 0,
                'status' => 1,
                'parent' => 0,
            ],
            [
                'title' => 'General Biology: Foundations of Biology',
                'description' => 'Introduction to core biology concepts for learners at any level.',
                'start' => '+6 days 06:15:00',
                'duration' => 60,
                'entry_fee' => 31.00,
                'total_seats' => 18,
                'booked_seats' => 2,
                'offline' => 0,
                'status' => 1,
                'parent' => 0,
            ],
            [
                'title' => 'Organic Chemistry - Covalent Bonding',
                'description' => 'In-person workshop on molecular shapes and covalent bonding.',
                'start' => '+8 days 00:35:00',
                'duration' => 30,
                'entry_fee' => 54.00,
                'total_seats' => 5,
                'booked_seats' => 0,
                'offline' => 1,
                'status' => 1,
                'parent' => 0,
            ],
            [
                'title' => 'Vectors - Basic Introduction - Physics',
                'description' => 'Scalars, vectors, and component forms — completed session sample.',
                'start' => '-14 days 14:40:00',
                'duration' => 45,
                'entry_fee' => 73.00,
                'total_seats' => 5,
                'booked_seats' => 0,
                'offline' => 0,
                'status' => 2,
                'parent' => 0,
            ],
        ];

        foreach ($classes as $class) {
            $slug = Str::slug($class['title']).'-demo-'.$teacherId;
            $exists = DB::table('tbl_group_classes')
                ->where('grpcls_teacher_id', $teacherId)
                ->where('grpcls_slug', $slug)
                ->exists();
            if ($exists) {
                continue;
            }

            $start = strtotime($class['start']);
            $end = strtotime('+'.$class['duration'].' minutes', $start);

            DB::table('tbl_group_classes')->insert([
                'grpcls_type' => 1,
                'grpcls_parent' => $class['parent'],
                'grpcls_slug' => $slug,
                'grpcls_title' => $class['title'],
                'grpcls_description' => $class['description'],
                'grpcls_teacher_id' => $teacherId,
                'grpcls_tlang_id' => $tlangId,
                'grpcls_duration' => $class['duration'],
                'grpcls_start_datetime' => date('Y-m-d H:i:s', $start),
                'grpcls_end_datetime' => date('Y-m-d H:i:s', $end),
                'grpcls_total_seats' => $class['total_seats'],
                'grpcls_booked_seats' => $class['booked_seats'],
                'grpcls_entry_fee' => $class['entry_fee'],
                'grpcls_status' => $class['status'],
                'grpcls_metool_id' => 0,
                'grpcls_added_on' => now(),
                'grpcls_address_id' => null,
                'grpcls_offline' => $class['offline'],
            ]);
        }
    }
}
