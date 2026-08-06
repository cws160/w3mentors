<?php

namespace Database\Seeders;

use App\Services\LegacyPasswordHasher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeacherReviewsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $learners = [
            'Lonie Wintheiser' => 'lonie.wintheiser@dummyid.com',
            'Rocio Medhurst' => 'rocio.medhurst@dummyid.com',
            'Kendra O\'Kon' => 'kendra.okon@dummyid.com',
            'Dr. Abelardo O\'Keefe' => 'abelardo.okeefe@dummyid.com',
        ];

        $teachers = [
            'Zoila Lemke' => 'zoila.lemke@dummyid.com',
            'Ashlynn Pacocha' => 'ashlynn.pacocha@dummyid.com',
            'Matt Pollich' => 'matt.pollich@dummyid.com',
            'Dr. Abelardo O\'Keefe' => 'dr.abelardo.okeefe@dummyid.com',
        ];

        $learnerIds = [];
        foreach ($learners as $name => $email) {
            $learnerIds[$name] = $this->ensureUser($name, $email, false);
        }

        $teacherIds = [];
        foreach ($teachers as $name => $email) {
            $teacherIds[$name] = $this->ensureUser($name, $email, true);
        }

        $reviews = [
            [
                'id' => 101,
                'learner' => 'Lonie Wintheiser',
                'teacher' => 'Zoila Lemke',
                'title' => 'Always Ready to Help Me Out',
                'detail' => 'Always Ready to Help Me Out',
                'rating' => 5.0,
                'created_at' => '2025-11-04 01:14:00',
            ],
            [
                'id' => 100,
                'learner' => 'Rocio Medhurst',
                'teacher' => 'Ashlynn Pacocha',
                'title' => 'Ashlynn Pacocha Adapted Early On To My Needs As An Intermediate-Level Student',
                'detail' => 'Ashlynn Pacocha Adapted Early On To My Needs As An Intermediate-Level Student',
                'rating' => 5.0,
                'created_at' => '2025-11-03 22:38:00',
            ],
            [
                'id' => 99,
                'learner' => 'Lonie Wintheiser',
                'teacher' => 'Ashlynn Pacocha',
                'title' => 'Great Teaching Style And Patience',
                'detail' => 'Great Teaching Style And Patience',
                'rating' => 4.0,
                'created_at' => '2025-10-28 14:20:00',
            ],
            [
                'id' => 98,
                'learner' => 'Rocio Medhurst',
                'teacher' => 'Zoila Lemke',
                'title' => 'Very Knowledgeable And Supportive',
                'detail' => 'Very Knowledgeable And Supportive',
                'rating' => 4.0,
                'created_at' => '2025-10-15 09:45:00',
            ],
            [
                'id' => 97,
                'learner' => 'Kendra O\'Kon',
                'teacher' => 'Matt Pollich',
                'title' => 'Clear Explanations Every Session',
                'detail' => 'Clear Explanations Every Session',
                'rating' => 5.0,
                'created_at' => '2025-09-22 16:30:00',
            ],
            [
                'id' => 96,
                'learner' => 'Rocio Medhurst',
                'teacher' => 'Matt Pollich',
                'title' => 'Engaging Lessons That Keep Me Motivated',
                'detail' => 'Engaging Lessons That Keep Me Motivated',
                'rating' => 4.0,
                'created_at' => '2025-09-10 11:12:00',
            ],
            [
                'id' => 95,
                'learner' => 'Lonie Wintheiser',
                'teacher' => 'Zoila Lemke',
                'title' => 'Wonderful Experience From Start To Finish',
                'detail' => 'Wonderful Experience From Start To Finish',
                'rating' => 5.0,
                'created_at' => '2025-08-05 07:55:00',
            ],
            [
                'id' => 94,
                'learner' => 'Lonie Wintheiser',
                'teacher' => 'Matt Pollich',
                'title' => 'Variety and Diversification of Courses',
                'detail' => 'Variety and Diversification of Courses',
                'rating' => 5.0,
                'created_at' => '2026-03-28 18:42:00',
            ],
            [
                'id' => 93,
                'learner' => 'Dr. Abelardo O\'Keefe',
                'teacher' => 'Dr. Abelardo O\'Keefe',
                'title' => 'Professional And Well Prepared',
                'detail' => 'Professional And Well Prepared',
                'rating' => 4.0,
                'created_at' => '2025-07-18 13:05:00',
            ],
            [
                'id' => 92,
                'learner' => 'Kendra O\'Kon',
                'teacher' => 'Ashlynn Pacocha',
                'title' => 'Highly Recommend For Beginners',
                'detail' => 'Highly Recommend For Beginners',
                'rating' => 5.0,
                'created_at' => '2025-06-12 10:18:00',
            ],
        ];

        foreach ($reviews as $review) {
            $row = [
                'ratrev_type' => 1,
                'ratrev_type_id' => 1,
                'ratrev_lang_id' => 1,
                'ratrev_user_id' => $learnerIds[$review['learner']],
                'ratrev_teacher_id' => $teacherIds[$review['teacher']],
                'ratrev_overall' => $review['rating'],
                'ratrev_title' => $review['title'],
                'ratrev_detail' => $review['detail'],
                'ratrev_status' => 1,
                'ratrev_teacher_notify' => 1,
                'ratrev_created' => $review['created_at'],
            ];

            $id = (int) $review['id'];
            $exists = DB::table('tbl_rating_reviews')->where('ratrev_id', $id)->exists();
            if ($exists) {
                DB::table('tbl_rating_reviews')->where('ratrev_id', $id)->update($row);
            } else {
                DB::table('tbl_rating_reviews')->insert(array_merge(['ratrev_id' => $id], $row));
            }
        }

        $maxId = (int) DB::table('tbl_rating_reviews')->max('ratrev_id');
        if ($maxId > 0) {
            DB::statement('ALTER TABLE tbl_rating_reviews AUTO_INCREMENT = '.($maxId + 1));
        }

        $this->command?->info('Seeded '.count($reviews).' teacher review demo rows.');
    }

    private function ensureUser(string $fullName, string $email, bool $isTeacher): int
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [];
        $firstName = $parts[0] ?? $fullName;
        $lastName = $parts[1] ?? '';

        $existing = DB::table('tbl_users')->where('user_email', $email)->first();
        $row = [
            'user_first_name' => $firstName,
            'user_last_name' => $lastName,
            'user_email' => $email,
            'user_username' => Str::slug($email, ''),
            'user_password' => LegacyPasswordHasher::hash('lydia@123'),
            'user_timezone' => 'UTC',
            'user_lang_id' => 1,
            'user_currency_id' => 1,
            'user_country_id' => 91,
            'user_gender' => 2,
            'user_is_teacher' => $isTeacher ? 1 : 0,
            'user_is_affiliate' => 0,
            'user_featured' => $isTeacher ? 1 : 0,
            'user_offline_sessions' => 0,
            'user_active' => 1,
            'user_verified' => now()->subYear(),
            'user_lastseen' => now(),
        ];

        if ($existing) {
            DB::table('tbl_users')->where('user_id', $existing->user_id)->update($row);

            return (int) $existing->user_id;
        }

        return (int) DB::table('tbl_users')->insertGetId(array_merge($row, [
            'user_created' => now()->subYear(),
            'user_deleted' => null,
        ]));
    }
}
