<?php

namespace App\Http\Controllers;

use App\Services\Admin\AdminSitemapViewService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SitemapFileController extends Controller
{
    public function __construct(private AdminSitemapViewService $sitemap)
    {
    }

    public function index(): BinaryFileResponse
    {
        return $this->serve('sitemap.xml');
    }

    public function list(int $number): BinaryFileResponse
    {
        return $this->serve('sitemap/list_'.$number.'.xml');
    }

    private function serve(string $relative): BinaryFileResponse
    {
        $path = $this->sitemap->filePath($relative);
        if ($path === null) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
