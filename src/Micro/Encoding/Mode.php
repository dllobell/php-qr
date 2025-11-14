<?php

declare(strict_types=1);

namespace Dllobell\Qr\Micro\Encoding;

use Dllobell\Qr\Micro\MicroQrCodeVersion;

enum Mode
{
    case NUMERIC;
    case ALPHANUMERIC;
    case BYTE;
    case KANJI;

    public function bits(): int
    {
        return match ($this) {
            self::NUMERIC => 0x0,
            self::ALPHANUMERIC => 0x1,
            self::BYTE => 0x2,
            self::KANJI => 0x3,
        };
    }

    public function supports(MicroQrCodeVersion $version): bool
    {
        return match ($this) {
            self::NUMERIC => true,
            self::ALPHANUMERIC => $version->value >= 2,
            self::BYTE => $version->value >= 3,
            self::KANJI => $version->value >= 3,
        };
    }

    public function characterCountBitsLength(MicroQrCodeVersion $version): int
    {
        return match ($this) {
            self::NUMERIC => $version->value + 2,
            self::ALPHANUMERIC => $version->value + 1,
            self::BYTE => $version->value + 1,
            self::KANJI => $version->value,
        };
    }
}
