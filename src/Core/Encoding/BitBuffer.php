<?php

declare(strict_types=1);

namespace Dllobell\Qr\Core\Encoding;

use InvalidArgumentException;
use Stringable;

final class BitBuffer implements Stringable
{
    /**
     * @var array<int, bool>
     */
    public private(set) array $data = [];

    public private(set) int $length = 0;

    public function getBit(int $index): int
    {
        return $this->data[$index] ? 1 : 0;
    }

    public function append(int $value, int $length): void
    {
        if ($length < 0 || $length > 31 || BitUtils::unsignedRightShift($value, $length) !== 0) {
            throw new InvalidArgumentException('Value out of range');
        }

        if (PHP_INT_MAX - $this->length < $length) {
            throw new InvalidArgumentException('Maximum length reached');
        }

        for ($i = $length - 1; $i >= 0; $i--, $this->length++) {
            $this->data[$this->length] = (BitUtils::unsignedRightShift($value, $i) & 1) !== 0;
        }
    }

    public function appendBuffer(self $buffer): void
    {
        if (PHP_INT_MAX - $this->length < $buffer->length) {
            throw new InvalidArgumentException('Maximum length reached');
        }

        for ($i = 0; $i < $buffer->length; $i++, $this->length++) {  // Append bit by bit
            $this->data[$this->length] = $buffer->data[$i];
        }
    }

    public function zeroPadRight(int $length): void
    {
        if (PHP_INT_MAX - $this->length < $length) {
            throw new InvalidArgumentException('Maximum length reached');
        }

        for ($i = 0; $i < $length; $i++, $this->length++) {
            $this->data[$this->length] = false;
        }
    }

    public function __toString(): string
    {
        $bits = implode('', array_map(static fn (bool $bit): string => $bit ? '1' : '0', $this->data));

        return rtrim(chunk_split($bits, 8, ' '));
    }
}
