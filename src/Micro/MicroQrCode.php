<?php

declare(strict_types=1);

namespace Dllobell\Qr\Micro;

use Dllobell\Qr\Core\QrCode;
use Dllobell\Qr\Core\QrCodeModules;

final readonly class MicroQrCode implements QrCode
{
    private function __construct(
        public MicroQrCodeVersion $version,
        public MicroQrCodeEcl $ecl,
        public int $mask,
        public int $size,
        public QrCodeModules $modules,
    ) {}

    public static function create(
        MicroQrCodeVersion $version,
        MicroQrCodeEcl $ecl,
        int $mask,
        int $size,
        QrCodeModules $modules,
    ): self {
        return new self($version, $ecl, $mask, $size, $modules);
    }

    public function isFinderPattern(int $row, int $col): bool
    {
        return $row < 7 && $col < 7;
    }

    public function getFinderPositions(): array
    {
        return [
            [0, 0, 0],
        ];
    }
}
