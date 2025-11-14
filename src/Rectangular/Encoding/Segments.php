<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rectangular\Encoding;

use ArrayIterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Segment>
 */
final readonly class Segments implements IteratorAggregate
{
    /**
     * @param list<Segment> $segments
     */
    public function __construct(private array $segments) {}

    /**
     * @return ArrayIterator<int, Segment>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->segments);
    }
}
