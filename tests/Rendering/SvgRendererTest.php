<?php

declare(strict_types=1);

use Dllobell\Qr\Rendering\Fill;
use Dllobell\Qr\Rendering\RenderOptions;
use Dllobell\Qr\Rendering\SvgRenderer;
use Dllobell\Qr\Standard\StandardQrEncoder;

describe('SvgRenderer', function (): void {
    it('should render a QR code as SVG', function (string $text, int $scale, string $lightColor, string $darkColor): void {
        $renderer = SvgRenderer::create();

        $qr = StandardQrEncoder::create()->encode($text);

        $svg = $renderer->render($qr, RenderOptions::make(
            size: $scale,
            padding: 0,
            fill: Fill::make(background: $lightColor, foreground: $darkColor),
        ));

        expect($svg)->toMatchSnapshot();
    })->with([
        ['qrcode', 10, 'white', 'black'],
        ['hello', 8, '#fff', '#222'],
        ['test', 12, 'yellow', 'blue'],
    ]);
});
