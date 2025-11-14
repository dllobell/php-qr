<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rectangular\Encoding;

use Dllobell\Qr\Core\Encoding\BitBuffer;

final readonly class Segment
{
    public function __construct(
        public Mode $mode,
        public BitBuffer $bits,
        public int $length,
    ) {}
}
