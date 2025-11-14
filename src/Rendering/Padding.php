<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use InvalidArgumentException;

/**
 * @property int<1, max> $top
 * @property int<1, max> $right
 * @property int<1, max> $bottom
 * @property int<1, max> $left
 */
final readonly class Padding
{
    private function __construct(public int $top, public int $right, public int $bottom, public int $left)
    {
        if ($top < 0 || $right < 0 || $bottom < 0 || $left < 0) {
            throw new InvalidArgumentException('Padding must be non-negative');
        }
    }

    public static function make(int $top, int $right, int $bottom, int $left): self
    {
        return new self(
            top: $top,
            right: $right,
            bottom: $bottom,
            left: $left,
        );
    }

    public static function all(int $value): self
    {
        return self::make(
            top: $value,
            right: $value,
            bottom: $value,
            left: $value,
        );
    }

    public static function symmetric(int $vertical, int $horizontal): self
    {
        return self::make(
            top: $vertical,
            right: $horizontal,
            bottom: $vertical,
            left: $horizontal,
        );
    }
}
