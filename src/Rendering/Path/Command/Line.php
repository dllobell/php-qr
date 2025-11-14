<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Path\Command;

use Dllobell\Qr\Rendering\Path\Command;
use Dllobell\Qr\Rendering\Path\CommandVisitor;

final readonly class Line extends Command
{
    public function __construct(public float $x, public float $y, public bool $absolute) {}

    public function accept(CommandVisitor $visitor): mixed
    {
        return $visitor->visitLine($this);
    }

    public function translate(float $x, float $y): self
    {
        return new self(
            $this->x + $x,
            $this->y + $y,
            $this->absolute,
        );
    }

    public function rotate(float $degrees): self
    {
        $radians = deg2rad($degrees);

        $sin = sin($radians);
        $cos = cos($radians);

        return new self(
            $this->x * $cos - $this->y * $sin,
            $this->x * $sin + $this->y * $cos,
            $this->absolute,
        );
    }
}
