<?php

namespace App\Adapters\Outbound\Storage;

use App\Application\Ports\CoverStorage;
use App\Domain\Uuid;

/** D-08: covers on disk; the key is opaque (uuid.ext), never a path. */
final class DiskCoverStorage implements CoverStorage
{
    public function __construct(private readonly string $dir) {}

    public function put(string $bytes, string $extension): string
    {
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
        $key = Uuid::v4().'.'.$extension;
        file_put_contents($this->path($key), $bytes);

        return $key;
    }

    public function get(string $key): ?string
    {
        if (! self::validKey($key) || ! is_file($this->path($key))) {
            return null;
        }

        return file_get_contents($this->path($key));
    }

    public function delete(string $key): void
    {
        if (self::validKey($key) && is_file($this->path($key))) {
            unlink($this->path($key));
        }
    }

    private function path(string $key): string
    {
        return rtrim($this->dir, '/').'/'.$key;
    }

    private static function validKey(string $key): bool
    {
        return (bool) preg_match('/^[0-9a-f-]{36}\.(png|jpg)$/', $key);
    }
}
