<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Imagick;

use Dllobell\Qr\Rendering\Path\Command;
use Dllobell\Qr\Rendering\Path\CommandVisitor;
use Dllobell\Qr\Rendering\Path\Path;
use ImagickDraw;

/**
 * @implements CommandVisitor<bool>
 */
final readonly class PathDrawer implements CommandVisitor
{
    public function __construct(private ImagickDraw $draw) {}

    public function draw(Path $path): void
    {
        $this->draw->pathStart();

        foreach ($path->commands as $command) {
            $command->accept($this);
        }

        $this->draw->pathFinish();
    }

    public function visitMove(Command\Move $command): bool
    {
        if ($command->absolute) {
            return $this->draw->pathMoveToAbsolute($command->x, $command->y);
        }

        return $this->draw->pathMoveToRelative($command->x, $command->y);
    }

    public function visitLine(Command\Line $command): bool
    {
        if ($command->absolute) {
            return $this->draw->pathLineToAbsolute($command->x, $command->y);
        }

        return $this->draw->pathLineToRelative($command->x, $command->y);
    }

    public function visitHorizontal(Command\Horizontal $command): bool
    {
        if ($command->absolute) {
            return $this->draw->pathLineToHorizontalAbsolute($command->x);
        }

        return $this->draw->pathLineToHorizontalRelative($command->x);
    }

    public function visitVertical(Command\Vertical $command): bool
    {
        if ($command->absolute) {
            return $this->draw->pathLineToVerticalAbsolute($command->y);
        }

        return $this->draw->pathLineToVerticalRelative($command->y);
    }

    public function visitEllipticArc(Command\EllipticArc $command): bool
    {
        if ($command->absolute) {
            return $this->draw->pathEllipticArcAbsolute(
                $command->rx,
                $command->ry,
                $command->xAxisRotation,
                $command->largeArc,
                $command->sweep,
                $command->x,
                $command->y,
            );
        }

        return $this->draw->pathEllipticArcRelative(
            $command->rx,
            $command->ry,
            $command->xAxisRotation,
            $command->largeArc,
            $command->sweep,
            $command->x,
            $command->y,
        );
    }

    public function visitClose(Command\Close $command): bool
    {
        return $this->draw->pathClose();
    }
}
