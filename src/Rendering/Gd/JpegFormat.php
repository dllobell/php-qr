<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Gd;

use GdImage;
use InvalidArgumentException;

final readonly class JpegFormat implements Format
{
    private function __construct(private int $quality)
    {
        if ($quality < 1 || $quality > 100) {
            throw new InvalidArgumentException('JPEG quality must be between 1 and 100');
        }
    }

    public static function make(int $quality): self
    {
        return new self($quality);
    }

    public function render(GdImage $image): string
    {
        ob_start();

        imagejpeg($image, quality: $this->quality);

        return (string) ob_get_clean();
    }
}
