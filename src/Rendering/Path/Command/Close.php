<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Path\Command;

use Dllobell\Qr\Rendering\Path\Command;
use Dllobell\Qr\Rendering\Path\CommandVisitor;

final readonly class Close extends Command
{
    public function __construct(public bool $absolute) {}

    public function accept(CommandVisitor $visitor): mixed
    {
        return $visitor->visitClose($this);
    }

    public function translate(float $x, float $y): self
    {
        return $this;
    }

    public function rotate(float $degrees): self
    {
        return $this;
    }
}
