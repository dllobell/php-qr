<?php

declare(strict_types=1);

namespace Dllobell\Qr\Standard;

enum StandardQrCodeEcl: int
{
    case Low = 0;
    case Medium = 1;
    case Quartile = 2;
    case High = 3;

    public function formatBits(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 0,
            self::Quartile => 3,
            self::High => 2,
        };
    }
}
