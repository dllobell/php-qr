<?php

declare(strict_types=1);

use Dllobell\Qr\Standard\StandardQrCodeEcl;
use Dllobell\Qr\Standard\StandardQrEncoder;

describe('StandardQrEncoder contract', function (): void {
    it('derives matrix size from version', function (string $text): void {
        $qr = StandardQrEncoder::create()->encode($text);

        expect($qr->size)->toBe($qr->version * 4 + 17)
            ->and($qr->modules->width())->toBe($qr->size)
            ->and($qr->modules->height())->toBe($qr->size);
    })->with([
        'numeric' => ['12345'],
        'alphanumeric' => ['HELLO'],
        'byte' => ['hello'],
    ]);

    it('selects an automatic mask in the legal range', function (): void {
        $qr = StandardQrEncoder::create()->encode('automatic-mask');

        expect($qr->mask)->toBeGreaterThanOrEqual(0)
            ->and($qr->mask)->toBeLessThanOrEqual(7);
    });

    it('honours a forced version when min and max match', function (): void {
        $qr = StandardQrEncoder::create()->encode(
            text: 'forced-version',
            minVersion: 3,
            maxVersion: 3,
        );

        expect($qr->version)->toBe(3);
    });

    it('rejects invalid version bounds', function (): void {
        expect(fn () => StandardQrEncoder::create()->encode('x', minVersion: 0))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => StandardQrEncoder::create()->encode('x', maxVersion: 41))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => StandardQrEncoder::create()->encode('x', minVersion: 5, maxVersion: 3))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects an invalid mask', function (): void {
        expect(fn () => StandardQrEncoder::create()->encode('x', mask: 8))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => StandardQrEncoder::create()->encode('x', mask: -2))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws when the payload does not fit the version range', function (): void {
        expect(fn () => StandardQrEncoder::create()->encode(str_repeat('x', 300), maxVersion: 1))
            ->toThrow(RangeException::class);
    });

    it('accepts every error correction level', function (StandardQrCodeEcl $ecl): void {
        $qr = StandardQrEncoder::create()->encode('a', $ecl, minVersion: 1, maxVersion: 1, mask: 0);

        expect($qr->ecl)->toBe($ecl)
            ->and($qr->mask)->toBe(0);
    })->with(StandardQrCodeEcl::cases());
});
