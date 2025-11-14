<?php

declare(strict_types=1);

namespace Dllobell\Qr\Standard\Encoding;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<Segment>
 */
final readonly class Segments implements IteratorAggregate
{
    /**
     * @param list<Segment> $segments
     */
    public function __construct(private array $segments) {}

    public static function fromText(string $value, string $encoding): self
    {
        return new self($value !== '' ? [Segment::fromText($value, $encoding)] : []);
    }

    public function totalBits(int $version): int
    {
        $result = 0;
        foreach ($this->segments as $segment) {
            $ccbits = $segment->mode->totalCharacterCountBits($version);

            if ($segment->length >= (1 << $ccbits)) {
                return -1;  // The segment's length doesn't fit the field's bit width
            }

            // 4 bits for mode indicator + character count bits + data bits
            $result += 4 + $ccbits + $segment->data->length;
            if ($result > PHP_INT_MAX) {
                return -1; // The sum will overflow an int type
            }
        }

        return $result;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->segments);
    }
}
