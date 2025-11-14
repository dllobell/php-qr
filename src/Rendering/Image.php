<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use InvalidArgumentException;

final readonly class Image
{
    private function __construct(
        public string $path,
        public bool $hideBackground,
        public float $widthPercent,
        public float $heightPercent,
    ) {
        if ($widthPercent <= 0 || $widthPercent > 1) {
            throw new InvalidArgumentException('Width percent must be between 0 (exclusive) and 1 (inclusive)');
        }

        if ($heightPercent <= 0 || $heightPercent > 1) {
            throw new InvalidArgumentException('Height percent must be between 0 (exclusive) and 1 (inclusive)');
        }
    }

    public static function make(
        string $path,
        float $widthPercent,
        float $heightPercent,
        bool $hideBackground,
    ): self {
        return new self($path, $hideBackground, $widthPercent, $heightPercent);
    }
}
