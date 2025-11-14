<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Gd;

use GdImage;
use InvalidArgumentException;

final readonly class PngFormat implements Format
{
    private function __construct(private int $compression)
    {
        if ($compression < 0 || $compression > 9) {
            throw new InvalidArgumentException('PNG compression level must be between 0 and 9');
        }
    }

    public static function make(int $compression): self
    {
        return new self($compression);
    }

    public function render(GdImage $image): string
    {
        ob_start();

        imagepng($image, quality: $this->compression);

        return (string) ob_get_clean();
    }
}
