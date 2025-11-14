<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Imagick;

use Imagick;
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

    public function configureImage(Imagick $image): void
    {
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($this->quality);
    }
}
