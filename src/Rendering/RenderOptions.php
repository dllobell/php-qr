<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Dllobell\Qr\Rendering\Module\ModuleShape;
use Dllobell\Qr\Rendering\Module\SquareModule;
use InvalidArgumentException;

/**
 * @property int<1, max>|null $size
 */
final readonly class RenderOptions
{
    private function __construct(
        public ?int $size,
        public Padding $padding,
        public ModuleShape $moduleShape,
        public FinderStyle $finderStyle,
        public Fill $fill,
        public ?Image $image,
    ) {
        if ($size !== null && $size <= 0) {
            throw new InvalidArgumentException('Size must be a positive integer');
        }
    }

    public static function make(
        ?int $size,
        int | Padding $padding,
        ?ModuleShape $moduleShape = null,
        ?FinderStyle $finderStyle = null,
        ?Fill $fill = null,
        ?Image $image = null,
    ): self {
        return new self(
            $size,
            $padding instanceof Padding ? $padding : Padding::all($padding),
            $moduleShape ?? new SquareModule(),
            $finderStyle ?? FinderStyle::make(),
            $fill ?? Fill::make(),
            $image,
        );
    }
}
