<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Svg;

use Dllobell\Qr\Rendering\Path\Command;
use Dllobell\Qr\Rendering\Path\CommandVisitor;

/**
 * @implements CommandVisitor<string>
 */
final readonly class CommandRenderer implements CommandVisitor
{
    private const int ROUND_PRECISION = 3;

    public function __construct() {}

    public function render(Command $command): string
    {
        return $command->accept($this);
    }

    public function visitMove(Command\Move $command): string
    {
        $x = round($command->x, self::ROUND_PRECISION);
        $y = round($command->y, self::ROUND_PRECISION);

        $letter = $command->absolute ? 'M' : 'm';

        return "{$letter}{$x},{$y}";
    }

    public function visitLine(Command\Line $command): string
    {
        $x = round($command->x, self::ROUND_PRECISION);
        $y = round($command->y, self::ROUND_PRECISION);

        $letter = $command->absolute ? 'L' : 'l';

        return "{$letter}{$x},{$y}";
    }

    public function visitHorizontal(Command\Horizontal $command): string
    {
        $x = round($command->x, self::ROUND_PRECISION);
        $letter = $command->absolute ? 'H' : 'h';

        return "{$letter}{$x}";
    }

    public function visitVertical(Command\Vertical $command): string
    {
        $y = round($command->y, self::ROUND_PRECISION);

        $letter = $command->absolute ? 'V' : 'v';

        return "{$letter}{$y}";
    }

    public function visitEllipticArc(Command\EllipticArc $command): string
    {
        $rx = round($command->rx, self::ROUND_PRECISION);
        $ry = round($command->ry, self::ROUND_PRECISION);

        $x = round($command->x, self::ROUND_PRECISION);
        $y = round($command->y, self::ROUND_PRECISION);

        $letter = $command->absolute ? 'A' : 'a';

        return "{$letter}{$rx},{$ry} {$command->xAxisRotation} ".($command->largeArc ? '1' : '0').' '.($command->sweep ? '1' : '0')." {$x},{$y}";
    }

    public function visitClose(Command\Close $command): string
    {
        return $command->absolute ? 'Z' : 'z';
    }
}
