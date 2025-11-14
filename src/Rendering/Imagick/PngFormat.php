<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Imagick;

use Imagick;
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

    public function configureImage(Imagick $image): void
    {
        $image->setImageFormat('png');
        $image->setImageCompression(Imagick::COMPRESSION_ZIP);
        $image->setImageCompressionQuality($this->compression);
    }
}
