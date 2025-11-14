<?php

declare(strict_types=1);

namespace Dllobell\Qr\Core;

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, array<int, bool>>
 */
final readonly class QrCodeModules implements IteratorAggregate
{
    private function __construct(private BitMatrix $matrix) {}

    public static function make(BitMatrix $matrix): self
    {
        return new self($matrix);
    }

    public function width(): int
    {
        return $this->matrix->width;
    }

    public function height(): int
    {
        return $this->matrix->height;
    }

    public function getIterator(): Traversable
    {
        return $this->matrix->getIterator();
    }

    public function isDark(int $row, int $col): bool
    {
        return $this->matrix->get($row, $col);
    }
}
