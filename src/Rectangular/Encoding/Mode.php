<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rectangular\Encoding;

use Dllobell\Qr\Rectangular\RectangularQrCodeVersion;

enum Mode: int
{
    case NUMERIC = 0;
    case ALPHANUMERIC = 1;
    case BYTE = 2;
    case KANJI = 3;

    public function characterCountBitsLength(RectangularQrCodeVersion $version): int
    {
        $map = [
            [4, 3, 3, 2],
            [5, 5, 4, 3],
            [6, 5, 5, 4],
            [7, 6, 5, 5],
            [7, 6, 6, 5],
            [5, 5, 4, 3],
            [6, 5, 5, 4],
            [7, 6, 5, 5],
            [7, 6, 6, 5],
            [8, 7, 6, 6],
            [4, 4, 3, 2],
            [6, 5, 5, 4],
            [7, 6, 5, 5],
            [7, 6, 6, 5],
            [8, 7, 6, 6],
            [8, 7, 7, 6],
            [5, 5, 4, 3],
            [6, 6, 5, 5],
            [7, 6, 6, 5],
            [7, 7, 6, 6],
            [8, 7, 7, 6],
            [8, 8, 7, 7],
            [7, 6, 6, 5],
            [7, 7, 6, 5],
            [8, 7, 7, 6],
            [8, 7, 7, 6],
            [9, 8, 7, 7],
            [7, 6, 6, 5],
            [8, 7, 6, 6],
            [8, 7, 7, 6],
            [8, 8, 7, 6],
            [9, 8, 8, 7],
        ];

        return $map[$version->value][$this->value];
    }
}
