<?php

declare(strict_types=1);

use Dllobell\Qr\Standard\StandardQrCodeEcl;
use Dllobell\Qr\Standard\StandardQrEncoder;
use Dllobell\Qr\Tests\Support\MatrixFormatter;

describe('StandardQrEncoder golden matrices', function (): void {
    /**
     * Golden tests pin version, ECL, mask, and optimize so the matrix is spec-determined
     * for a given payload. They survive internal refactors but will change if encoding
     * strategy (segment choice, padding path, etc.) is intentionally changed.
     */
    it('matches a pinned module matrix', function (
        string $text,
        StandardQrCodeEcl $ecl,
        int $version,
        int $mask,
    ): void {
        $qr = StandardQrEncoder::create()->encode(
            text: $text,
            ecl: $ecl,
            minVersion: $version,
            maxVersion: $version,
            mask: $mask,
        );

        expect(MatrixFormatter::encodeSnapshot($qr))->toMatchSnapshot();
    })->with([
        'numeric v1 low mask0' => ['1', StandardQrCodeEcl::Low, 1, 0],
        'alphanumeric v1 low mask0' => ['ABC', StandardQrCodeEcl::Low, 1, 0],
        'byte v1 low mask0' => ['abc', StandardQrCodeEcl::Low, 1, 0],
        'alphanumeric v1 high mask0' => ['ABCDEF', StandardQrCodeEcl::High, 1, 0],
        'byte v2 medium mask3' => ['hello', StandardQrCodeEcl::Medium, 2, 3],
    ]);
});
