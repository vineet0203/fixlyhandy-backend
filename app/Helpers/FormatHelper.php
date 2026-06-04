<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class FormatHelper
{
    public static function camelToSnake(array $data): array
    {
        $converted = [];

        foreach ($data as $key => $value) {
            $snakeKey = Str::snake($key);
            $converted[$snakeKey] = is_array($value)
                ? self::camelToSnake($value)
                : $value;
        }

        return $converted;
    }
}
