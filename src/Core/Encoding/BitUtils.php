<?php

declare(strict_types=1);

namespace Dllobell\Qr\Core\Encoding;

final readonly class BitUtils
{
    public static function unsignedRightShift(int $a, int $b): int
    {
        if ($b >= 32 || $b < -32) {
            $m = (int) ($b / 32);
            $b -= ($m * 32);
        }

        if ($b < 0) {
            $b = 32 + $b;
        }

        if ($b === 0) {
            return (($a >> 1) & 0x7FFFFFFF) * 2 + (($a >> $b) & 1);
        }

        if ($a < 0) {
            $a = ($a >> 1);
            $a &= 0x7FFFFFFF;
            $a |= 0x40000000;
            $a = ($a >> ($b - 1));
        } else {
            $a = ($a >> $b);
        }

        return $a;
    }
}
