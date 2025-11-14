<?php

declare(strict_types=1);

namespace Dllobell\Qr\Standard;

use Dllobell\Qr\Core\Encoding\BitBuffer;
use Dllobell\Qr\Core\Encoding\BitUtils;
use Dllobell\Qr\Standard\Encoding\Drawer;
use Dllobell\Qr\Standard\Encoding\Segments;
use InvalidArgumentException;
use RangeException;

final readonly class StandardQrEncoder
{
    private const int MIN_VERSION = 1;

    private const int MAX_VERSION = 40;

    private const array EC_CODEWORDS_PER_BLOCK = [
        // Version: (note that index 0 is for padding, and is set to an illegal value)
        // 0,  1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40    Error correction level
        [-1, 7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30, 22, 24, 28, 30, 28, 28, 28, 28, 30, 30, 26, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],  // Low
        [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28],  // Medium
        [-1, 13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20, 30, 24, 28, 28, 26, 30, 28, 30, 30, 30, 30, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],  // Quartile
        [-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28, 30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],  // High
    ];

    private const array NUM_ERROR_CORRECTION_BLOCKS = [
        // Version: (note that index 0 is for padding, and is set to an illegal value)
        // 0, 1, 2, 3, 4, 5, 6, 7, 8, 9,10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40    Error correction level
        [-1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 4, 4, 4, 4, 4, 6, 6, 6, 6, 7, 8, 8, 9, 9, 10, 12, 12, 12, 13, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 24, 25],  // Low
        [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49],  // Medium
        [-1, 1, 1, 2, 2, 4, 4, 6, 6, 8, 8, 8, 10, 12, 16, 12, 17, 16, 18, 21, 20, 23, 23, 25, 27, 29, 34, 34, 35, 38, 40, 43, 45, 48, 51, 53, 56, 59, 62, 65, 68],  // Quartile
        [-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25, 25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81],  // High
    ];

    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public function encode(
        string $text,
        StandardQrCodeEcl $ecl = StandardQrCodeEcl::Low,
        int $minVersion = self::MIN_VERSION,
        int $maxVersion = self::MAX_VERSION,
        int $mask = -1,
        bool $optimize = false,
    ): StandardQrCode {
        if ($minVersion < self::MIN_VERSION) {
            throw new InvalidArgumentException('Minimum version must be at least '.self::MIN_VERSION);
        }

        if ($maxVersion > self::MAX_VERSION) {
            throw new InvalidArgumentException('Maximum version must not exceed '.self::MAX_VERSION);
        }

        if ($minVersion > $maxVersion) {
            throw new InvalidArgumentException('Minimum version must not be greater than maximum version');
        }

        if ($mask < -1 || $mask > 7) {
            throw new InvalidArgumentException('Mask must be between -1 and 7');
        }

        $segments = Segments::fromText($text, 'UTF-8');

        return $this->encodeSegments($segments, $ecl, $minVersion, $maxVersion, $mask, $optimize);
    }

    private function encodeSegments(Segments $segments, StandardQrCodeEcl $ecl, int $minVersion, int $maxVersion, int $mask, bool $optimize): StandardQrCode
    {
        // Find the minimal version number to use
        for ($version = $minVersion;; $version++) {
            $dataCapacityBits = $this->getTotalCodewordsBits($version, $ecl);  // Number of data bits available

            $dataUsedBits = $segments->totalBits($version); // Number of data bits needed

            if ($dataUsedBits !== -1 && $dataUsedBits <= $dataCapacityBits) {
                break; // This version number is found to be suitable
            }

            if ($version >= $maxVersion) {  // All versions in the range could not fit the given data
                $message = 'Segment too long';

                if ($dataUsedBits !== -1) {
                    $message = sprintf('Data length = %d bits, Max capacity = %d bits', $dataUsedBits, $dataCapacityBits);
                }

                throw new RangeException($message);
            }
        }

        if ($optimize) {
            foreach (StandardQrCodeEcl::cases() as $newEcl) {
                if ($dataUsedBits <= $this->getTotalCodewordsBits($version, $newEcl)) {
                    $ecl = $newEcl;
                }
            }
        }

        $data = new BitBuffer();
        foreach ($segments as $segment) {
            // Add the mode indicator
            $data->append($segment->mode->bits(), 4);

            // Add the character count indicator
            $data->append($segment->length, $segment->mode->totalCharacterCountBits($version));

            // Add the encoded data
            $data->appendBuffer($segment->data);
        }

        $dataCapacityBits = $this->getTotalCodewordsBits($version, $ecl);

        // Add terminator (if necessary)
        $data->zeroPadRight(min(4, $dataCapacityBits - $data->length));

        // Add padding (if necessary)
        $data->zeroPadRight((8 - $data->length % 8) % 8);

        // Add the 11101100 and 00010001 bits interchanged (if necessary) until maximum capacity is reached
        for ($padByte = 0xEC; $data->length < $dataCapacityBits; $padByte ^= 0xFD) {
            $data->append($padByte, 8);
        }

        // Convert codewords from binary to decimal
        $codewords = array_fill(0, (int) ($data->length / 8), 0);
        for ($i = 0; $i < $data->length; $i++) {
            $codewords[$i >> 3] |= $data->getBit($i) << (0x7 - ($i & 0x7));
        }

        assert(count($codewords) === $this->getTotalCodewords($version, $ecl));

        $codewords = $this->addEccAndInterleave($codewords, $version, $ecl);

        return Drawer::create($version, $ecl, $mask)->draw($codewords);
    }

    /**
     * @param array<int, int> $codewords
     *
     * @return list<int>
     */
    private function addEccAndInterleave(array $codewords, int $version, StandardQrCodeEcl $ecl): array
    {
        // Calculate parameter numbers
        $numBlocks = self::NUM_ERROR_CORRECTION_BLOCKS[$ecl->value][$version];
        $ecBlockLength = self::EC_CODEWORDS_PER_BLOCK[$ecl->value][$version];

        $rawCodewords = $this->getTotalRawCodewords($version);
        $numShortBlocks = $numBlocks - $rawCodewords % $numBlocks;
        $shortBlockLen = intdiv($rawCodewords, $numBlocks);

        $divisor = $this->reedSolomonComputeDivisor($ecBlockLength);

        // Split data into blocks and append ECC to each block
        $blocks = [];
        for ($i = 0, $k = 0; $i < $numBlocks; $i++) {
            $data = array_slice($codewords, $k, $shortBlockLen - $ecBlockLength + ($i < $numShortBlocks ? 0 : 1));

            $k += count($data);

            $ecc = $this->reedSolomonComputeRemainder($data, $divisor);

            $blocks[] = array_merge($data, $ecc);
        }

        // Interleave the bytes from every block into a single sequence
        $result = [];
        for ($i = 0; $i < count($blocks[0]); $i++) {
            foreach ($blocks as $block) {
                $result[] = $block[$i];
            }
        }

        return $result;
    }

    /**
     * @return array<int>
     */
    private function reedSolomonComputeDivisor(int $degree): array
    {
        assert($degree >= 1 && $degree <= 255);

        // Polynomial coefficients are stored from highest to lowest power, excluding the leading term which is always 1.
        // For example the polynomial x^3 + 255x^2 + 8x + 93 is stored as the int array [255, 8, 93].
        $result = array_fill(0, $degree - 1, 0);

        $result[] = 1; // Start off with the monomial x^0

        // Compute the product polynomial (x - r^0) * (x - r^1) * (x - r^2) * ... * (x - r^{degree-1}),
        // and drop the highest monomial term which is always 1x^degree.
        // Note that r = 0x02, which is a generator element of this field GF(2^8/0x11D).
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            // Multiply the current product by (x - r^i)
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = $this->reedSolomonMultiply($result[$j], $root);

                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }

            $root = $this->reedSolomonMultiply($root, 0x02);
        }

        return $result;
    }

    /**
     * @param array<int> $data
     * @param array<int> $divisor
     *
     * @return array<int>
     */
    private function reedSolomonComputeRemainder(array $data, array $divisor): array
    {
        $result = array_map(static fn (): int => 0, $divisor);

        foreach ($data as $b) { // Polynomial division
            $factor = $b ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coefficient) {
                $result[$i] ^= $this->reedSolomonMultiply($coefficient, $factor);
            }
        }

        return $result;
    }

    private function reedSolomonMultiply(int $x, int $y): int
    {
        assert($x >> 8 === 0 && $y >> 8 === 0);

        // Russian peasant multiplication
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (BitUtils::unsignedRightShift($z, 7) * 0x11D);
            $z ^= ((BitUtils::unsignedRightShift($y, $i) & 1) * $x);
        }

        return $z;
    }

    private function getTotalCodewordsBits(int $version, StandardQrCodeEcl $ecl): int
    {
        return $this->getTotalCodewords($version, $ecl) * 8;
    }

    private function getTotalCodewords(int $version, StandardQrCodeEcl $ecl): int
    {
        $rawCapacity = $this->getTotalRawCodewords($version);

        $eclCapacity = self::EC_CODEWORDS_PER_BLOCK[$ecl->value][$version] * self::NUM_ERROR_CORRECTION_BLOCKS[$ecl->value][$version];

        return $rawCapacity - $eclCapacity;
    }

    private function getTotalRawCodewords(int $version): int
    {
        $size = $version * 4 + 17;

        $result = $size * $size;        // Number of modules in the whole QR Code square
        $result -= 8 * 8 * 3;           // Subtract the three finders with separators
        $result -= ($size - 16) * 2;    // Subtract the timing patterns (excluding finders)
        $result -= 15 * 2 + 1;          // Subtract the format information and dark module

        // The five lines above are equivalent to: $result = (16 * $version + 128) * $version + 64;
        if ($version > 1) {
            $numAlign = intdiv($version, 7) + 2;
            $result -= ($numAlign - 1) * ($numAlign - 1) * 25;  // Subtract alignment patterns not overlapping with timing patterns
            $result -= ($numAlign - 2) * 2 * 20;  // Subtract alignment patterns that overlap with timing patterns

            // The two lines above are equivalent to: $result -= (25 * $numAlign - 10) * $numAlign - 55;
            if ($version >= 7) {
                $result -= 6 * 3 * 2;
            }  // Subtract version information
        }

        return intdiv((int) $result, 8);
    }
}
