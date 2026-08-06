<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$defaultLang = (int) DB::table('tbl_configurations')->where('conf_name', 'CONF_DEFAULT_LANG')->value('conf_val');
$defaultLang = $defaultLang > 0 ? $defaultLang : 1;

$total = DB::table('tbl_pages_language_data')->where('plang_lang_id', $defaultLang)->count();
echo "default_lang={$defaultLang} total={$total}\n";

$rows = DB::table('tbl_pages_language_data')
    ->where('plang_lang_id', $defaultLang)
    ->orderBy('plang_key')
    ->limit(5)
    ->get(['plang_id', 'plang_key', 'plang_title']);

foreach ($rows as $row) {
    echo "{$row->plang_id} | {$row->plang_key} | {$row->plang_title}\n";
}
