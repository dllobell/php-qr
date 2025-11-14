<?php

declare(strict_types=1);

namespace Dllobell\Qr\Standard;

enum StandardQrCodeModuleType
{
    case Data;
    case Finder;
    case Separator;
    case Timing;
    case Alignment;
    case FormatInformation;
    case VersionInformation;
    case DarkModule;
}
