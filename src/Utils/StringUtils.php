<?php
declare(strict_types=1);

namespace App\Utils;

use App\Service\Collection;

final class StringUtils
{
    public static function parseDependencies(string $dependencies): array
    {
        return (new Collection(explode(
            ' ',
            str_replace(
                '\'',
                '',
                $dependencies
            )
        )))->map(static function (string $dependency)
        {
            if (strpos($dependency, '<') !== false) {
                $dependency = substr($dependency, 0, strpos($dependency, '<'));
            }

            if (strpos($dependency, '>') !== false) {
                $dependency = substr($dependency, 0, strpos($dependency, '>'));
            }

            if (strpos($dependency, '=') !== false) {
                $dependency = substr($dependency, 0, strpos($dependency, '='));
            }

            return $dependency;
        })->map(static function (string $dependency)
        {
            return preg_replace(
                '/[^a-zA-Z\ \_\'0-9\-]/',
                '',
                $dependency
            );
        })->filter(static function (string $item)
        {
            return $item !== '';
        })
            ->unique()
            ->toArray();
    }
}
