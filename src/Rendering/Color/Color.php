<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Color;

use InvalidArgumentException;

/**
 * @property int<0, 255> $red
 * @property int<0, 255> $green
 * @property int<0, 255> $blue
 */
final readonly class Color
{
    private function __construct(public int $red, public int $green, public int $blue)
    {
        if ($red < 0 || $red > 255) {
            throw new InvalidArgumentException('Red value must be between 0 and 255');
        }

        if ($green < 0 || $green > 255) {
            throw new InvalidArgumentException('Green value must be between 0 and 255');
        }

        if ($blue < 0 || $blue > 255) {
            throw new InvalidArgumentException('Blue value must be between 0 and 255');
        }
    }

    public static function make(int $red, int $green, int $blue): self
    {
        return new self($red, $green, $blue);
    }

    public static function fromString(string $value): self
    {
        return new ColorParser()->parse($value);
    }
}
