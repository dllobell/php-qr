<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Dllobell\Qr\Core\QrCode;

final readonly class TextRenderer
{
    public function render(QrCode $qr, string $darkCharacter, string $lightCharacter): string
    {
        $lines = [];
        foreach ($qr->modules as $cols) {
            $line = '';
            foreach ($cols as $isDark) {
                $line .= $isDark ? $darkCharacter : $lightCharacter;
            }

            $lines[] = $line;
        }

        return implode(PHP_EOL, $lines);
    }
}
