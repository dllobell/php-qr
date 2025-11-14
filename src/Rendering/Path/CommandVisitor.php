<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Path;

/**
 * @template T
 */
interface CommandVisitor
{
    /**
     * @return T
     */
    public function visitMove(Command\Move $command): mixed;

    /**
     * @return T
     */
    public function visitLine(Command\Line $command): mixed;

    /**
     * @return T
     */
    public function visitHorizontal(Command\Horizontal $command): mixed;

    /**
     * @return T
     */
    public function visitVertical(Command\Vertical $command): mixed;

    /**
     * @return T
     */
    public function visitEllipticArc(Command\EllipticArc $command): mixed;

    /**
     * @return T
     */
    public function visitClose(Command\Close $command): mixed;
}
