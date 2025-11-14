<?php

declare(strict_types=1);

namespace Dllobell\Qr\Micro;

use InvalidArgumentException;

/**
 * @property int<1, 4> $value
 */
final readonly class MicroQrCodeVersion
{
    public const int MIN = 1;

    public const int MAX = 4;

    private function __construct(public int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new InvalidArgumentException('Micro QR version must be between '.self::MIN.' and '.self::MAX);
        }
    }

    public static function make(int $value): self
    {
        return new self($value);
    }
}
