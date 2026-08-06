<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$count = (int) DB::table('tbl_blog_post_comments')->where('bpcomment_deleted', 0)->count();
echo "existing_comments={$count}\n";

if ($count > 0) {
    echo "skip: comments already exist\n";
    exit(0);
}

$postIds = DB::table('tbl_blog_post')
    ->where('post_deleted', 0)
    ->orderByDesc('post_id')
    ->limit(5)
    ->pluck('post_id')
    ->all();

if ($postIds === []) {
    echo "error: no blog posts found\n";
    exit(1);
}

$now = now()->format('Y-m-d H:i:s');
$samples = [
    ['Jane Cooper', 'jane@example.com', 'Great article, very insightful!', 1],
    ['John Smith', 'john@example.com', 'Thanks for sharing this guide.', 0],
    ['Aisha Khan', 'aisha@example.com', 'Could you add more examples?', 0],
    ['Carlos Ruiz', 'carlos@example.com', 'Well written and easy to follow.', 1],
    ['Emily Chen', 'emily@example.com', 'Looking forward to the next post.', 0],
];

foreach ($samples as $index => [$name, $email, $content, $approved]) {
    $postId = $postIds[$index % count($postIds)];
    DB::table('tbl_blog_post_comments')->insert([
        'bpcomment_post_id' => $postId,
        'bpcomment_user_id' => 0,
        'bpcomment_author_name' => $name,
        'bpcomment_author_email' => $email,
        'bpcomment_content' => $content,
        'bpcomment_approved' => $approved,
        'bpcomment_deleted' => 0,
        'bpcomment_added_on' => $now,
        'bpcomment_user_ip' => '127.0.0.1',
        'bpcomment_user_agent' => 'DemoSeeder/1.0',
    ]);
}

echo 'inserted='.count($samples)."\n";
