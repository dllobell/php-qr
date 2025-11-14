<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Gd;

use GdImage;

interface Format
{
    public function render(GdImage $image): string;
}
