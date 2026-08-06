<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeacherDemoQuizzesSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('demo.teacher.email');
        $teacher = DB::table('tbl_users')->where('user_email', $email)->first();
        if (! $teacher) {
            return;
        }

        $teacherId = (int) $teacher->user_id;
        $detail = 'This quiz is designed to assess your understanding of the material.';

        $quizzes = [
            ['title' => 'Conversation Starter: Vocabulary & Speaking Quiz', 'type' => 2],
            ['title' => 'Challenge: Character Formation Edition', 'type' => 1],
            ['title' => 'Chinese Characters 101: Formation Mastery Test', 'type' => 2],
            ['title' => 'How Well Do You Know Chinese Character Structure?', 'type' => 1],
            ['title' => 'Grammar Rules Unlocked', 'type' => 1],
            ['title' => 'Master the Rules: Quiz Edition', 'type' => 1],
        ];

        foreach ($quizzes as $quiz) {
            $slug = Str::slug($quiz['title']).'-'.$teacherId;
            $exists = DB::table('tbl_quizzes')
                ->where('quiz_user_id', $teacherId)
                ->where('quiz_title', $quiz['title'])
                ->exists();
            if ($exists) {
                continue;
            }

            DB::table('tbl_quizzes')->insert([
                'quiz_type' => $quiz['type'],
                'quiz_title' => $quiz['title'],
                'quiz_detail' => $detail,
                'quiz_user_id' => $teacherId,
                'quiz_duration' => 2400,
                'quiz_attempts' => 3,
                'quiz_marks' => 100,
                'quiz_passmark' => 60,
                'quiz_validity' => 48,
                'quiz_certificate' => 0,
                'quiz_questions' => 0,
                'quiz_failmsg' => 'Unfortunately, you did not pass the exam.',
                'quiz_passmsg' => 'Congratulations! You have successfully passed the exam.',
                'quiz_active' => 1,
                'quiz_status' => 2,
                'quiz_created' => now(),
                'quiz_updated' => now(),
                'quiz_deleted' => null,
            ]);
        }
    }
}
