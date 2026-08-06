<?php

namespace App\Services\Admin;

class AdminSitemapGenerateService
{
    public function __construct(private AdminSitemapHtmlService $htmlService)
    {
    }

    private int $listIndex = 0;

    private int $urlCount = 0;

    /** @var resource|null */
    private $buffer = null;

    /** @throws \RuntimeException */
    public function generate(int $langId = 1): void
    {
        $frontUrl = $this->frontUrl();
        $uploadsPath = $this->uploadsPath();
        $urls = $this->htmlService->flatUrls($langId);

        $this->listIndex = 0;
        $this->urlCount = 0;
        $this->startSitemapXml();

        foreach ($urls as $url) {
            $this->writeSitemapUrl($url, $uploadsPath);
        }

        $this->endSitemapXml($uploadsPath);
        $this->writeSitemapIndex($uploadsPath, $frontUrl);
    }

    private function frontUrl(): string
    {
        return rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');
    }

    private function uploadsPath(): string
    {
        $path = realpath(base_path('../user-uploads')) ?: base_path('../user-uploads');
        if (! is_dir($path) && ! mkdir($path, 0777, true) && ! is_dir($path)) {
            throw new \RuntimeException('Unable to create uploads directory for sitemap.');
        }

        $sitemapDir = $path.'/sitemap';
        if (! is_dir($sitemapDir) && ! mkdir($sitemapDir, 0777, true) && ! is_dir($sitemapDir)) {
            throw new \RuntimeException('Unable to create sitemap directory.');
        }

        return $path;
    }

    private function startSitemapXml(): void
    {
        $this->buffer = fopen('php://temp', 'w+');
        if ($this->buffer === false) {
            throw new \RuntimeException('Unable to start sitemap buffer.');
        }

        fwrite($this->buffer, '<?xml version="1.0" encoding="utf-8"?>'."\n");
        fwrite($this->buffer, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n");
    }

    private function writeSitemapUrl(string $url, string $uploadsPath): void
    {
        $this->urlCount++;
        if ($this->urlCount > 2000) {
            $this->urlCount = 1;
            $this->endSitemapXml($uploadsPath);
            $this->startSitemapXml();
        }

        $entry = "<url>\n"
            .'    <loc>'.htmlspecialchars($url, ENT_XML1)."</loc>\n"
            .'    <lastmod>'.date('Y-m-d')."</lastmod>\n"
            ."    <changefreq>weekly</changefreq>\n"
            ."    <priority>0.8</priority>\n"
            ."</url>\n";
        fwrite($this->buffer, $entry);
    }

    private function endSitemapXml(string $uploadsPath): void
    {
        if (! is_resource($this->buffer)) {
            return;
        }

        fwrite($this->buffer, '</urlset>'."\n");
        rewind($this->buffer);
        $contents = stream_get_contents($this->buffer);
        fclose($this->buffer);
        $this->buffer = null;

        if ($contents === false) {
            throw new \RuntimeException('Unable to read sitemap buffer.');
        }

        $this->listIndex++;
        $target = $uploadsPath.'/sitemap/list_'.$this->listIndex.'.xml';
        if (file_put_contents($target, $contents) === false) {
            throw new \RuntimeException('Unable to write sitemap file.');
        }
    }

    private function writeSitemapIndex(string $uploadsPath, string $frontUrl): void
    {
        if ($this->listIndex === 0) {
            $this->listIndex = 1;
            $this->startSitemapXml();
            $this->writeSitemapUrl($frontUrl.'/', $uploadsPath);
            $this->endSitemapXml($uploadsPath);
        }

        $lines = [
            "<?xml version='1.0' encoding='utf-8' standalone='no' ?>",
            "<sitemapindex xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd' xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>",
        ];

        for ($i = 1; $i <= $this->listIndex; $i++) {
            $lines[] = '<sitemap><loc>'.$frontUrl.'/sitemap/list_'.$i.'.xml</loc></sitemap>';
        }

        $lines[] = '</sitemapindex>';

        if (file_put_contents($uploadsPath.'/sitemap.xml', implode("\n", $lines)."\n") === false) {
            throw new \RuntimeException('Unable to write sitemap index.');
        }
    }
}
