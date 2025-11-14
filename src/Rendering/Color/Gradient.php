<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Color;

final readonly class Gradient
{
    private function __construct(
        public Color $from,
        public Color $to,
        public GradientType $type,
    ) {}

    public static function make(
        Color | string $from,
        Color | string $to,
        GradientType $type,
    ): self {
        return new self(
            $from instanceof Color ? $from : Color::fromString($from),
            $to instanceof Color ? $to : Color::fromString($to),
            $type,
        );
    }
}
