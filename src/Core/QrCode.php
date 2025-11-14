<?php

declare(strict_types=1);

namespace Dllobell\Qr\Core;

interface QrCode
{
    public QrCodeModules $modules { get; }

    public function isFinderPattern(int $row, int $col): bool;

    /**
     * @return list<array{int, int, int}>
     */
    public function getFinderPositions(): array;
}
