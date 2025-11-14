<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Closure;
use Dllobell\Qr\Core\QrCode;
use Dllobell\Qr\Core\QrCodeModules;
use Dllobell\Qr\Rendering\Color\Color;
use Dllobell\Qr\Rendering\Color\Gradient;
use Dllobell\Qr\Rendering\Color\GradientType;
use Dllobell\Qr\Rendering\Imagick\Format;
use Dllobell\Qr\Rendering\Imagick\PathDrawer;
use Dllobell\Qr\Rendering\Module\Neighbours;
use Dllobell\Qr\Rendering\Path\Path;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use InvalidArgumentException;

final readonly class ImagickRenderer
{
    private function __construct(private Format $format) {}

    public static function create(Format $format): self
    {
        return new self($format);
    }

    public function render(QrCode $qr, RenderOptions $options): string
    {
        if ($options->size === null) {
            throw new InvalidArgumentException('Size must be specified for ImagickRenderer');
        }

        $scale = $this->calculateScale($qr, $options);

        $image = new Imagick();

        $image->newImage($options->size, $options->size, new ImagickPixel($this->getColorString($options->fill->background)));

        $this->format->configureImage($image);

        $imageCover = $this->calculateImageCover($qr, $options);

        $filter = function (int $row, int $col) use ($qr, $options, $imageCover): bool {
            if ($qr->isFinderPattern($row, $col)) {
                return false;
            }

            if ($options->image === null || !$options->image->hideBackground || $imageCover === null) {
                return true;
            }

            $inside = $col + 1 > $imageCover[0]
                && $col < $imageCover[1]
                && $row + 1 > $imageCover[2]
                && $row < $imageCover[3];

            return !$inside;
        };

        $draw = new ImagickDraw();

        $draw->scale($scale[0], $scale[1]);

        $this->drawForeground($draw, $qr, $options, $scale, $filter);

        $image->drawImage($draw);

        $draw->destroy();

        if ($options->image !== null && $imageCover !== null) {
            $this->drawImage($image, $options->image, $imageCover, $options, $scale);
        }

        $contents = $image->getImageBlob();

        $image->clear();

        return $contents;
    }

    /**
     * @param array{float, float} $scale
     */
    private function drawForeground(ImagickDraw $draw, QrCode $qr, RenderOptions $options, array $scale, Closure $filter): void
    {
        $modulesPath = Path::make();

        foreach ($qr->modules as $row => $cols) {
            foreach ($cols as $col => $isDark) {
                if ($isDark && $filter($row, $col)) {
                    $neighbours = $this->buildNeighbours($row, $col, $qr->modules, $filter);

                    $modulesPath = $modulesPath->append($options->moduleShape->path($row, $col, $neighbours));
                }
            }
        }

        $modulesPath = $modulesPath->translate($options->padding->left, $options->padding->top);

        foreach ($qr->getFinderPositions() as [$row, $col, $rotation]) {
            $translateX = $col + $options->padding->left + 3.5;
            $translateY = $row + $options->padding->top + 3.5;

            $externalPath = $options->finderStyle->externalPath->rotate($rotation)->translate($translateX, $translateY);
            $internalPath = $options->finderStyle->internalPath->rotate($rotation)->translate($translateX, $translateY);

            if ($options->fill->eye !== null) {
                $draw->push();

                if ($options->fill->eye->external instanceof Gradient) {
                    $id = 'gradient-eye-external';

                    $width = (7 + $options->padding->left) * $scale[0];
                    $height = (7 + $options->padding->top) * $scale[1];

                    $this->drawGradient($draw, $options->fill->eye->external, $id, $width, $height);

                    $draw->setFillPatternURL("#{$id}");

                    new PathDrawer($draw)->draw($externalPath);
                } else {
                    $fill = $this->getColorString($options->fill->eye->external);

                    $draw->setFillColor(new ImagickPixel($fill));

                    new PathDrawer($draw)->draw($externalPath);
                }

                $draw->pop();

                $draw->push();

                if ($options->fill->eye->internal instanceof Gradient) {
                    $id = 'gradient-eye-internal';

                    $width = (3 + $options->padding->left) * $scale[0];
                    $height = (3 + $options->padding->top) * $scale[1];

                    $this->drawGradient($draw, $options->fill->eye->internal, $id, $width, $height);

                    $draw->setFillPatternURL("#{$id}");

                    new PathDrawer($draw)->draw($internalPath);
                } else {
                    $fill = $this->getColorString($options->fill->eye->internal);

                    $draw->setFillColor(new ImagickPixel($fill));

                    new PathDrawer($draw)->draw($internalPath);
                }

                $draw->pop();
            } else {
                $modulesPath->append($externalPath);
                $modulesPath->append($internalPath);
            }
        }

        $draw->push();

        if ($options->fill->foreground instanceof Gradient) {
            $id = 'gradient-module';

            $width = ($qr->modules->width() + $options->padding->left) * $scale[0];
            $height = ($qr->modules->height() + $options->padding->top) * $scale[1];

            $this->drawGradient($draw, $options->fill->foreground, $id, $width, $height);

            $draw->setFillPatternURL("#{$id}");
        } else {
            $fill = $this->getColorString($options->fill->foreground);

            $draw->setFillColor(new ImagickPixel($fill));
        }

        new PathDrawer($draw)->draw($modulesPath);

        $draw->pop();
    }

    private function drawGradient(ImagickDraw $draw, Gradient $gradient, string $id, float $width, float $height): void
    {
        $from = $this->getColorString($gradient->from);
        $to = $this->getColorString($gradient->to);

        $gradientImage = new Imagick();

        switch ($gradient->type) {
            case GradientType::LeftToRight:
                $gradientImage->newPseudoImage((int) $height, (int) $width, "gradient:{$from}-{$to}");
                $gradientImage->rotateImage('transparent', -90);
                break;
            case GradientType::RightToLeft:
                $gradientImage->newPseudoImage((int) $height, (int) $width, "gradient:{$from}-{$to}");
                $gradientImage->rotateImage('transparent', 90);
                break;
            case GradientType::TopToBottom:
                $gradientImage->newPseudoImage((int) $width, (int) $height, "gradient:{$from}-{$to}");
                break;
            case GradientType::BottomToTop:
                $gradientImage->newPseudoImage((int) $width, (int) $height, "gradient:{$to}-{$from}");
                break;
            case GradientType::TopLeftDiagonal:
                $gradientImage->newPseudoImage((int) ($width * sqrt(2)), (int) ($height * sqrt(2)), "gradient:{$from}-{$to}");
                $gradientImage->rotateImage('transparent', -45);

                $rotatedWidth = $gradientImage->getImageWidth();
                $rotatedHeight = $gradientImage->getImageHeight();

                $gradientImage->setImagePage($rotatedWidth, $rotatedHeight, 0, 0);
                $gradientImage->cropImage(
                    intdiv($rotatedWidth, 2) - 2,
                    intdiv($rotatedHeight, 2) - 2,
                    intdiv($rotatedWidth, 4) + 1,
                    intdiv($rotatedWidth, 4) + 1,
                );
                break;
            case GradientType::TopRightDiagonal:
                $gradientImage->newPseudoImage((int) ($width * sqrt(2)), (int) ($height * sqrt(2)), "gradient:{$from}-{$to}");
                $gradientImage->rotateImage('transparent', 45);

                $rotatedWidth = $gradientImage->getImageWidth();
                $rotatedHeight = $gradientImage->getImageHeight();

                $gradientImage->setImagePage($rotatedWidth, $rotatedHeight, 0, 0);
                $gradientImage->cropImage(
                    intdiv($rotatedWidth, 2) - 2,
                    intdiv($rotatedHeight, 2) - 2,
                    intdiv($rotatedWidth, 4) + 1,
                    intdiv($rotatedWidth, 4) + 1,
                );
                break;
            case GradientType::BottomLeftDiagonal:
                $gradientImage->newPseudoImage((int) ($width * sqrt(2)), (int) ($height * sqrt(2)), "gradient:{$from}-{$to}");
                $gradientImage->rotateImage('transparent', -135);

                $rotatedWidth = $gradientImage->getImageWidth();
                $rotatedHeight = $gradientImage->getImageHeight();

                $gradientImage->setImagePage($rotatedWidth, $rotatedHeight, 0, 0);
                $gradientImage->cropImage(
                    intdiv($rotatedWidth, 2) - 2,
                    intdiv($rotatedHeight, 2) - 2,
                    intdiv($rotatedWidth, 4) + 1,
                    intdiv($rotatedWidth, 4) + 1,
                );
                break;
            case GradientType::BottomRightDiagonal:
                $gradientImage->newPseudoImage((int) ($width * sqrt(2)), (int) ($height * sqrt(2)), "gradient:{$from}-{$to}");
                $gradientImage->rotateImage('transparent', 135);

                $rotatedWidth = $gradientImage->getImageWidth();
                $rotatedHeight = $gradientImage->getImageHeight();

                $gradientImage->setImagePage($rotatedWidth, $rotatedHeight, 0, 0);
                $gradientImage->cropImage(
                    intdiv($rotatedWidth, 2) - 2,
                    intdiv($rotatedHeight, 2) - 2,
                    intdiv($rotatedWidth, 4) + 1,
                    intdiv($rotatedWidth, 4) + 1,
                );
                break;
            case GradientType::Radial:
                $gradientImage->newPseudoImage((int) ($width * 1.4), (int) ($height * 1.4), "radial-gradient:{$from}-{$to}");

                $scaledWidth = $gradientImage->getImageWidth();
                $scaledHeight = $gradientImage->getImageHeight();

                $gradientImage->cropImage(
                    (int) $width,
                    (int) $height,
                    (int) (($scaledWidth - $width) / 2),
                    (int) (($scaledHeight - $height) / 2),
                );
                break;
            default: throw new InvalidArgumentException('Invalid gradient type');
        }

        $draw->pushPattern($id, 0, 0, $width, $height);
        $draw->composite(Imagick::COMPOSITE_COPY, 0, 0, $width, $height, $gradientImage);
        $draw->popPattern();
    }

    private function buildNeighbours(int $row, int $col, QrCodeModules $modules, Closure $filter): Neighbours
    {
        $hasNeighbour = function (int $row, int $col) use ($modules, $filter): bool {
            if ($row < 0 || $col < 0 || $row >= $modules->height() || $col >= $modules->width()) {
                return false;
            }

            return $modules->isDark($row, $col) && $filter($row, $col);
        };

        return Neighbours::make(
            top: $hasNeighbour($row - 1, $col),
            bottom: $hasNeighbour($row + 1, $col),
            left: $hasNeighbour($row, $col - 1),
            right: $hasNeighbour($row, $col + 1),
            topLeft: $hasNeighbour($row - 1, $col - 1),
            topRight: $hasNeighbour($row - 1, $col + 1),
            bottomLeft: $hasNeighbour($row + 1, $col - 1),
            bottomRight: $hasNeighbour($row + 1, $col + 1),
        );
    }

    /**
     * @return array{float, float, float, float}
     */
    private function calculateImageCover(QrCode $qr, RenderOptions $options): ?array
    {
        if ($options->image === null) {
            return null;
        }

        $width = $qr->modules->width();
        $height = $qr->modules->height();

        return [
            (($width - ($width * $options->image->widthPercent)) / 2),
            (($width + ($width * $options->image->widthPercent)) / 2),
            (($height - ($height * $options->image->heightPercent)) / 2),
            (($height + ($height * $options->image->heightPercent)) / 2),
        ];
    }

    /**
     * @param array{float, float, float, float} $imageCover
     * @param array{float, float} $scale
     */
    private function drawImage(Imagick $target, Image $image, array $imageCover, RenderOptions $options, array $scale): void
    {
        $x = ($imageCover[0] + $options->padding->left) * $scale[0];
        $y = ($imageCover[2] + $options->padding->top) * $scale[1];

        $width = ($imageCover[1] - $imageCover[0]) * $scale[0];
        $height = ($imageCover[3] - $imageCover[2]) * $scale[1];

        $source = new Imagick($image->path);

        $source->cropThumbnailImage((int) $width, (int) $height);

        $target->compositeImage(
            $source,
            Imagick::COMPOSITE_DEFAULT,
            (int) $x,
            (int) $y,
        );

        $source->clear();
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

    private function getColorString(Color $color): string
    {
        return sprintf('rgb(%d, %d, %d)', $color->red, $color->green, $color->blue);
    }
}
