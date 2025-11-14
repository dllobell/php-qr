<?php

declare(strict_types=1);

use Dllobell\Qr\Core\Encoding\ReedSolomonParityCalculator;

describe('ReedSolomonParityCalculator', function (): void {
    /**
     * Vectors from http://www.swetake.com/qr/qr3.html — spec-pinned math, not encoder output.
     */
    it('computes error correction bytes for known data codewords', function (
        array $data,
        int $ecCount,
        array $expected,
    ): void {
        $calculator = ReedSolomonParityCalculator::create();

        expect($calculator->calculate($data, $ecCount))->toBe($expected);
    })->with([
        'version 1 sample' => [
            [32, 65, 205, 69, 41, 220, 46, 128, 236],
            17,
            [42, 159, 74, 221, 244, 169, 239, 150, 138, 70, 237, 85, 224, 96, 74, 219, 61],
        ],
        'version 2 sample' => [
            [67, 70, 22, 38, 54, 70, 86, 102, 118, 134, 150, 166, 182, 198, 214],
            18,
            [175, 80, 155, 64, 178, 45, 214, 233, 65, 209, 12, 155, 117, 31, 140, 214, 27, 187],
        ],
        'high-order zero coefficient' => [
            [32, 49, 205, 69, 42, 20, 0, 236, 17],
            17,
            [0, 3, 130, 179, 194, 0, 55, 211, 110, 79, 98, 72, 170, 96, 211, 137, 213],
        ],
    ]);
});
