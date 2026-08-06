<?php

namespace App\Services\Admin;

class AdminRobotsTxtService
{
    public function content(): string
    {
        $file = $this->filePath();
        if (! file_exists($file)) {
            return '';
        }
        if (! is_readable($file)) {
            throw new \RuntimeException('Read permission denied');
        }

        return (string) file_get_contents($file);
    }

    public function save(string $content): void
    {
        $file = $this->filePath();
        $directory = dirname($file);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create robots.txt directory');
        }
        if (file_exists($file) && ! is_writable($file)) {
            throw new \RuntimeException('Write permission denied');
        }
        if (file_put_contents($file, $content) === false && $content !== '') {
            throw new \RuntimeException('Something went wrong');
        }
    }

    private function filePath(): string
    {
        $path = realpath(base_path('../public/robots.txt'));

        return $path !== false ? $path : base_path('../public/robots.txt');
    }
}
