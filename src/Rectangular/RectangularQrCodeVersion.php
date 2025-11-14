<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rectangular;

use InvalidArgumentException;

/**
 * @property int<0, 31> $value
 */
final readonly class RectangularQrCodeVersion
{
    public const int MIN = 0;

    public const int MAX = 31;

    private function __construct(public int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new InvalidArgumentException('Rectangular QR version indicator must be between '.self::MIN.' and '.self::MAX);
        }
    }

    public static function make(int $value): self
    {
        return new self($value);
    }
}
