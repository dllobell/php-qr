<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Imagick;

use Imagick;
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

    public function configureImage(Imagick $image): void
    {
        $image->setImageFormat('jpg');
        $image->setImageCompression(Imagick::COMPRESSION_JPEG);
        $image->setImageCompressionQuality($this->quality);
    }
}
