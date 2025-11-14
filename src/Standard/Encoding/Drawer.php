<?php

declare(strict_types=1);

namespace Dllobell\Qr\Standard\Encoding;

use Dllobell\Qr\Core\BitMatrix;
use Dllobell\Qr\Core\Encoding\BitUtils;
use Dllobell\Qr\Core\QrCodeModules;
use Dllobell\Qr\Standard\StandardQrCode;
use Dllobell\Qr\Standard\StandardQrCodeEcl;
use Dllobell\Qr\Standard\StandardQrCodeModuleType;
use Exception;
use RangeException;

final class Drawer
{
    private const int PENALTY_N1 = 3;

    private const int PENALTY_N2 = 3;

    private const int PENALTY_N3 = 40;

    private const int PENALTY_N4 = 10;

    /**
     * @param array<array<bool>> $modules
     * @param array<array<StandardQrCodeModuleType>> $types
     */
    private function __construct(
        private readonly int $version,
        private readonly StandardQrCodeEcl $ecl,
        private readonly int $mask,
        private readonly int $size,
        private array $modules,
        private array $types,
    ) {}

    public static function create(
        int $version,
        StandardQrCodeEcl $ecl,
        int $mask,
    ): self {
        $size = $version * 4 + 17;

        $modules = array_fill(0, $size, array_fill(0, $size, false));
        $types = array_fill(0, $size, array_fill(0, $size, StandardQrCodeModuleType::Data));

        return new self(
            version: $version,
            ecl: $ecl,
            mask: $mask,
            size: $size,
            modules: $modules,
            types: $types,
        );
    }

    /**
     * @param array<int, int> $codewords
     */
    public function draw(array $codewords): StandardQrCode
    {
        $this->drawFunctionPatterns();

        $this->drawCodewords($codewords);

        $mask = $this->mask;
        if ($mask === -1) { // Automatically choose best mask
            $minPenalty = 1000000000;
            for ($i = 0; $i < 8; $i++) {
                $this->applyMask($i);

                $this->drawFormatBits($i);

                $penalty = $this->getPenaltyScore();

                if ($penalty < $minPenalty) {
                    $mask = $i;
                    $minPenalty = $penalty;
                }

                $this->applyMask($i); // Undoes the mask due to XOR
            }
        }

        $this->applyMask($mask); // Apply the final choice of mask
        $this->drawFormatBits($mask); // Overwrite old format bits

        $modules = BitMatrix::create($this->size, $this->size);
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->modules[$y][$x]) {
                    $modules->set($y, $x, true);
                }
            }
        }

        return StandardQrCode::create(
            version: $this->version,
            ecl: $this->ecl,
            mask: $mask,
            size: $this->size,
            modules: QrCodeModules::make($modules),
            types: $this->types,
        );
    }

    private function drawFunctionPatterns(): void
    {
        $this->drawTimingPatterns();

        $this->drawFinderPatterns();

        $this->drawAlignmentPatterns();

        // Draw configuration data
        $this->drawFormatBits(0); // Dummy mask value; overwritten later in the constructor
        $this->drawVersion();
    }

    /**
     * Draw horizontal and vertical timing patterns
     */
    private function drawTimingPatterns(): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            $this->setFunctionModule(x: 6, y: $i, isDark: $i % 2 === 0, type: StandardQrCodeModuleType::Timing);
            $this->setFunctionModule(x: $i, y: 6, isDark: $i % 2 === 0, type: StandardQrCodeModuleType::Timing);
        }
    }

    /**
     * Draw the three finder patterns (all corners except bottom right; overwrites some timing modules)
     */
    private function drawFinderPatterns(): void
    {
        $this->drawFinderPattern(3, 3);
        $this->drawFinderPattern($this->size - 4, 3);
        $this->drawFinderPattern(3, $this->size - 4);
    }

    private function drawFinderPattern(int $x, int $y): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $dist = max(abs($dx), abs($dy)); // Chebyshev/infinity norm
                $xx = $x + $dx;
                $yy = $y + $dy;

                if ($xx >= 0 && $xx < $this->size && $yy >= 0 && $yy < $this->size) {
                    $this->setFunctionModule(x: $xx, y: $yy, isDark: $dist !== 2 && $dist !== 4, type: StandardQrCodeModuleType::Finder);
                }
            }
        }
    }

    private function drawAlignmentPatterns(): void
    {
        // Draw numerous alignment patterns
        $alignPatPos = $this->getAlignmentPatternPositions();
        $numAlign = count($alignPatPos);
        for ($i = 0; $i < $numAlign; $i++) {
            for ($j = 0; $j < $numAlign; $j++) {
                // Don't draw on the three finder corners
                if (!($i === 0 && $j === 0 || $i === 0 && $j === $numAlign - 1 || $i === $numAlign - 1 && $j === 0)) {
                    $this->drawAlignmentPattern($alignPatPos[$i], $alignPatPos[$j]);
                }
            }
        }
    }

    private function drawAlignmentPattern(int $x, int $y): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $this->setFunctionModule($x + $dx, $y + $dy, max(abs($dx), abs($dy)) !== 1, type: StandardQrCodeModuleType::Alignment);
            }
        }
    }

    private function drawVersion(): void
    {
        if ($this->version < 7) {
            return;
        }

        // Calculate error correction code and pack bits
        $rem = $this->version; // version is uint6, in the range [7, 40]
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (BitUtils::unsignedRightShift($rem, 11) * 0x1F25);
        }
        $bits = $this->version << 12 | $rem; // uint18

        // Draw two copies
        for ($i = 0; $i < 18; $i++) {
            $color = $this->getBit($bits, $i);
            $a = $this->size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $this->setFunctionModule($a, $b, $color, StandardQrCodeModuleType::VersionInformation);
            $this->setFunctionModule($b, $a, $color, StandardQrCodeModuleType::VersionInformation);
        }
    }

    private function drawFormatBits(int $mask): void
    {
        // Calculate error correction code and pack bits
        $data = ($this->ecl->formatBits() << 3) | $mask; // errCorrLvl is uint2, mask is uint3
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (BitUtils::unsignedRightShift($rem, 9) * 0x537);
        }
        $bits = ($data << 10 | $rem) ^ 0x5412; // uint15

        // Draw first copy
        for ($i = 0; $i <= 5; $i++) {
            $this->setFunctionModule(8, $i, $this->getBit($bits, $i), StandardQrCodeModuleType::FormatInformation);
        }
        $this->setFunctionModule(8, 7, $this->getBit($bits, 6), StandardQrCodeModuleType::FormatInformation);
        $this->setFunctionModule(8, 8, $this->getBit($bits, 7), StandardQrCodeModuleType::FormatInformation);
        $this->setFunctionModule(7, 8, $this->getBit($bits, 8), StandardQrCodeModuleType::FormatInformation);
        for ($i = 9; $i < 15; $i++) {
            $this->setFunctionModule(14 - $i, 8, $this->getBit($bits, $i), StandardQrCodeModuleType::FormatInformation);
        }

        // Draw second copy
        for ($i = 0; $i < 8; $i++) {
            $this->setFunctionModule($this->size - 1 - $i, 8, $this->getBit($bits, $i), StandardQrCodeModuleType::FormatInformation);
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFunctionModule(8, $this->size - 15 + $i, $this->getBit($bits, $i), StandardQrCodeModuleType::FormatInformation);
        }
        $this->setFunctionModule(8, $this->size - 8, true, StandardQrCodeModuleType::DarkModule); // Always dark
    }

    /**
     * @param array<int, int> $codewords
     */
    private function drawCodewords(array $codewords): void
    {
        $i = 0; // Bit index into the data

        // Do the funny zigzag scan
        for ($right = $this->size - 1; $right >= 1; $right -= 2) { // Index of right column in each column pair
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $this->size; $vert++) { // Vertical counter
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j; // Actual x coordinate
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? $this->size - 1 - $vert : $vert; // Actual y coordinate
                    if ($this->types[$y][$x] === StandardQrCodeModuleType::Data && $i < count($codewords) * 8) {
                        $this->modules[$y][$x] = $this->getBit($codewords[$i >> 3], 0x7 - ($i & 0x7));
                        $i++;
                    }
                    // If this QR Code has any remainder bits (0 to 7), they were assigned as
                    // 0/false/light by the constructor and are left unchanged by this method
                }
            }
        }
    }

    private function setFunctionModule(int $x, int $y, bool $isDark, StandardQrCodeModuleType $type): void
    {
        $this->modules[$y][$x] = $isDark;
        $this->types[$y][$x] = $type;
    }

    private function applyMask(int $mask): void
    {
        if ($mask < 0 || $mask > 7) {
            throw new RangeException('Mask value out of range');
        }

        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                $invert = false;
                $invert = match ($mask) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => $x * $y % 2 + $x * $y % 3 === 0,
                    6 => ($x * $y % 2 + $x * $y % 3) % 2 === 0,
                    7 => (($x + $y) % 2 + $x * $y % 3) % 2 === 0,
                    default => throw new Exception('Unreachable'),
                };

                if ($this->types[$y][$x] === StandardQrCodeModuleType::Data && $invert) {
                    $this->modules[$y][$x] = !$this->modules[$y][$x];
                }
            }
        }
    }

    private function getPenaltyScore(): int
    {
        $result = 0;

        // Adjacent modules in row having same color, and finder-like patterns
        for ($y = 0; $y < $this->size; $y++) {
            $runColor = false;
            $runX = 0;
            $runHistory = [0, 0, 0, 0, 0, 0, 0];
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->modules[$y][$x] === $runColor) {
                    $runX++;

                    if ($runX === 5) {
                        $result += self::PENALTY_N1;
                    } elseif ($runX > 5) {
                        $result++;
                    }
                } else {
                    $this->finderPenaltyAddHistory($runX, $runHistory);

                    if (!$runColor) {
                        $result += $this->finderPenaltyCountPatterns($runHistory) * self::PENALTY_N3;
                    }

                    $runColor = $this->modules[$y][$x];
                    $runX = 1;
                }
            }
            $result += $this->finderPenaltyTerminateAndCount($runColor, $runX, $runHistory) * self::PENALTY_N3;
        }

        // Adjacent modules in column having same color, and finder-like patterns
        for ($x = 0; $x < $this->size; $x++) {
            $runColor = false;
            $runY = 0;
            $runHistory = [0, 0, 0, 0, 0, 0, 0];
            for ($y = 0; $y < $this->size; $y++) {
                if ($this->modules[$y][$x] === $runColor) {
                    $runY++;
                    if ($runY === 5) {
                        $result += self::PENALTY_N1;
                    } elseif ($runY > 5) {
                        $result++;
                    }
                } else {
                    $this->finderPenaltyAddHistory($runY, $runHistory);

                    if (!$runColor) {
                        $result += $this->finderPenaltyCountPatterns($runHistory) * self::PENALTY_N3;
                    }

                    $runColor = $this->modules[$y][$x];
                    $runY = 1;
                }
            }
            $result += $this->finderPenaltyTerminateAndCount($runColor, $runY, $runHistory) * self::PENALTY_N3;
        }

        // 2*2 blocks of modules having same color
        for ($y = 0; $y < $this->size - 1; $y++) {
            for ($x = 0; $x < $this->size - 1; $x++) {
                $color = $this->modules[$y][$x];
                if ($color === $this->modules[$y][$x + 1]
                      && $color === $this->modules[$y + 1][$x]
                      && $color === $this->modules[$y + 1][$x + 1]) {
                    $result += self::PENALTY_N2;
                }
            }
        }

        // Balance of dark and light modules
        $dark = 0;
        foreach ($this->modules as $row) {
            $dark = array_reduce($row, static fn (int $sum, bool $color): int => $sum + ($color ? 1 : 0), $dark);
        }
        $total = $this->size * $this->size;  // Note that size is odd, so dark/total != 1/2
        // Compute the smallest integer k >= 0 such that (45-5k)% <= dark/total <= (55+5k)%
        $k = (int) ceil(abs($dark * 20 - $total * 10) / $total) - 1;
        $result += $k * self::PENALTY_N4;

        return $result;
    }

    /**
     * Pushes the given value to the front and drops the last value. A helper function for getPenaltyScore()
     *
     * @param list<int> $runHistory
     */
    private function finderPenaltyAddHistory(int $currentRunLength, array &$runHistory): void
    {
        if ($runHistory[0] === 0) {
            $currentRunLength += $this->size; // Add light border to initial run
        }

        array_pop($runHistory);

        array_unshift($runHistory, $currentRunLength);
    }

    /**
     * Can only be called immediately after a light run is added, and
     * returns either 0, 1, or 2. A helper function for getPenaltyScore()
     *
     * @param list<int> $runHistory
     */
    private function finderPenaltyCountPatterns(array $runHistory): int
    {
        $n = $runHistory[1];

        $core = $n > 0 && $runHistory[2] === $n && $runHistory[3] === $n * 3 && $runHistory[4] === $n && $runHistory[5] === $n;

        return ($core && $runHistory[0] >= $n * 4 && $runHistory[6] >= $n ? 1 : 0)
             + ($core && $runHistory[6] >= $n * 4 && $runHistory[0] >= $n ? 1 : 0);
    }

    /**
     * Must be called at the end of a line (row or column) of modules. A helper function for getPenaltyScore()
     *
     * @param list<int> $runHistory
     */
    private function finderPenaltyTerminateAndCount(bool $currentRunColor, int $currentRunLength, array &$runHistory): int
    {
        if ($currentRunColor) {  // Terminate dark run
            $this->finderPenaltyAddHistory($currentRunLength, $runHistory);
            $currentRunLength = 0;
        }

        $currentRunLength += $this->size;  // Add light border to final run

        $this->finderPenaltyAddHistory($currentRunLength, $runHistory);

        return $this->finderPenaltyCountPatterns($runHistory);
    }

    /**
     * @return array<int>
     */
    private function getAlignmentPatternPositions(): array
    {
        if ($this->version === 1) {
            return [];
        }

        $numAlign = intdiv($this->version, 7) + 2;
        $step = intdiv($this->version * 8 + $numAlign * 3 + 5, $numAlign * 4 - 4) * 2;

        $result = [6];
        for ($pos = $this->size - 7; count($result) < $numAlign; $pos -= $step) {
            array_splice($result, 1, 0, $pos);
        }

        return $result;
    }

    private function getBit(int $bit, int $i): bool
    {
        return (BitUtils::unsignedRightShift($bit, $i) & 1) !== 0;
    }
}
