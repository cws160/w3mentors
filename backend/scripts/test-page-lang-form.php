<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Admin\AdminPageLangDataManageService;
use Illuminate\Support\Facades\DB;

$defaultLang = (int) DB::table('tbl_configurations')->where('conf_name', 'CONF_DEFAULT_LANG')->value('conf_val');
$defaultLang = $defaultLang > 0 ? $defaultLang : 1;

$row = DB::table('tbl_pages_language_data')
    ->where('plang_key', 'abusive-words')
    ->where('plang_lang_id', $defaultLang)
    ->first();

if (! $row) {
    echo "abusive-words row not found for lang {$defaultLang}\n";
    exit(1);
}

echo "Listing row: plang_id={$row->plang_id} key={$row->plang_key} title={$row->plang_title}\n";

$service = app(AdminPageLangDataManageService::class);
$form = $service->langForm((int) $row->plang_id, $defaultLang);

if (! $form) {
    echo "langForm returned null\n";
    exit(1);
}

echo "langForm title: {$form['plang_title']}\n";
echo "langForm summary: " . substr($form['plang_summary'], 0, 60) . "...\n";
echo "langForm helping length: " . strlen($form['plang_helping_text']) . "\n";
echo "default helping length: " . strlen($form['default_helping_text']) . "\n";
