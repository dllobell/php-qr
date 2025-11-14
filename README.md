# PHP QR

<p>
<a href="https://packagist.org/packages/dllobell/qr"><img src="https://img.shields.io/packagist/dt/dllobell/qr" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/dllobell/qr"><img src="https://img.shields.io/packagist/v/dllobell/qr" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/dllobell/qr"><img src="https://img.shields.io/packagist/l/dllobell/qr" alt="License"></a>
<a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.5+-777BB4?logo=php" alt="PHP Minimum Version"></a>
</p>

Lightweight, framework-agnostic QR code generation for PHP. Encode data into a module matrix, then render it as SVG, raster images, or terminal output — with optional styling for colors, gradients, finder patterns, and embedded logos.

> [!WARNING]
> This package is a work in progress. APIs may change before the first stable release.

## Requirements

- PHP 8.5 or later
- `ext-mbstring`

Optional extensions for image rendering:

- `ext-gd` — PNG, JPEG, and WebP output via `GdRenderer`
- `ext-imagick` — PNG, JPEG, and WebP output via `ImagickRenderer` (supports gradients and advanced styling)

## Installation

```bash
composer require dllobell/qr
```

## Quick start

Encode a string as a standard QR code and render it to SVG:

```php
use Dllobell\Qr\Rendering\Fill;
use Dllobell\Qr\Rendering\RenderOptions;
use Dllobell\Qr\Rendering\SvgRenderer;
use Dllobell\Qr\Standard\StandardQrEncoder;

$qr = StandardQrEncoder::create()->encode('https://example.com');

$svg = SvgRenderer::create()->render($qr, RenderOptions::make(
    size: 256,
    padding: 2,
    fill: Fill::make(background: 'white', foreground: 'black'),
));

file_put_contents('qr.svg', $svg);
```

Every encoder returns a value object that implements `Dllobell\Qr\Core\QrCode`. Renderers accept that object plus `RenderOptions` (where applicable).

## Encoding

The library supports three QR symbologies. All encoders follow the same pattern: create an encoder instance, call `encode()`, and pass the result to a renderer.

### Standard QR codes

The most common format. Versions 1–40, four error correction levels, automatic version selection, and optional mask optimization.

```php
use Dllobell\Qr\Standard\StandardQrCodeEcl;
use Dllobell\Qr\Standard\StandardQrEncoder;

$encoder = StandardQrEncoder::create();

$qr = $encoder->encode(
    text: 'Hello, world!',
    ecl: StandardQrCodeEcl::Medium,
    minVersion: 1,
    maxVersion: 40,
    mask: -1,      // -1 = pick the best mask automatically
    optimize: false,
);
```

| Parameter | Default | Description |
|-----------|---------|-------------|
| `ecl` | `Low` | Error correction level: `Low`, `Medium`, `Quartile`, `High` |
| `minVersion` | `1` | Smallest version to try |
| `maxVersion` | `40` | Largest version to try |
| `mask` | `-1` | Data mask pattern (`0`–`7`), or `-1` for automatic selection |
| `optimize` | `false` | Split the payload into multiple segments for a more compact encoding |

Text is encoded as UTF-8 byte mode. Numeric, alphanumeric, byte, and kanji modes are selected automatically per segment.

### Micro QR codes

Compact codes for very short payloads (versions M1–M4).

```php
use Dllobell\Qr\Micro\MicroQrCodeEcl;
use Dllobell\Qr\Micro\MicroQrCodeEncoder;

$qr = MicroQrCodeEncoder::create()->encode(
    text: '12345',
    ecl: MicroQrCodeEcl::Low,
    version: null, // null = pick the smallest version that fits
    mask: null,    // null = pick the best mask automatically
);
```

Error correction levels: `Low`, `Medium`, `Quartile`.

### Rectangular QR codes

Non-square matrix layouts for payloads that fit better in a wide or tall rectangle.

```php
use Dllobell\Qr\Rectangular\Encoding\Fit;
use Dllobell\Qr\Rectangular\RectangularQrCodeEcl;
use Dllobell\Qr\Rectangular\RectangularQrCodeEncoder;

$qr = RectangularQrCodeEncoder::create()->encode(
    text: 'RECT-QR',
    ecl: RectangularQrCodeEcl::Medium,
    fit: Fit::Balanced,
);
```

| `Fit` case | Behavior |
|------------|----------|
| `Smallest` | Smallest overall area |
| `SmallestHeight` | Minimize height |
| `SmallestWidth` | Minimize width |
| `Balanced` | Prefer a balanced aspect ratio |

Error correction levels: `Medium`, `High`.

## Rendering

### SVG

`SvgRenderer` is the most capable backend. It supports custom module shapes, finder styles, gradients, separate eye colors, padding, and embedded background images.

```php
use Dllobell\Qr\Rendering\Fill;
use Dllobell\Qr\Rendering\RenderOptions;
use Dllobell\Qr\Rendering\SvgRenderer;

$svg = SvgRenderer::create()->render($qr, RenderOptions::make(
    size: 300,   // output width/height in pixels; omit for a scalable SVG with no fixed size
    padding: 4,  // quiet zone in modules; or pass a Padding instance for per-side values
    fill: Fill::make(
        background: '#ffffff',
        foreground: '#1a1a2e',
    ),
));
```

### GD

Requires `ext-gd`. Produces binary image data (PNG, JPEG, or WebP). A fixed `size` is required. Gradient foregrounds are not supported.

```php
use Dllobell\Qr\Rendering\Fill;
use Dllobell\Qr\Rendering\Gd\PngFormat;
use Dllobell\Qr\Rendering\GdRenderer;
use Dllobell\Qr\Rendering\RenderOptions;

$png = GdRenderer::create(PngFormat::make(compression: 6))->render($qr, RenderOptions::make(
    size: 256,
    padding: 2,
    fill: Fill::make(),
));

file_put_contents('qr.png', $png);
```

Other GD formats: `Gd\JpegFormat::make(quality: 90)`, `Gd\WebpFormat::make(quality: 80)`.

### Imagick

Requires `ext-imagick`. Supports the same advanced styling as SVG (gradients, custom shapes, finder styles, embedded images).

```php
use Dllobell\Qr\Rendering\Fill;
use Dllobell\Qr\Rendering\Imagick\PngFormat;
use Dllobell\Qr\Rendering\ImagickRenderer;
use Dllobell\Qr\Rendering\RenderOptions;

$png = ImagickRenderer::create(PngFormat::make(compression: 6))->render($qr, RenderOptions::make(
    size: 256,
    padding: 2,
    fill: Fill::make(),
));

file_put_contents('qr.png', $png);
```

Other Imagick formats: `Imagick\JpegFormat::make(quality: 90)`, `Imagick\WebpFormat::make(quality: 80)`.

### Text and ANSI

Useful for debugging and CLI output. These renderers do not use `RenderOptions`.

```php
use Dllobell\Qr\Rendering\ANSIRenderer;
use Dllobell\Qr\Rendering\TextRenderer;

// Plain text (█ for dark modules, space for light)
echo (new TextRenderer())->render($qr, darkCharacter: '█', lightCharacter: ' ');

// ANSI-colored blocks for terminals that support escape codes
echo (new ANSIRenderer())->render($qr);
```

## Customization

`RenderOptions::make()` accepts optional styling arguments beyond `size` and `padding`:

```php
RenderOptions::make(
    size: 256,
    padding: Padding::symmetric(vertical: 2, horizontal: 4),
    moduleShape: new SquareModule(),
    finderStyle: FinderStyle::make(),
    fill: Fill::make(/* ... */),
    image: Image::make(path: 'logo.png', widthPercent: 0.2, heightPercent: 0.2, hideBackground: true),
);
```

### Colors and gradients

Colors accept hex strings (`#ff0000`), named colors (`red`), or `Color` instances. SVG and Imagick support gradient foregrounds and eye fills:

```php
use Dllobell\Qr\Rendering\Color\Gradient;
use Dllobell\Qr\Rendering\Color\GradientType;
use Dllobell\Qr\Rendering\EyeFill;
use Dllobell\Qr\Rendering\Fill;

$fill = Fill::make(
    background: 'white',
    foreground: Gradient::make('navy', 'cyan', GradientType::TopLeftDiagonal),
    eye: EyeFill::make(external: 'navy', internal: 'white'),
);
```

Available gradient directions: `LeftToRight`, `RightToLeft`, `TopToBottom`, `BottomToTop`, diagonal variants, and `Radial`.

### Padding

```php
use Dllobell\Qr\Rendering\Padding;

Padding::all(4);                        // 4 modules on every side
Padding::symmetric(vertical: 2, horizontal: 4);
Padding::make(top: 1, right: 2, bottom: 1, left: 2);
```

### Module shapes and finder styles

Implement `ModuleShape` to draw custom module geometry. The built-in `SquareModule` is the default.

`FinderStyle` controls the outer and inner paths of the three finder patterns. Defaults are provided via `ExternalSquareFinder` and `InternalSquareFinder`; pass custom `Path` or `PathMaker` instances to override them.

### Embedded logo

Place an image in the center of the code. When `hideBackground` is `true`, modules behind the logo are omitted so the image shows through clearly. Supported by `SvgRenderer` and `ImagickRenderer`.

```php
use Dllobell\Qr\Rendering\Image;

Image::make(
    path: '/path/to/logo.png',
    widthPercent: 0.25,
    heightPercent: 0.25,
    hideBackground: true,
);
```

## Credits

Encoding logic for the Standard QR code is ported from [nayuki/QR-Code-generator](https://github.com/nayuki/QR-Code-generator) by Project Nayuki.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
