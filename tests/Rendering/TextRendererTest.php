<?php

declare(strict_types=1);

use Dllobell\Qr\Rendering\TextRenderer;
use Dllobell\Qr\Standard\StandardQrEncoder;

describe('TextRenderer', function (): void {
    it('should render a QR code as text', function (string $text, string $lightCharacter, string $darkCharacter): void {
        $renderer = new TextRenderer();

        $qr = StandardQrEncoder::create()->encode($text);

        $contents = $renderer->render($qr, lightCharacter: $lightCharacter, darkCharacter: $darkCharacter);

        expect($contents)->toMatchSnapshot();
    })->with([
        ['qrcode', ' ', '█'],
        ['hello', '.', '#'],
        ['test', '-', '*'],
    ]);
});
