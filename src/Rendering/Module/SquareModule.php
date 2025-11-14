<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Module;

use Dllobell\Qr\Rendering\Path\Path;

final readonly class SquareModule implements ModuleShape
{
    public function path(int $row, int $col, Neighbours $neighbours): Path
    {
        return Path::make()
            ->move($col, $row)
            ->horizontal(1, absolute: false)
            ->vertical(1, absolute: false)
            ->horizontal(-1, absolute: false)
            ->close()
        ;
    }
}
