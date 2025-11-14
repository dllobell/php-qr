<?php

declare(strict_types=1);

namespace Dllobell\Qr\Standard\Encoding;

enum Mode
{
    case NUMERIC;
    case ALPHANUMERIC;
    case BYTE;
    case KANJI;
    case ECI;

    public function bits(): int
    {
        return match ($this) {
            self::NUMERIC => 0x1,
            self::ALPHANUMERIC => 0x2,
            self::BYTE => 0x4,
            self::KANJI => 0x8,
            self::ECI => 0x7,
        };
    }

    public function totalCharacterCountBits(int $version): int
    {
        $index = $version <= 9 ? 0 : ($version <= 26 ? 1 : 2);

        return $this->characterCountBits()[$index];
    }

    /**
     * @return array<int>
     */
    private function characterCountBits(): array
    {
        return match ($this) {
            self::NUMERIC => [10, 12, 14],
            self::ALPHANUMERIC => [9, 11, 13],
            self::BYTE => [8, 16, 16],
            self::KANJI => [8, 10, 12],
            self::ECI => [0, 0, 0],
        };
    }
}
