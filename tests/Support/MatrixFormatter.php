<?php

declare(strict_types=1);

namespace Dllobell\Qr\Tests\Support;

use Dllobell\Qr\Core\QrCodeModules;
use Dllobell\Qr\Standard\StandardQrCode;

final class MatrixFormatter
{
    public static function encodeSnapshot(StandardQrCode $qr): string
    {
        return implode("\n", [
            'version: '.$qr->version,
            'ecl: '.$qr->ecl->name,
            'mask: '.$qr->mask,
            'size: '.$qr->size,
            'matrix:',
            self::modules($qr->modules),
        ]);
    }

    public static function modules(QrCodeModules $modules): string
    {
        $lines = [];

        foreach ($modules as $row) {
            $lines[] = implode('', array_map(static fn (bool $dark): string => $dark ? '1' : '0', $row));
        }

        return implode("\n", $lines);
    }
}
