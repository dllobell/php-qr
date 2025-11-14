<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Path;

interface PathMaker
{
    public function makePath(): Path;
}
