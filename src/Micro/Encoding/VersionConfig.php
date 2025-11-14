<?php

declare(strict_types=1);

namespace Dllobell\Qr\Micro\Encoding;

use Dllobell\Qr\Micro\MicroQrCodeEcl;
use Dllobell\Qr\Micro\MicroQrCodeVersion;

final readonly class VersionConfig
{
    private const array CONFIG = [
        1 => [
            'symbolNumber' => 0, 'dataCodewords' => 3, 'dataBits' => 20, 'ecCodewords' => 2, // Error detection only; total codewords = 5
        ],
        2 => [
            MicroQrCodeEcl::Low->value => ['symbolNumber' => 1, 'dataCodewords' => 5, 'dataBits' => 40, 'ecCodewords' => 5],  // total codewords 10
            MicroQrCodeEcl::Medium->value => ['symbolNumber' => 2, 'dataCodewords' => 4, 'dataBits' => 32, 'ecCodewords' => 6],
        ],
        3 => [
            MicroQrCodeEcl::Low->value => ['symbolNumber' => 3, 'dataCodewords' => 11, 'dataBits' => 84, 'ecCodewords' => 6], // total codewords 17
            MicroQrCodeEcl::Medium->value => ['symbolNumber' => 4, 'dataCodewords' => 9, 'dataBits' => 68, 'ecCodewords' => 8],
        ],
        4 => [
            MicroQrCodeEcl::Low->value => ['symbolNumber' => 5, 'dataCodewords' => 16, 'dataBits' => 128, 'ecCodewords' => 8], // total codewords 24
            MicroQrCodeEcl::Medium->value => ['symbolNumber' => 6, 'dataCodewords' => 14, 'dataBits' => 112, 'ecCodewords' => 10],
            MicroQrCodeEcl::Quartile->value => ['symbolNumber' => 7, 'dataCodewords' => 10, 'dataBits' => 80, 'ecCodewords' => 14],
        ],
    ];

    private function __construct(
        public int $symbolNumber,
        public int $dataCodewords,
        public int $dataBitsCapacity,
        public int $totalErrorCorrectionCodewords,
    ) {}

    public static function for(MicroQrCodeVersion $version, MicroQrCodeEcl $ecl): self
    {
        $versionConfig = self::CONFIG[$version->value];

        if ($version->value === 1) {
            return new self(
                symbolNumber: $versionConfig['symbolNumber'],
                dataCodewords: $versionConfig['dataCodewords'],
                dataBitsCapacity: $versionConfig['dataBits'],
                totalErrorCorrectionCodewords: $versionConfig['ecCodewords'],
            );
        }

        return new self(
            symbolNumber: $versionConfig[$ecl->value]['symbolNumber'],
            dataCodewords: $versionConfig[$ecl->value]['dataCodewords'],
            dataBitsCapacity: $versionConfig[$ecl->value]['dataBits'],
            totalErrorCorrectionCodewords: $versionConfig[$ecl->value]['ecCodewords'],
        );
    }
}
