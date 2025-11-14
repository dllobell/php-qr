<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Dllobell\Qr\Rendering\Color\Color;
use Dllobell\Qr\Rendering\Color\Gradient;

final readonly class EyeFill
{
    private function __construct(
        public Color | Gradient $external,
        public Color | Gradient $internal,
    ) {}

    public static function make(
        Color | Gradient | string $external = 'black',
        Color | Gradient | string $internal = 'white',
    ): self {
        return new self(
            $external instanceof Color || $external instanceof Gradient ? $external : Color::fromString($external),
            $internal instanceof Color || $internal instanceof Gradient ? $internal : Color::fromString($internal),
        );
    }
}
