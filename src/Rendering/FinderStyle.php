<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering;

use Dllobell\Qr\Rendering\Finder\ExternalSquareFinder;
use Dllobell\Qr\Rendering\Finder\InternalSquareFinder;
use Dllobell\Qr\Rendering\Path\Path;
use Dllobell\Qr\Rendering\Path\PathMaker;

final readonly class FinderStyle
{
    private function __construct(
        public Path $externalPath,
        public Path $internalPath,
    ) {}

    public static function make(
        Path | PathMaker | null $externalPath = null,
        Path | PathMaker | null $internalPath = null,
    ): self {
        return new self(
            self::resolvePath($externalPath) ?? new ExternalSquareFinder()->makePath(),
            self::resolvePath($internalPath) ?? new InternalSquareFinder()->makePath(),
        );
    }

    private static function resolvePath(Path | PathMaker | null $path): ?Path
    {
        if ($path === null) {
            return null;
        }

        return $path instanceof Path ? $path : $path->makePath();
    }
}
