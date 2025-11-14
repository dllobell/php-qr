<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Imagick;

use Imagick;

interface Format
{
    public function configureImage(Imagick $image): void;
}
