<?php

declare(strict_types=1);

namespace Dllobell\Qr\Micro;

use Closure;
use Dllobell\Qr\Core\BitMatrix;
use Dllobell\Qr\Core\Encoding\BitBuffer;
use Dllobell\Qr\Core\Encoding\ReedSolomonParityCalculator;
use Dllobell\Qr\Core\QrCodeModules;
use Dllobell\Qr\Micro\Encoding\Mode;
use Dllobell\Qr\Micro\Encoding\Segment;
use Dllobell\Qr\Micro\Encoding\VersionConfig;
use InvalidArgumentException;
use RuntimeException;

final readonly class MicroQrCodeEncoder
{
    private const array MAXIMUM_TEXT_LENGTH_TABLE = [
        2 => [
            MicroQrCodeEcl::Low->value => [
                Mode::NUMERIC->name => 10,
                Mode::ALPHANUMERIC->name => 6,
            ],
            MicroQrCodeEcl::Medium->value => [
                Mode::NUMERIC->name => 8,
                Mode::ALPHANUMERIC->name => 5,
            ],
        ],
        3 => [
            MicroQrCodeEcl::Low->value => [
                Mode::NUMERIC->name => 23,
                Mode::ALPHANUMERIC->name => 14,
                Mode::BYTE->name => 9,
                Mode::KANJI->name => 6,
            ],
            MicroQrCodeEcl::Medium->value => [
                Mode::NUMERIC->name => 18,
                Mode::ALPHANUMERIC->name => 11,
                Mode::BYTE->name => 7,
                Mode::KANJI->name => 4,
            ],
        ],
        4 => [
            MicroQrCodeEcl::Low->value => [
                Mode::NUMERIC->name => 35,
                Mode::ALPHANUMERIC->name => 21,
                Mode::BYTE->name => 15,
                Mode::KANJI->name => 9,
            ],
            MicroQrCodeEcl::Medium->value => [
                Mode::NUMERIC->name => 30,
                Mode::ALPHANUMERIC->name => 18,
                Mode::BYTE->name => 13,
                Mode::KANJI->name => 8,
            ],
            MicroQrCodeEcl::Quartile->value => [
                Mode::NUMERIC->name => 21,
                Mode::ALPHANUMERIC->name => 13,
                Mode::BYTE->name => 9,
                Mode::KANJI->name => 5,
            ],
        ],
    ];

    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public function encode(
        string $text,
        MicroQrCodeEcl $ecl = MicroQrCodeEcl::Low,
        ?int $version = null,
        ?int $mask = null,
    ): MicroQrCode {
        if ($text === '') {
            throw new InvalidArgumentException('Text cannot be empty');
        }

        if ($mask !== null && ($mask < 0 || $mask > 3)) {
            throw new InvalidArgumentException('Mask must be between 0 and 3');
        }

        $segment = Segment::fromText($text, encoding: 'UTF-8');

        $version = $this->resolveVersion($segment, $ecl, $version);

        $config = VersionConfig::for($version, $ecl);

        $data = $this->buildDataBits($segment, $version, $config->dataBitsCapacity);

        $codewords = $this->computeCodewords($data, $config);

        $size = $version->value * 2 + 9;

        [$modules, $mask] = $this->buildModules($size, $config, $codewords);

        return MicroQrCode::create(
            version: $version,
            ecl: $ecl,
            mask: $mask,
            size: $size,
            modules: $modules,
        );
    }

    private function resolveVersion(Segment $segment, MicroQrCodeEcl $ecl, ?int $version): MicroQrCodeVersion
    {
        $minimum = $this->findMinimumVersion($segment, $ecl);

        if ($version === null) {
            return $minimum;
        }

        if ($version < $minimum->value) {
            throw new InvalidArgumentException(
                sprintf('Data too long to encode in Micro QR code version %d with ECL %s', $version, $ecl->name),
            );
        }

        return MicroQrCodeVersion::make($version);
    }

    private function findMinimumVersion(Segment $segment, MicroQrCodeEcl $ecl): MicroQrCodeVersion
    {
        for ($versionNumber = MicroQrCodeVersion::MIN; $versionNumber <= MicroQrCodeVersion::MAX; $versionNumber++) {
            $version = MicroQrCodeVersion::make($versionNumber);

            if (!$segment->mode->supports($version)) {
                continue;
            }

            $maximumLength = $this->getMaximumTextLength($version, $ecl, $segment->mode);

            if ($segment->length <= $maximumLength) {
                return $version;
            }
        }

        throw new RuntimeException('Data too long to encode in Micro QR code');
    }

    private function getMaximumTextLength(MicroQrCodeVersion $version, MicroQrCodeEcl $ecl, Mode $mode): int
    {
        assert($mode->supports($version));

        if ($version->value === 1) {
            return 5; // Version 1 only supports numeric mode with capacity of 5
        }

        $versionTable = self::MAXIMUM_TEXT_LENGTH_TABLE[$version->value];

        $eclTable = $versionTable[$ecl->value] ?? null;
        if ($eclTable === null) {
            throw new InvalidArgumentException(
                sprintf('Error correction level %s is not available for Micro QR version %d', $ecl->name, $version->value),
            );
        }

        $capacity = $eclTable[$mode->name] ?? null;
        if ($capacity === null) {
            throw new InvalidArgumentException(
                sprintf('Mode %s is not supported for Micro QR version %d with ECL %s', $mode->name, $version->value, $ecl->name),
            );
        }

        return $capacity;
    }

    private function buildDataBits(Segment $segment, MicroQrCodeVersion $version, int $capacity): BitBuffer
    {
        $data = new BitBuffer();

        $this->appendModeIndicator($data, $segment, $version);
        $this->appendCharacterCountIndicator($data, $segment, $version);
        $this->appendEncodedData($data, $segment);
        $this->appendTerminator($data, $version, $capacity);
        $this->appendByteAlignment($data, $version);
        $this->appendPadding($data, $capacity);

        return $data;
    }

    private function appendModeIndicator(BitBuffer $data, Segment $segment, MicroQrCodeVersion $version): void
    {
        if ($version->value === 1) {
            return; // No mode indicator for version 1
        }

        $bits = $segment->mode->bits();

        $length = $version->value - 1;

        $data->append($bits, $length);
    }

    private function appendCharacterCountIndicator(BitBuffer $data, Segment $segment, MicroQrCodeVersion $version): void
    {
        $bits = $segment->length;

        $length = $segment->mode->characterCountBitsLength($version);

        $data->append($bits, $length);
    }

    private function appendEncodedData(BitBuffer $data, Segment $segment): void
    {
        $data->appendBuffer($segment->data);
    }

    private function appendTerminator(BitBuffer $data, MicroQrCodeVersion $version, int $capacity): void
    {
        $length = $version->value * 2 + 1;

        $data->zeroPadRight(min($length, $capacity - $data->length));
    }

    private function appendByteAlignment(BitBuffer $data, MicroQrCodeVersion $version): void
    {
        // Codewords are 8 bits in length, except in versions M1 and M3 where the final data codeword is 4 bits in length
        $finalDataCodewordBitsLength = $version->value % 2 === 0 ? 8 : 4;

        $length = ($finalDataCodewordBitsLength - $data->length % $finalDataCodewordBitsLength) % $finalDataCodewordBitsLength;

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
    private function computeCodewords(BitBuffer $data, VersionConfig $config): array
    {
        $dataCodewords = array_fill(0, $data->length / 8, 0);
        for ($i = 0; $i < $data->length; $i++) {
            $dataCodewords[$i >> 3] |= $data->getBit($i) << (0x7 - ($i & 0x7));
        }

        $totalErrorCorrectionCodewords = $config->totalErrorCorrectionCodewords;

        $errorCorrectionCodewords = ReedSolomonParityCalculator::create()->calculate($dataCodewords, $totalErrorCorrectionCodewords);

        return array_merge($dataCodewords, $errorCorrectionCodewords);
    }

    /**
     * @param array<int> $codewords
     *
     * @return array{QrCodeModules, int}
     */
    private function buildModules(int $size, VersionConfig $config, array $codewords): array
    {
        $modules = BitMatrix::create($size, $size);

        $reserved = BitMatrix::create($size, $size);

        $this->reserveFormatInformation($reserved);

        $this->placeFinderPattern($modules, $reserved);

        $this->placeSeparatorPatterns($modules, $reserved);

        $this->placeTimingPatterns($modules, $reserved, $size);

        $this->placeCodewords($modules, $reserved, $codewords, $size);

        $mask = $this->findBestMask($modules, $reserved, $size);

        $this->applyMask($modules, $reserved, $size, $mask);

        $this->placeFormatInformation($modules, $mask, $config->symbolNumber);

        return [QrCodeModules::make($modules), $mask];
    }

    private function placeFinderPattern(BitMatrix $modules, BitMatrix $reserved): void
    {
        for ($y = 0; $y < 7; $y++) {
            for ($x = 0; $x < 7; $x++) {
                $dark = max(abs($x - 3), abs($y - 3)) !== 2;

                $modules->set($y, $x, $dark);

                $reserved->set($y, $x, true);
            }
        }
    }

    private function placeSeparatorPatterns(BitMatrix $modules, BitMatrix $reserved): void
    {
        for ($y = 0; $y < 7; $y++) {
            $modules->set($y, 7, false);
            $modules->set(7, $y, false);

            $reserved->set($y, 7, true);
            $reserved->set(7, $y, true);
        }

        $modules->set(7, 7, false);
        $reserved->set(7, 7, true);
    }

    private function placeTimingPatterns(BitMatrix $modules, BitMatrix $reserved, int $size): void
    {
        for ($i = 8; $i < $size; $i++) {
            $dark = $i % 2 === 0;

            $modules->set(0, $i, $dark);
            $modules->set($i, 0, $dark);

            $reserved->set(0, $i, true);
            $reserved->set($i, 0, true);
        }
    }

    private function reserveFormatInformation(BitMatrix $reserved): void
    {
        for ($y = 1; $y < 8; $y++) {
            $reserved->set($y, 8, true);
        }

        for ($x = 1; $x < 9; $x++) {
            $reserved->set(8, $x, true);
        }
    }

    private function placeFormatInformation(BitMatrix $modules, int $mask, int $symbolNumber): void
    {
        // $symbolNumber = match ([$version->value, $ecl]) {
        //     [2, MicroQrCodeEcl::Low] => 1,
        //     [2, MicroQrCodeEcl::Medium] => 2,
        //     [3, MicroQrCodeEcl::Low] => 3,
        //     [3, MicroQrCodeEcl::Medium] => 4,
        //     [4, MicroQrCodeEcl::Low] => 5,
        //     [4, MicroQrCodeEcl::Medium] => 6,
        //     [4, MicroQrCodeEcl::Quartile] => 7,
        //     default => 0,
        // };

        $bits = $this->formatBits($symbolNumber, $mask);

        for ($y = 1; $y < 8; $y++) {
            $dark = (($bits >> $y - 1) & 1) === 1;
            $modules->set($y, 8, $dark);
        }

        for ($x = 1; $x < 9; $x++) {
            $dark = (($bits >> $x + 6) & 1) === 1;
            $modules->set(8, 9 - $x, $dark);
        }
    }

    private function formatBits(int $symbolNumber, int $mask): int
    {
        $data = ($symbolNumber << 2) | $mask;

        $data = ($data & 0x1F) << 10;

        $generator = 0x537;

        $value = $data;
        for ($i = 14; $i >= 10; $i--) {
            if ((($value >> $i) & 1) === 1) {
                $value ^= ($generator << ($i - 10));
            }
        }

        $bch = $value & 0x3FF;

        $value = $data | $bch;

        return $value ^ 0b100010001000101;
    }

    /**
     * @param array<int> $codewords
     */
    private function placeCodewords(BitMatrix $modules, BitMatrix $reserved, array $codewords, int $size): void
    {
        $totalBits = count($codewords) * 8;

        $bitIndex = 0;

        for ($right = $size - 1; $right > 0; $right -= 2) {
            $upwards = ($right & 2) === 0;

            for ($vertical = 0; $vertical < $size; $vertical++) {
                $y = $upwards ? $size - 1 - $vertical : $vertical;

                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;

                    if (!$reserved->get($y, $x) && $bitIndex < $totalBits) {
                        $dark = ($codewords[$bitIndex >> 3] >> (7 - ($bitIndex & 7)) & 1) === 1;

                        $modules->set($y, $x, $dark);

                        $bitIndex++;
                    }
                }
            }
        }
    }

    private function findBestMask(BitMatrix $modules, BitMatrix $reserved, int $size): int
    {
        $bestScore = null;
        $bestMask = 0;
        for ($mask = 0; $mask < 4; $mask++) {
            $copy = $modules->clone();

            $this->applyMask($copy, $reserved, $size, $mask);

            $score = $this->computeMaskPenaltyScore($copy, $size);

            if ($bestScore === null || $score > $bestScore) {
                $bestScore = $score;
                $bestMask = $mask;
            }
        }

        return $bestMask;
    }

    private function applyMask(BitMatrix $modules, BitMatrix $reserved, int $size, int $mask): void
    {
        $maskCondition = $this->maskCondition($mask);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!$reserved->get($y, $x) && $maskCondition($y, $x)) {
                    $modules->invert($y, $x);
                }
            }
        }
    }

    /**
     * @return Closure(int, int): bool
     */
    private function maskCondition(int $mask): Closure
    {
        return match ($mask) {
            0 => static fn (int $y) => $y % 2 === 0,
            1 => static fn (int $y, int $x) => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
            2 => static fn (int $y, int $x) => (($y * $x) % 2 + ($y * $x) % 3) % 2 === 0,
            3 => static fn (int $y, int $x) => (($y + $x) % 2 + ($y * $x) % 3) % 2 === 0,
            default => throw new InvalidArgumentException('Mask must be between 0 and 3'),
        };
    }

    private function computeMaskPenaltyScore(BitMatrix $modules, int $size): int
    {
        // Right edge (last column)
        $sum1 = 0;
        for ($y = 1; $y < $size; $y++) {
            if ($modules->get($y, $size - 1) === true) {
                $sum1++;
            }
        }

        // Bottom edge (last row)
        $sum2 = 0;
        for ($x = 1; $x < $size; $x++) {
            if ($modules->get($size - 1, $x) === true) {
                $sum2++;
            }
        }

        return $sum1 <= $sum2 ? $sum1 * 16 + $sum2 : $sum2 * 16 + $sum1;
    }
}
