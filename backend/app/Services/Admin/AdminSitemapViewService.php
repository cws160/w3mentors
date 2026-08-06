<?php

namespace App\Services\Admin;

class AdminSitemapViewService
{
    /** @return array{content: string, files: array<int, array{name: string, url: string}>} */
    public function xmlIndex(): array
    {
        $uploadsPath = $this->uploadsPath();
        $indexFile = $uploadsPath.'/sitemap.xml';
        $content = file_exists($indexFile) ? (string) file_get_contents($indexFile) : '';

        $files = [];
        foreach (glob($uploadsPath.'/sitemap/list_*.xml') ?: [] as $file) {
            $name = basename($file);
            $files[] = [
                'name' => $name,
                'url' => '/sitemap/'.$name,
            ];
        }

        usort($files, fn (array $a, array $b) => strnatcmp($a['name'], $b['name']));

        return [
            'content' => $content,
            'files' => $files,
        ];
    }

    public function filePath(string $relative): ?string
    {
        $relative = ltrim(str_replace(['..', '\\'], '', $relative), '/');
        if ($relative === 'sitemap.xml') {
            $path = $this->uploadsPath().'/sitemap.xml';
        } elseif (preg_match('/^sitemap\/list_\d+\.xml$/', $relative) === 1) {
            $path = $this->uploadsPath().'/'.$relative;
        } else {
            return null;
        }

        return file_exists($path) ? $path : null;
    }

    private function uploadsPath(): string
    {
        return realpath(base_path('../user-uploads')) ?: base_path('../user-uploads');
    }
}
