<?php

declare(strict_types=1);

namespace Dllobell\Qr\Core;

use RuntimeException;

final readonly class Utils
{
    /**
     * Returns a new array of bytes representing the given string in the specified encoding
     *
     * @return list<int>
     */
    public static function stringToByteArray(string $value, string $encoding): array
    {
        /** @var false|string */
        $encoded = mb_convert_encoding($value, to_encoding: $encoding, from_encoding: 'UTF-8');
        if ($encoded === false) {
            throw new RuntimeException('Failed to convert string encoding');
        }

        /** @var array<int>|false */
        $bytes = unpack('C*', $encoded);
        if ($bytes === false) {
            throw new RuntimeException('Failed to convert string to byte array');
        }

        return array_values($bytes);
    }
}
