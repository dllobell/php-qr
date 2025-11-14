<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rectangular\Encoding;

enum Fit
{
    case Smallest;
    case SmallestHeight;
    case SmallestWidth;
    case Balanced;
}
