<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<array{0: string, 1: string}> */
    private array $textColumns = [
        ['tbl_language_labels', 'label_caption'],
        ['tbl_meta_tags', 'meta_identifier'],
        ['tbl_admin', 'admin_name'],
        ['tbl_configurations', 'conf_val'],
        ['tbl_email_templates', 'etpl_subject'],
        ['tbl_email_templates', 'etpl_body'],
        ['tbl_email_templates', 'etpl_from_name'],
        ['tbl_email_archives', 'emailarchive_subject'],
        ['tbl_email_archives', 'emailarchive_body'],
        ['tbl_email_archives', 'emailarchive_from_name'],
        ['tbl_testimonials_lang', 'testimonial_text'],
        ['tbl_video_content_lang', 'videocontent_title'],
        ['tbl_pages_language_data', 'plang_title'],
        ['tbl_pages_language_data', 'plang_summary'],
        ['tbl_pages_language_data', 'plang_recommendations'],
        ['tbl_pages_language_data', 'plang_helping_text'],
        ['tbl_pages_language_data', 'plang_warning'],
        ['tbl_content_pages_lang', 'cpage_title'],
        ['tbl_content_pages_lang', 'cpage_content'],
        ['tbl_blog_post_lang', 'post_title'],
        ['tbl_blog_post_lang', 'post_description'],
        ['tbl_faq_lang', 'faq_title'],
        ['tbl_faq_lang', 'faq_content'],
    ];

    public function up(): void
    {
        foreach ($this->textColumns as [$table, $column]) {
            if (! $this->tableHasColumn($table, $column)) {
                continue;
            }

            DB::statement(
                "UPDATE `{$table}` SET `{$column}` = REPLACE(REPLACE(REPLACE(REPLACE(`{$column}`, 'w3mentors', 'w3mentors'), 'W3Mentors', 'w3mentors'), 'W3Mentors', 'w3mentors'), 'W3 Mentors', 'w3mentors') WHERE `{$column}` LIKE '%Yo%Coach%' OR `{$column}` LIKE '%W3Mentors%' OR `{$column}` LIKE '%W3 Mentors%'"
            );
        }

        if ($this->tableHasColumn('tbl_configurations', 'conf_val')) {
            DB::table('tbl_configurations')
                ->whereIn('conf_name', [
                    'CONF_WEBSITE_NAME_1',
                    'CONF_WEBSITE_NAME_2',
                    'CONF_WEBSITE_NAME_3',
                    'CONF_FROM_NAME_1',
                    'CONF_FROM_NAME_2',
                    'CONF_FROM_NAME_3',
                ])
                ->where('conf_val', 'Sitename')
                ->update(['conf_val' => 'w3mentors']);
        }

        Cache::flush();
    }

    public function down(): void
    {
        // Branding migration is not reversed.
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasTable($table)
            && DB::getSchemaBuilder()->hasColumn($table, $column);
    }
};
