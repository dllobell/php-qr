<?php

declare(strict_types=1);

namespace Dllobell\Qr\Standard;

use Dllobell\Qr\Core\QrCode;
use Dllobell\Qr\Core\QrCodeModules;

final readonly class StandardQrCode implements QrCode
{
    /**
     * @param array<array<StandardQrCodeModuleType>> $types
     */
    private function __construct(
        public int $version,
        public StandardQrCodeEcl $ecl,
        public int $mask,
        public int $size,
        public QrCodeModules $modules,
        public array $types,
    ) {}

    /**
     * @param array<array<StandardQrCodeModuleType>> $types
     */
    public static function create(
        int $version,
        StandardQrCodeEcl $ecl,
        int $mask,
        int $size,
        QrCodeModules $modules,
        array $types,
    ): self {
        return new self($version, $ecl, $mask, $size, $modules, $types);
    }

    public function isFinderPattern(int $row, int $col): bool
    {
        return $this->types[$row][$col] === StandardQrCodeModuleType::Finder;
    }

    public function getFinderPositions(): array
    {
        $size = $this->modules->width();

        return [
            [0, 0, 0],
            [0, $size - 7, 90],
            [$size - 7, 0, 270],
        ];
    }
}
