<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Svg;

use Dllobell\Qr\Rendering\Path\Path;

final readonly class PathRenderer
{
    private CommandRenderer $commandRenderer;

    public function __construct()
    {
        $this->commandRenderer = new CommandRenderer();
    }

    public function render(Path $path, string $fill): string
    {
        $commands = $path->commands
            |> (fn (array $commands) => array_map($this->commandRenderer->render(...), $commands))
            |> (static fn (array $commands) => implode('', $commands));

        return "<path fill=\"{$fill}\" fill-rule=\"evenodd\" d=\"{$commands}\" />";
    }
}
