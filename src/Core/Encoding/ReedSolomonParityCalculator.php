<?php

declare(strict_types=1);

namespace Dllobell\Qr\Core\Encoding;

final readonly class ReedSolomonParityCalculator
{
    /**
     * @param non-empty-array<int> $gfLog
     * @param non-empty-array<int> $gfExp
     */
    private function __construct(
        private array $gfLog,
        private array $gfExp,
    ) {}

    public static function create(): self
    {
        $gfLog = array_fill(0, 256, 0);
        $gfExp = array_fill(0, 512, 0);

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $gfExp[$i] = $x;
            $gfLog[$x] = $i;

            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }

        for ($i = 255; $i < 512; $i++) {
            $gfExp[$i] = $gfExp[$i - 255];
        }

        return new self($gfLog, $gfExp);
    }

    /**
     * @param array<int> $data
     *
     * @return array<int>
     */
    public function calculate(array $data, int $count): array
    {
        $generator = $this->computeGeneratorPolynomial($count);

        $message = array_merge($data, array_fill(0, $count, 0));

        for ($i = 0; $i < count($data); $i++) {
            $coeff = $message[$i];

            for ($j = 0; $j < count($generator); $j++) {
                $message[$i + $j] ^= $this->gfExp[$this->gfLog[$coeff] + $this->gfLog[$generator[$j]]];
            }
        }

        return array_slice($message, count($data));
    }

    /**
     * @return array<int>
     */
    private function computeGeneratorPolynomial(int $degree): array
    {
        /** @var array<int> */
        $poly = [1];

        for ($i = 0; $i < $degree; $i++) {
            $poly = $this->multiplyPolynomials($poly, [1, $this->gfExp[$i]]);
        }

        return $poly;
    }

    /**
     * @param array<int> $p1
     * @param array<int> $p2
     *
     * @return array<int>
     */
    private function multiplyPolynomials(array $p1, array $p2): array
    {
        $result = array_fill(0, count($p1) + count($p2) - 1, 0);
        foreach ($p1 as $i => $coeff1) {
            foreach ($p2 as $j => $coeff2) {
                $result[$i + $j] ^= $this->gfExp[$this->gfLog[$coeff1] + $this->gfLog[$coeff2]];
            }
        }

        return $result;
    }
}
