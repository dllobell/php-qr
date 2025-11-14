<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rectangular;

use Dllobell\Qr\Core\QrCode;
use Dllobell\Qr\Core\QrCodeModules;

final readonly class RectangularQrCode implements QrCode
{
    private function __construct(
        public RectangularQrCodeVersion $version,
        public RectangularQrCodeEcl $ecl,
        public int $width,
        public int $height,
        public QrCodeModules $modules,
    ) {}

    public static function create(
        RectangularQrCodeVersion $version,
        RectangularQrCodeEcl $ecl,
        QrCodeModules $modules,
    ): self {
        return new self($version, $ecl, $modules->width(), $modules->height(), $modules);
    }

    public function isFinderPattern(int $row, int $col): bool
    {
        return $row <= 7 && $col <= 7;
    }

    public function getFinderPositions(): array
    {
        return [
            [0, 0, 0],
        ];
    }
}
