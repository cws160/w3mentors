<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceUsersSeeder::class,
            TeacherDemoAvailabilitySeeder::class,
            TeacherDemoGroupClassesSeeder::class,
            GroupClassMetaTagsDemoSeeder::class,
            TeacherDemoQuizzesSeeder::class,
            TeacherDemoForumTagRequestsSeeder::class,
            WithdrawRequestsDemoSeeder::class,
            WalletRechargeOrdersSeeder::class,
            GiftCardOrdersDemoSeeder::class,
            ReportedIssuesDemoSeeder::class,
            TeacherReviewsDemoSeeder::class,
            LessonStatsDemoSeeder::class,
            SocialLoginConfigSeeder::class,
        ]);
    }
}
