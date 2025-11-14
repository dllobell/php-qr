<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Dllobell\Qr\Core\QrCode;
use Dllobell\Qr\Rendering\Color\Color;
use Dllobell\Qr\Rendering\Color\Gradient;
use Dllobell\Qr\Rendering\Gd\Format;
use GdImage;
use InvalidArgumentException;
use RuntimeException;

final readonly class GdRenderer
{
    private function __construct(private Format $format) {}

    public static function create(Format $format): self
    {
        return new self($format);
    }

    public function render(QrCode $qr, RenderOptions $options): string
    {
        if ($options->size === null) {
            throw new InvalidArgumentException('Size must be specified for GdRenderer');
        }

        if ($options->fill->foreground instanceof Gradient) {
            throw new InvalidArgumentException('GdRenderer does not support gradient foreground colors');
        }

        $scale = $this->calculateScale($qr, $options);

        $image = imagecreatetruecolor($options->size, $options->size);

        if ($image === false) {
            throw new RuntimeException('Failed to create image');
        }

        $darkColor = $this->allocateColor($image, $options->fill->foreground);
        $lightColor = $this->allocateColor($image, $options->fill->background);

        imagefill($image, 0, 0, $lightColor);

        foreach ($qr->modules as $row => $cols) {
            foreach ($cols as $col => $isDark) {
                if ($isDark) {
                    imagefilledrectangle(
                        $image,
                        (int) round(($col + $options->padding->left) * $scale[0]),
                        (int) round(($row + $options->padding->top) * $scale[1]),
                        (int) round(($col + $options->padding->left + 1) * $scale[0]) - 1,
                        (int) round(($row + $options->padding->top + 1) * $scale[1]) - 1,
                        $darkColor,
                    );
                }
            }
        }

        return $this->format->render($image);
    }

    /**
     * @return array{float, float}
     */
    private function calculateScale(QrCode $qr, RenderOptions $options): array
    {
        $horizotanl = $qr->modules->width() + $options->padding->left + $options->padding->right;
        $vertical = $qr->modules->height() + $options->padding->top + $options->padding->bottom;

        return [
            $options->size / $horizotanl,
            $options->size / $vertical,
        ];
    }

    private function allocateColor(GdImage $image, Color $color): int
    {
        $color = imagecolorallocate($image, $color->red, $color->green, $color->blue);

        if ($color === false) {
            throw new RuntimeException('Failed to allocate color');
        }

        return $color;
    }
}
