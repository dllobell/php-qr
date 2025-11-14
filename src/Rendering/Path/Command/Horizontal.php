<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Path\Command;

use Dllobell\Qr\Rendering\Path\Command;
use Dllobell\Qr\Rendering\Path\CommandVisitor;

final readonly class Horizontal extends Command
{
    public function __construct(public float $x, public bool $absolute) {}

    public function accept(CommandVisitor $visitor): mixed
    {
        return $visitor->visitHorizontal($this);
    }

    public function translate(float $x, float $y): self
    {
        return new self(
            $this->x,
            $this->absolute,
        );
    }

    public function rotate(float $degrees): self
    {
        return $this;
    }
}
