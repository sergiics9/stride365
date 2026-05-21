<?php

namespace App\Support;

class BrandLogo
{
    public const NAME = 'Stride365';

    public static function name(): string
    {
        return self::NAME;
    }

    public static function path(): string
    {
        return public_path('logo.png');
    }

    public static function exists(): bool
    {
        return is_file(static::path());
    }

    public static function dataUri(): ?string
    {
        if (! static::exists()) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents(static::path()));
    }
}
