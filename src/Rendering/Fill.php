<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Dllobell\Qr\Rendering\Color\Color;
use Dllobell\Qr\Rendering\Color\Gradient;

final readonly class Fill
{
    private function __construct(
        public Color $background,
        public Color | Gradient $foreground,
        public ?EyeFill $eye,
    ) {}

    public static function make(
        Color | string $background = 'white',
        Color | Gradient | string $foreground = 'black',
        Color | EyeFill | Gradient | string | null $eye = null,
    ): self {
        return new self(
            self::resolveBackground($background),
            self::resolveForeground($foreground),
            self::resolveEyeFill($eye),
        );
    }

    private static function resolveBackground(Color | string $background): Color
    {
        if ($background instanceof Color) {
            return $background;
        }

        return Color::fromString($background);
    }

    private static function resolveForeground(Color | Gradient | string $foreground): Color | Gradient
    {
        if ($foreground instanceof Color || $foreground instanceof Gradient) {
            return $foreground;
        }

        return Color::fromString($foreground);
    }

    private static function resolveEyeFill(Color | EyeFill | Gradient | string | null $eyeFill): ?EyeFill
    {
        if ($eyeFill === null || $eyeFill instanceof EyeFill) {
            return $eyeFill;
        }

        if ($eyeFill instanceof Color || $eyeFill instanceof Gradient) {
            return EyeFill::make(
                external: $eyeFill,
                internal: $eyeFill,
            );
        }

        return EyeFill::make(
            external: Color::fromString($eyeFill),
            internal: Color::fromString($eyeFill),
        );
    }
}
