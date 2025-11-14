<?php

declare(strict_types=1);

namespace Dllobell\Qr\Rectangular;

use Dllobell\Qr\Core\BitMatrix;
use Dllobell\Qr\Core\Encoding\BitBuffer;
use Dllobell\Qr\Core\Encoding\ReedSolomonParityCalculator;
use Dllobell\Qr\Core\QrCodeModules;
use Dllobell\Qr\Core\Utils;
use Dllobell\Qr\Rectangular\Encoding\Fit;
use Dllobell\Qr\Rectangular\Encoding\Mode;
use Dllobell\Qr\Rectangular\Encoding\Segment;
use Dllobell\Qr\Rectangular\Encoding\Segments;
use InvalidArgumentException;

final readonly class RectangularQrCodeEncoder
{
    private const string ALPHANUMERIC_CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

    private const int MODE_INDICATOR_BITS_LENGTH = 3;

    private const array DATA_CODEWORDS_TABLE = [
        [6, 3], // 0
        [12, 7], // 1
        [20, 10], // 2
        [28, 14], // 3
        [44, 24], // 4
        [12, 7], // 5
        [21, 11], // 6
        [31, 17], // 7
        [42, 22], // 8
        [63, 33], // 9
        [7, 5], // 10
        [19, 11], // 11
        [31, 15], // 12
        [43, 23], // 13
        [57, 29], // 14
        [84, 42], // 15
        [12, 7], // 16
        [27, 13], // 17
        [38, 20], // 18
        [53, 29], // 19
        [73, 35], // 20
        [106, 54], // 21
        [33, 15], // 22
        [48, 26], // 23
        [67, 31], // 24
        [88, 48], // 25
        [127, 69], // 26
        [39, 21], // 27
        [56, 28], // 28
        [78, 38], // 29
        [100, 56], // 30
        [152, 76], // 31
    ];

    private const array DATA_ERROR_CORRECTION_CODEWORDS_TABLE = [
        [7, 10], // 0
        [9, 14], // 1
        [12, 22], // 2
        [16, 30], // 3
        [24, 44], // 4
        [9, 14], // 5
        [12, 22], // 6
        [18, 32], // 7
        [24, 44], // 8
        [36, 66], // 9
        [8, 10], // 10
        [12, 20], // 11
        [16, 32], // 12
        [24, 44], // 13
        [32, 60], // 14
        [48, 90], // 15
        [9, 14], // 16
        [14, 28], // 17
        [22, 40], // 18
        [32, 56], // 19
        [40, 78], // 20
        [60, 112], // 21
        [18, 36], // 22
        [26, 48], // 23
        [36, 72], // 24
        [48, 88], // 25
        [72, 130], // 26
        [22, 40], // 27
        [32, 60], // 28
        [44, 84], // 29
        [60, 104], // 30
        [80, 156], // 31
    ];

    private const array TOTAL_BLOCKS_TABLE = [
        [1, 1], // 0
        [1, 1], // 1
        [1, 1], // 2
        [1, 1], // 3
        [1, 2], // 4
        [1, 1], // 5
        [1, 1], // 6
        [1, 2], // 7
        [1, 2], // 8
        [2, 3], // 9
        [1, 1], // 10
        [1, 1], // 11
        [1, 2], // 12
        [1, 2], // 13
        [2, 2], // 14
        [2, 3], // 15
        [1, 1], // 16
        [1, 1], // 17
        [1, 2], // 18
        [2, 2], // 19
        [2, 3], // 20
        [3, 4], // 21
        [1, 2], // 22
        [1, 2], // 23
        [2, 3], // 24
        [2, 4], // 25
        [3, 5], // 26
        [1, 2], // 27
        [2, 2], // 28
        [2, 3], // 29
        [3, 4], // 30
        [4, 6], // 31
    ];

    private const array VERSION_DIMENSIONS_TABLE = [
        [43, 7], // 0
        [59, 7], // 1
        [77, 7], // 2
        [99, 7], // 3
        [139, 7], // 4
        [43, 9], // 5
        [59, 9], // 6
        [77, 9], // 7
        [99, 9], // 8
        [139, 9], // 9
        [27, 11], // 10
        [43, 11], // 11
        [59, 11], // 12
        [77, 11], // 13
        [99, 11], // 14
        [139, 11], // 15
        [27, 13], // 16
        [43, 13], // 17
        [59, 13], // 18
        [77, 13], // 19
        [99, 13], // 20
        [139, 13], // 21
        [43, 15], // 22
        [59, 15], // 23
        [77, 15], // 24
        [99, 15], // 25
        [139, 15], // 26
        [43, 17], // 27
        [59, 17], // 28
        [77, 17], // 29
        [99, 17], // 30
        [139, 17], // 31
    ];

    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public function encode(string $text, RectangularQrCodeEcl $ecl, Fit $fit): RectangularQrCode
    {
        if ($text === '') {
            throw new InvalidArgumentException('Text cannot be empty');
        }

        $segments = $this->buildSegments($text);

        $version = $this->resolveVersion($segments, $ecl, $fit);

        $data = $this->buildDataBits($segments, $version, $ecl);

        $codewords = $this->computeCodewords($data, $version, $ecl);

        $modules = $this->buildModules($version, $ecl, $codewords);

        return RectangularQrCode::create($version, $ecl, $modules);
    }

    private function buildSegments(string $text): Segments
    {
        $segment = $this->buildSegment($text);

        return new Segments([$segment]);
    }

    private function buildSegment(string $text): Segment
    {
        $mode = $this->resolveMode($text);

        [$bits, $length] = match ($mode) {
            Mode::NUMERIC => $this->encodeNumericSegment($text),
            Mode::ALPHANUMERIC => $this->encodeAlphanumericSegment($text),
            Mode::BYTE => $this->encodeByteSegment($text),
            Mode::KANJI => throw new InvalidArgumentException('KANJI mode is not supported yet'),
        };

        return new Segment($mode, $bits, $length);
    }

    private function resolveMode(string $text): Mode
    {
        if (preg_match('/^[0-9]*$/', $text)) {
            return Mode::NUMERIC;
        }

        if (preg_match('/^[A-Z0-9 $%*+.\\/:-]*$/', $text)) {
            return Mode::ALPHANUMERIC;
        }

        return Mode::BYTE;
    }

    /**
     * @return array{BitBuffer, int}
     */
    private function encodeNumericSegment(string $text): array
    {
        $length = strlen($text);

        $data = new BitBuffer();
        for ($i = 0; $i < $length;) {
            // Consume up to 3 digits per iteration
            $n = min($length - $i, 3);

            $data->append((int) substr($text, $i, $n), $n * 3 + 1);

            $i += $n;
        }

        return [$data, $length];
    }

    /**
     * @return array{BitBuffer, int}
     */
    private function encodeAlphanumericSegment(string $text): array
    {
        $length = strlen($text);

        $data = new BitBuffer();

        for ($i = 0; $i <= $length - 2; $i += 2) { // Process groups of 2
            $bits = strpos(self::ALPHANUMERIC_CHARSET, $text[$i]) * 45;

            $bits += strpos(self::ALPHANUMERIC_CHARSET, $text[$i + 1]);

            $data->append($bits, 11);
        }

        if ($i < $length) { // Handle the last single character if length is odd
            $data->append(strpos(self::ALPHANUMERIC_CHARSET, $text[$i]), 6);  // @phpstan-ignore argument.type
        }

        return [$data, $length];
    }

    /**
     * @return array{BitBuffer, int}
     */
    private function encodeByteSegment(string $text): array
    {
        $bytes = Utils::stringToByteArray($text, encoding: 'UTF-8');

        $length = count($bytes);

        $data = new BitBuffer();

        for ($i = 0; $i < $length; $i++) {
            $data->append($bytes[$i] & 0xFF, 8);
        }

        return [$data, $length];
    }

    private function resolveVersion(Segments $segments, RectangularQrCodeEcl $ecl, Fit $fit): RectangularQrCodeVersion
    {
        $versions = $this->findSuitableVersions($segments, $ecl);

        if ($versions === []) {
            throw new InvalidArgumentException('Data too long to encode');
        }

        $sorter = match ($fit) {
            Fit::Smallest => static function (RectangularQrCodeVersion $a, RectangularQrCodeVersion $b) {
                [$aWidth, $aHeight] = self::VERSION_DIMENSIONS_TABLE[$a->value];
                [$bWidth, $bHeight] = self::VERSION_DIMENSIONS_TABLE[$b->value];

                $aScore = $aWidth + $aHeight;
                $bScore = $bWidth + $bHeight;

                return $aScore <=> $bScore;
            },
            Fit::SmallestHeight => static function (RectangularQrCodeVersion $a, RectangularQrCodeVersion $b) {
                [, $aHeight] = self::VERSION_DIMENSIONS_TABLE[$a->value];
                [, $bHeight] = self::VERSION_DIMENSIONS_TABLE[$b->value];

                return $aHeight <=> $bHeight;
            },
            Fit::SmallestWidth => static function (RectangularQrCodeVersion $a, RectangularQrCodeVersion $b) {
                [$aWidth] = self::VERSION_DIMENSIONS_TABLE[$a->value];
                [$bWidth] = self::VERSION_DIMENSIONS_TABLE[$b->value];

                return $aWidth <=> $bWidth;
            },
            Fit::Balanced => static function (RectangularQrCodeVersion $a, RectangularQrCodeVersion $b) {
                [$aWidth, $aHeight] = self::VERSION_DIMENSIONS_TABLE[$a->value];
                [$bWidth, $bHeight] = self::VERSION_DIMENSIONS_TABLE[$b->value];

                $aScore = $aWidth - $aHeight;
                $bScore = $bWidth - $bHeight;

                return $aScore <=> $bScore;
            },
        };

        usort($versions, $sorter);

        return $versions[0];
    }

    /**
     * @return array<RectangularQrCodeVersion>
     */
    private function findSuitableVersions(Segments $segments, RectangularQrCodeEcl $ecl): array
    {
        $segment = $segments->getIterator()->current();

        $versions = [];
        for ($versionNumber = RectangularQrCodeVersion::MIN; $versionNumber <= RectangularQrCodeVersion::MAX; $versionNumber++) {
            $version = RectangularQrCodeVersion::make($versionNumber);

            $characterCountBitsLength = $segment->mode->characterCountBitsLength($version);

            $characterCountBitsWidth = 1 << $characterCountBitsLength;

            if ($segment->length >= $characterCountBitsWidth) {
                continue;
            }

            $bitsNeeded = self::MODE_INDICATOR_BITS_LENGTH + $characterCountBitsLength + $segment->bits->length;

            $bitsVersion = self::DATA_CODEWORDS_TABLE[$version->value][$ecl->value] * 8;

            if ($bitsNeeded <= $bitsVersion) {
                $versions[] = $version;
            }
        }

        return $versions;
    }

    private function buildDataBits(Segments $segments, RectangularQrCodeVersion $version, RectangularQrCodeEcl $ecl): BitBuffer
    {
        $capacity = self::DATA_CODEWORDS_TABLE[$version->value][$ecl->value] * 8;

        $data = new BitBuffer();

        foreach ($segments as $segment) {
            $this->appendSegment($data, $segment, $version);
        }

        $this->appendTerminator($data, $capacity);

        $this->appendByteAlignment($data);

        $this->appendPadding($data, $capacity);

        return $data;
    }

    private function appendSegment(BitBuffer $data, Segment $segment, RectangularQrCodeVersion $version): void
    {
        $this->appendModeIndicator($data, $segment->mode);

        $this->appendCharacterCountIndicator($data, $segment->length, $segment->mode, $version);

        $data->appendBuffer($segment->bits);
    }

    private function appendModeIndicator(BitBuffer $data, Mode $mode): void
    {
        $bits = match ($mode) {
            Mode::NUMERIC => 1,
            Mode::ALPHANUMERIC => 2,
            Mode::BYTE => 3,
            Mode::KANJI => 4,
        };

        $length = self::MODE_INDICATOR_BITS_LENGTH;

        $data->append($bits, $length);
    }

    private function appendCharacterCountIndicator(BitBuffer $data, int $length, Mode $mode, RectangularQrCodeVersion $version): void
    {
        $bits = $length;

        $length = $mode->characterCountBitsLength($version);

        $data->append($bits, $length);
    }

    private function appendTerminator(BitBuffer $data, int $capacity): void
    {
        $data->zeroPadRight(min(3, $capacity - $data->length));
    }

    private function appendByteAlignment(BitBuffer $data): void
    {
        $length = (8 - $data->length % 8) % 8;

        $data->zeroPadRight($length);
    }

    private function appendPadding(BitBuffer $data, int $capacity): void
    {
        $paddingBytes = [0b11101100, 0b00010001];

        for ($i = 0; $data->length < $capacity; $i++) {
            $data->append($paddingBytes[$i % 2], length: 8);
        }
    }

    /**
     * @return array<int>
     */
    private function computeCodewords(BitBuffer $data, RectangularQrCodeVersion $version, RectangularQrCodeEcl $ecl): array
    {
        $dataCodewords = array_fill(0, $data->length / 8, 0);
        for ($i = 0; $i < $data->length; $i++) {
            $dataCodewords[$i >> 3] |= $data->getBit($i) << (0x7 - ($i & 0x7));
        }

        $totalBlocks = self::TOTAL_BLOCKS_TABLE[$version->value][$ecl->value];

        $totalErrorCorrectionCodewords = self::DATA_ERROR_CORRECTION_CODEWORDS_TABLE[$version->value][$ecl->value];

        $parityCalculator = ReedSolomonParityCalculator::create();

        $splitLength = intdiv(count($dataCodewords), $totalBlocks);
        $totalShortBlocks = $totalBlocks - count($dataCodewords) % $totalBlocks;

        $dataBlocks = [];
        $errorCorrectionBlocks = [];
        for ($i = 0, $k = 0; $i < $totalBlocks; $i++) {
            $length = $splitLength + ($totalShortBlocks === 0 || $i < $totalShortBlocks ? 0 : 1);

            $dataBlock = array_slice($dataCodewords, $k, $length);

            $k += count($dataBlock);

            $errorCorrectionBlock = $parityCalculator->calculate($dataBlock, $totalErrorCorrectionCodewords / $totalBlocks);

            $dataBlocks[] = $dataBlock;
            $errorCorrectionBlocks[] = $errorCorrectionBlock;
        }

        $codewords = [];

        $maxBlockLength = max(array_map(count(...), $dataBlocks));
        for ($i = 0; $i < $maxBlockLength; $i++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$i])) {
                    $codewords[] = $block[$i];
                }
            }
        }

        for ($i = 0; $i < count($errorCorrectionBlocks[0]); $i++) {
            foreach ($errorCorrectionBlocks as $block) {
                $codewords[] = $block[$i];
            }
        }

        return $codewords;
    }

    /**
     * @param array<int> $codewords
     */
    private function buildModules(RectangularQrCodeVersion $version, RectangularQrCodeEcl $ecl, array $codewords): QrCodeModules
    {
        [$width, $height] = self::VERSION_DIMENSIONS_TABLE[$version->value];

        $modules = BitMatrix::create($width, $height);

        $reserved = BitMatrix::create($width, $height);

        $this->placeTimingPatterns($modules, $reserved);

        $this->placeFinderPattern($modules, $reserved);

        $this->placeSeparatorPattern($modules, $reserved);

        $this->placeSubFinderPattern($modules, $reserved);

        $this->placeCornerPatterns($modules, $reserved);

        $this->placeAlignmentPatterns($modules, $reserved);

        $this->placeFormatInformation($modules, $reserved, $version, $ecl);

        $this->placeCodewords($modules, $reserved, $codewords);

        return QrCodeModules::make($modules);
    }

    private function placeTimingPatterns(BitMatrix $modules, BitMatrix $reserved): void
    {
        for ($x = 0; $x < $modules->width; $x++) {
            $dark = ($x % 2) === 0;

            $this->placeModuleAndReserve($modules, $reserved, 0, $x, $dark);
            $this->placeModuleAndReserve($modules, $reserved, $modules->height - 1, $x, $dark);
        }

        for ($y = 0; $y < $modules->height; $y++) {
            $dark = ($y % 2) === 0;

            $this->placeModuleAndReserve($modules, $reserved, $y, 0, $dark);
            $this->placeModuleAndReserve($modules, $reserved, $y, $modules->width - 1, $dark);
        }
    }

    private function placeFinderPattern(BitMatrix $modules, BitMatrix $reserved): void
    {
        for ($y = 0; $y < 7; $y++) {
            for ($x = 0; $x < 7; $x++) {
                $dark = max(abs($x - 3), abs($y - 3)) !== 2;

                $this->placeModuleAndReserve($modules, $reserved, $y, $x, $dark);
            }
        }
    }

    private function placeSeparatorPattern(BitMatrix $modules, BitMatrix $reserved): void
    {
        for ($y = 0; $y < 7; $y++) {
            $this->placeModuleAndReserve($modules, $reserved, $y, 7, false);
        }

        if ($reserved->height > 7) {
            for ($x = 0; $x < 8; $x++) {
                $this->placeModuleAndReserve($modules, $reserved, 7, $x, false);
            }
        }
    }

    private function placeSubFinderPattern(BitMatrix $modules, BitMatrix $reserved): void
    {
        for ($y = 0; $y < 5; $y++) {
            for ($x = 0; $x < 5; $x++) {
                $dark = max(abs($x - 2), abs($y - 2)) !== 1;

                $yy = $modules->height - 5 + $y;
                $xx = $modules->width - 5 + $x;

                $this->placeModuleAndReserve($modules, $reserved, $yy, $xx, $dark);
            }
        }
    }

    private function placeCornerPatterns(BitMatrix $modules, BitMatrix $reserved): void
    {
        for ($i = 0; $i < 3; $i++) {
            // Top-right corner
            $this->placeModuleAndReserve($modules, $reserved, 0, $modules->width - 3 + $i, true);
            $this->placeModuleAndReserve($modules, $reserved, $i, $modules->width - 1, true);

            // Bottom-left corner
            $this->placeModuleAndReserve($modules, $reserved, $modules->height - 3 + $i, 0, true);
            $this->placeModuleAndReserve($modules, $reserved, $modules->height - 1, $i, true);
        }

        $this->placeModuleAndReserve($modules, $reserved, 1, $modules->width - 2, false);
        $this->placeModuleAndReserve($modules, $reserved, $modules->height - 2, 1, false);
    }

    private function placeAlignmentPatterns(BitMatrix $modules, BitMatrix $reserved): void
    {
        if ($modules->width === 27) {
            return;
        }

        $initialXs = match ($modules->width) {
            43 => [20],
            59 => [18, 38],
            77 => [24, 50],
            99 => [22, 49, 74],
            139 => [26, 54, 82, 110],
            default => throw new InvalidArgumentException('Unsupported version'),
        };

        foreach ($initialXs as $initialX) {
            for ($y = 0; $y < 3; $y++) {
                for ($x = 0; $x < 3; $x++) {
                    $dark = max(abs($x - 1), abs($y - 1)) === 1;

                    $xx = $initialX + $x;

                    // Top
                    $this->placeModuleAndReserve($modules, $reserved, $y, $xx, $dark);

                    // Bottom
                    $this->placeModuleAndReserve($modules, $reserved, $modules->height - 3 + $y, $xx, $dark);
                }
            }

            for ($y = 3; $y < $modules->height - 3; $y++) {
                $dark = $y % 2 === 0;

                $this->placeModuleAndReserve($modules, $reserved, $y, $initialX + 1, $dark);
            }
        }
    }

    private function placeFormatInformation(BitMatrix $modules, BitMatrix $reserved, RectangularQrCodeVersion $version, RectangularQrCodeEcl $ecl): void
    {
        $bits = $this->buildFormatBits($version, $ecl);

        $finderBits = $bits ^ 0b011111101010110010;
        $subFinderBits = $bits ^ 0b100000101001111011;

        for ($i = 0; $i < 15; $i++) {
            $dark = (($finderBits >> $i) & 1) === 1;

            $y = 1 + $i % 5;
            $x = 8 + intdiv($i, 5);

            $this->placeModuleAndReserve($modules, $reserved, $y, $x, $dark);

            $dark = (($subFinderBits >> $i) & 1) === 1;

            $y = $modules->height - 6 + $i % 5;
            $x = $modules->width - 8 + intdiv($i, 5);

            $this->placeModuleAndReserve($modules, $reserved, $y, $x, $dark);
        }

        for ($i = 0; $i < 3; $i++) {
            $dark = (($finderBits >> ($i + 15)) & 1) === 1;

            $y = $i + 1;
            $x = 11;

            $this->placeModuleAndReserve($modules, $reserved, $y, $x, $dark);

            $dark = (($subFinderBits >> ($i + 15)) & 1) === 1;

            $y = $modules->height - 6;
            $x = $modules->width - 5 + $i;

            $this->placeModuleAndReserve($modules, $reserved, $y, $x, $dark);
        }
    }

    private function buildFormatBits(RectangularQrCodeVersion $version, RectangularQrCodeEcl $ecl): int
    {
        $eclIndicator = match ($ecl) {
            RectangularQrCodeEcl::Medium => 0,
            RectangularQrCodeEcl::High => 1,
        };

        $data = ($eclIndicator << 5) | ($version->value);

        $data <<= 12;

        $generator = 0x1F25;

        $bch = $data;
        for ($i = 17; $i >= 12; $i--) {
            if ((($bch >> $i) & 1) === 1) {
                $bch ^= ($generator << ($i - 12));
            }
        }

        return $data | $bch;
    }

    /**
     * @param array<int> $codewords
     */
    private function placeCodewords(BitMatrix $modules, BitMatrix $reserved, array $codewords): void
    {
        $totalBits = count($codewords) * 8;

        $bitIndex = 0;

        $upwards = true;

        for ($right = $modules->width - 2; $right > 0; $right -= 2) {
            for ($vertical = 0; $vertical < $modules->height - 1; $vertical++) {
                $y = $upwards ? $modules->height - 1 - $vertical : $vertical;

                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;

                    if (!$reserved->get($y, $x)) {
                        $dark = $bitIndex < $totalBits
                            ? ($codewords[$bitIndex >> 3] >> (7 - ($bitIndex & 7)) & 1) === 1
                            : false;

                        // dump('['.$y.', '.$x.'] '.($dark ? 1 : 0));

                        $invert = (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0;

                        $modules->set($y, $x, $invert ? !$dark : $dark);

                        $bitIndex++;
                    }
                }
            }

            $upwards = !$upwards;
        }
    }

    private function placeModuleAndReserve(BitMatrix $modules, BitMatrix $reserved, int $y, int $x, bool $dark): void
    {
        $modules->set($y, $x, true);
        $modules->set($y, $x, $dark);
        $reserved->set($y, $x, true);
    }
}
