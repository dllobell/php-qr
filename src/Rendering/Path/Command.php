<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Path;

abstract readonly class Command
{
    /**
     * @template T
     *
     * @param CommandVisitor<T> $visitor
     *
     * @return T
     */
    abstract public function accept(CommandVisitor $visitor): mixed;

    abstract public function translate(float $x, float $y): self;

    abstract public function rotate(float $degrees): self;
}
