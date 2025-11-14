<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Dllobell\Qr\Core\QrCode;

final readonly class ANSIRenderer
{
    private TextRenderer $renderer;

    public function __construct()
    {
        $this->renderer = new TextRenderer();
    }

    public function render(QrCode $qr): string
    {
        return $this->renderer->render(
            qr: $qr,
            lightCharacter: "\x1B[47m　\x1B[0m",
            darkCharacter: "\x1B[40m　\x1B[0m",
        );
    }
}
