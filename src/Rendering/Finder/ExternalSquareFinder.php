<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Finder;

use Dllobell\Qr\Rendering\Path\Path;
use Dllobell\Qr\Rendering\Path\PathMaker;

final readonly class ExternalSquareFinder implements PathMaker
{
    public function makePath(): Path
    {
        return Path::make()
            ->move(-3.5, -3.5)
            ->line(3.5, -3.5)
            ->line(3.5, 3.5)
            ->line(-3.5, 3.5)
            ->close()
            ->move(-2.5, -2.5)
            ->line(2.5, -2.5)
            ->line(2.5, 2.5)
            ->line(-2.5, 2.5)
            ->close()
        ;
    }
}
