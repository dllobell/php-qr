<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rendering\Path;

final class Path
{
    /**
     * @param array<Command> $commands
     */
    private function __construct(
        public private(set) array $commands = [],
    ) {}

    public static function make(): self
    {
        return new self();
    }

    public function move(float $x, float $y, bool $absolute = true): self
    {
        $this->commands[] = new Command\Move($x, $y, $absolute);

        return $this;
    }

    public function line(float $x, float $y, bool $absolute = true): self
    {
        $this->commands[] = new Command\Line($x, $y, $absolute);

        return $this;
    }

    public function horizontal(float $x, bool $absolute = true): self
    {
        $this->commands[] = new Command\Horizontal($x, $absolute);

        return $this;
    }

    public function vertical(float $y, bool $absolute = true): self
    {
        $this->commands[] = new Command\Vertical($y, $absolute);

        return $this;
    }

    public function ellipticArc(float $rx, float $ry, float $angle, bool $largeArc, bool $sweep, float $x, float $y, bool $absolute = true): self
    {
        $this->commands[] = new Command\EllipticArc($rx, $ry, $angle, $largeArc, $sweep, $x, $y, $absolute);

        return $this;
    }

    public function close(bool $absolute = true): self
    {
        $this->commands[] = new Command\Close($absolute);

        return $this;
    }

    public function translate(float $x, float $y): self
    {
        $commands = array_map(fn (Command $command) => $command->translate($x, $y), $this->commands);

        return new self(
            $commands,
        );
    }

    public function rotate(float $degrees): self
    {
        $commands = array_map(fn (Command $command) => $command->rotate($degrees), $this->commands);

        return new self(
            $commands,
        );
    }

    public function append(self $path): self
    {
        $this->commands = array_merge($this->commands, $path->commands);

        return $this;
    }
}
