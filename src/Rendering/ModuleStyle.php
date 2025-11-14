<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Dllobell\Qr\Rendering\Color\Color;
use Dllobell\Qr\Rendering\Color\Gradient;
use Dllobell\Qr\Rendering\Module\ModuleShape;
use Dllobell\Qr\Rendering\Module\SquareModule;

final readonly class ModuleStyle
{
    private function __construct(
        public Color | Gradient $color,
        public ModuleShape $shape,
    ) {}

    public static function make(
        Color | Gradient | string $color = 'black',
        ?ModuleShape $shape = null,
    ): self {
        return new self(
            $color instanceof Color || $color instanceof Gradient ? $color : Color::fromString($color),
            $shape ?? new SquareModule(),
        );
    }
}
