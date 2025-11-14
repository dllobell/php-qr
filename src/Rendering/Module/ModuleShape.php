<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Module;

use Dllobell\Qr\Rendering\Path\Path;

interface ModuleShape
{
    public function path(int $row, int $col, Neighbours $neighbours): Path;
}
