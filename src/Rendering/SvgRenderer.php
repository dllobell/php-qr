<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Closure;
use Dllobell\Qr\Core\QrCode;
use Dllobell\Qr\Core\QrCodeModules;
use Dllobell\Qr\Rendering\Color\Color;
use Dllobell\Qr\Rendering\Color\Gradient;
use Dllobell\Qr\Rendering\Color\GradientType;
use Dllobell\Qr\Rendering\Module\Neighbours;
use Dllobell\Qr\Rendering\Path\Path;
use Dllobell\Qr\Rendering\Svg\PathRenderer;
use InvalidArgumentException;

final readonly class SvgRenderer
{
    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public function render(QrCode $qr, RenderOptions $options): string
    {
        $backgroundColor = $this->getColorString($options->fill->background);

        $viewBoxWidth = $qr->modules->width() + $options->padding->left + $options->padding->right;
        $viewBoxHeight = $qr->modules->height() + $options->padding->top + $options->padding->bottom;

        $sizeAttributes = $options->size !== null
            ? " width=\"{$options->size}\" height=\"{$options->size}\""
            : '';

        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$viewBoxWidth} {$viewBoxHeight}\"{$sizeAttributes}>";

        $svg .= "<rect fill=\"{$backgroundColor}\" width=\"100%\" height=\"100%\"/>";

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

        $svg .= $this->renderForeground($qr, $options, $filter);

        if ($options->image !== null && $imageCover !== null) {
            $svg .= $this->renderImage($options->image, $imageCover, $options);
        }

        $svg .= '</svg>';

        return $svg;
    }

    private function renderForeground(QrCode $qr, RenderOptions $options, Closure $filter): string
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

        $renderer = new PathRenderer();

        $result = '';

        foreach ($qr->getFinderPositions() as [$row, $col, $rotation]) {
            $translateX = $col + $options->padding->left + 3.5;
            $translateY = $row + $options->padding->top + 3.5;

            $externalPath = $options->finderStyle->externalPath->rotate($rotation)->translate($translateX, $translateY);
            $internalPath = $options->finderStyle->internalPath->rotate($rotation)->translate($translateX, $translateY);

            if ($options->fill->eye !== null) {
                if ($options->fill->eye->external instanceof Gradient) {
                    $id = 'gradient-eye-external';

                    $defs = $this->buildGradientDefinition($options->fill->eye->external, $id);

                    $result .= $defs.$renderer->render($externalPath, fill: "url(#{$id})");
                } else {
                    $fill = $this->getColorString($options->fill->eye->external);

                    $result .= $renderer->render($externalPath, $fill);
                }

                if ($options->fill->eye->internal instanceof Gradient) {
                    $id = 'gradient-eye-internal';

                    $defs = $this->buildGradientDefinition($options->fill->eye->internal, $id);

                    $result .= $defs.$renderer->render($internalPath, fill: "url(#{$id})");
                } else {
                    $fill = $this->getColorString($options->fill->eye->internal);

                    $result .= $renderer->render($internalPath, $fill);
                }
            } else {
                $modulesPath->append($externalPath);
                $modulesPath->append($internalPath);
            }
        }

        if ($options->fill->foreground instanceof Gradient) {
            $id = 'gradient-module';

            $defs = $this->buildGradientDefinition($options->fill->foreground, $id);

            $result .= $defs.$renderer->render($modulesPath, fill: "url(#{$id})");
        } else {
            $fill = $this->getColorString($options->fill->foreground);

            $result .= $renderer->render($modulesPath, $fill);
        }

        return $result;
    }

    private function buildGradientDefinition(Gradient $gradient, string $id): string
    {
        $from = $this->getColorString($gradient->from);
        $to = $this->getColorString($gradient->to);

        $gradient = $gradient->type === GradientType::Radial
            ? $this->buildRadialGradient($from, $to, $id)
            : $this->buildLinearGradient($gradient->type, $from, $to, $id);

        return "<defs>{$gradient}</defs>";
    }

    private function buildLinearGradient(GradientType $type, string $from, string $to, string $id): string
    {
        [$x1, $y1, $x2, $y2] = match ($type) {
            GradientType::LeftToRight => [0, 0, 1, 0],
            GradientType::RightToLeft => [1, 0, 0, 0],
            GradientType::TopToBottom => [0, 0, 0, 1],
            GradientType::BottomToTop => [0, 1, 0, 0],
            GradientType::TopLeftDiagonal => [0, 0, 1, 1],
            GradientType::TopRightDiagonal => [1, 0, 0, 1],
            GradientType::BottomLeftDiagonal => [0, 1, 1, 0],
            GradientType::BottomRightDiagonal => [1, 1, 0, 0],
            default => throw new InvalidArgumentException('Invalid gradient type for linear gradient'),
        };

        $gradient = "<linearGradient id=\"{$id}\" x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\">";
        $gradient .= "<stop offset=\"0%\" stop-color=\"{$from}\"/>";
        $gradient .= "<stop offset=\"100%\" stop-color=\"{$to}\"/>";
        $gradient .= '</linearGradient>';

        return $gradient;
    }

    private function buildRadialGradient(string $from, string $to, string $id): string
    {
        $gradient = "<radialGradient id=\"{$id}\" r=\"0.7\">";
        $gradient .= "<stop offset=\"0%\" stop-color=\"{$from}\"/>";
        $gradient .= "<stop offset=\"100%\" stop-color=\"{$to}\"/>";
        $gradient .= '</radialGradient>';

        return $gradient;
    }

    private function buildNeighbours(int $row, int $col, QrCodeModules $qr, Closure $filter): Neighbours
    {
        $hasNeighbour = function (int $row, int $col) use ($qr, $filter): bool {
            if ($row < 0 || $col < 0 || $row >= $qr->height() || $col >= $qr->width()) {
                return false;
            }

            return $qr->isDark($row, $col) && $filter($row, $col);
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
     * @return array{float, float, float, float}|null
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
     */
    private function renderImage(Image $image, array $imageCover, RenderOptions $options): string
    {
        $x = $imageCover[0] + $options->padding->left;
        $y = $imageCover[2] + $options->padding->top;

        $width = $imageCover[1] - $imageCover[0];
        $height = $imageCover[3] - $imageCover[2];

        $contents = file_get_contents($image->path);

        if ($contents === false) {
            throw new InvalidArgumentException("Unable to read image file at path: {$image->path}");
        }

        $logoData = base64_encode($contents);
        $mimeType = mime_content_type($image->path);
        $url = "data:{$mimeType};base64,{$logoData}";

        return "<image x=\"{$x}\" y=\"{$y}\" width=\"{$width}\" height=\"{$height}\" href=\"{$url}\" />";
    }

    private function getColorString(Color $color): string
    {
        $red = sprintf('%02x', $color->red);
        $green = sprintf('%02x', $color->green);
        $blue = sprintf('%02x', $color->blue);

        if ($red[0] === $red[1] && $green[0] === $green[1] && $blue[0] === $blue[1]) {
            return "#{$red[0]}{$green[0]}{$blue[0]}";
        }

        return "#{$red}{$green}{$blue}";
    }
}
