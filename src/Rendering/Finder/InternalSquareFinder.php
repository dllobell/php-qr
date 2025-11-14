<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Finder;

use Dllobell\Qr\Rendering\Path\Path;
use Dllobell\Qr\Rendering\Path\PathMaker;

final readonly class InternalSquareFinder implements PathMaker
{
    public function makePath(): Path
    {
        return Path::make()
            ->move(-1.5, -1.5)
            ->line(1.5, -1.5)
            ->line(1.5, 1.5)
            ->line(-1.5, 1.5)
            ->close()
        ;
    }
}
