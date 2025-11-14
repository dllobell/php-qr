<?php

declare(strict_types=1);

namespace Dllobell\Qr\Core;

use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, array<int, bool>>
 */
final class BitMatrix implements IteratorAggregate
{
    /**
     * @param array<int, array<int, bool>> $matrix
     */
    private function __construct(
        public readonly int $width,
        public readonly int $height,
        public array $matrix,
    ) {}

    public static function create(int $width, int $height): self
    {
        $matrix = array_fill(0, $height, array_fill(0, $width, false));

        return new self($width, $height, $matrix);
    }

    public function set(int $row, int $col, bool $value): void
    {
        $this->matrix[$row][$col] = $value;
    }

    public function get(int $row, int $col): bool
    {
        return $this->matrix[$row][$col];
    }

    public function invert(int $row, int $col): void
    {
        $this->matrix[$row][$col] = !$this->matrix[$row][$col];
    }

    public function clone(): self
    {
        return new self($this->width, $this->height, $this->matrix);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->matrix as $row) {
            yield $row;
        }
    }
}
