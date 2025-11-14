<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Gd;

use GdImage;
use InvalidArgumentException;

final readonly class WebpFormat implements Format
{
    private function __construct(private int $quality)
    {
        if ($this->quality < 0 || $this->quality > 100) {
            throw new InvalidArgumentException('WebP quality must be between 0 and 100');
        }
    }

    public static function make(int $quality): self
    {
        return new self($quality);
    }

    public function render(GdImage $image): string
    {
        ob_start();

        imagewebp($image, quality: $this->quality);

        return (string) ob_get_clean();
    }
}
