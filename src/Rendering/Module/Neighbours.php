<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Module;

final readonly class Neighbours
{
    private function __construct(
        public bool $top,
        public bool $bottom,
        public bool $left,
        public bool $right,
        public bool $topLeft,
        public bool $topRight,
        public bool $bottomLeft,
        public bool $bottomRight,
    ) {}

    public static function make(
        bool $top,
        bool $bottom,
        bool $left,
        bool $right,
        bool $topLeft,
        bool $topRight,
        bool $bottomLeft,
        bool $bottomRight,
    ): self {
        return new self(
            $top,
            $bottom,
            $left,
            $right,
            $topLeft,
            $topRight,
            $bottomLeft,
            $bottomRight,
        );
    }
}
