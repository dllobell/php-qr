<?php

declare(strict_types=1);

namespace Dllobell\Qr\Micro;

enum MicroQrCodeEcl: int
{
    case Low = 0;
    case Medium = 1;
    case Quartile = 2;
}
