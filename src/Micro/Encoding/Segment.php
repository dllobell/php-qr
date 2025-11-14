<?php

declare(strict_types=1);

namespace Dllobell\Qr\Micro\Encoding;

use Dllobell\Qr\Core\Encoding\BitBuffer;
use Dllobell\Qr\Core\Utils;

final readonly class Segment
{
    private const string ALPHANUMERIC_CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';

    public function __construct(
        public Mode $mode,
        public BitBuffer $data,
        public int $length,
    ) {}

    public static function fromText(string $value, string $encoding): self
    {
        if (preg_match('/^[0-9]*$/', $value)) {
            return self::fromNumeric($value);
        }

        if (preg_match('/^[A-Z0-9 $%*+.\\/:-]*$/', $value)) {
            return self::fromAlphanumeric($value);
        }

        return self::fromBytes(Utils::stringToByteArray($value, $encoding));
    }

    public static function fromNumeric(string $value): self
    {
        $length = strlen($value);

        $data = new BitBuffer();
        for ($i = 0; $i < $length;) {
            // Consume up to 3 digits per iteration
            $n = min($length - $i, 3);

            $data->append((int) substr($value, $i, $n), $n * 3 + 1);

            $i += $n;
        }

        return new self(
            mode: Mode::NUMERIC,
            length: $length,
            data: $data,
        );
    }

    public static function fromAlphanumeric(string $value): self
    {
        $length = strlen($value);

        $data = new BitBuffer();

        for ($i = 0; $i <= $length - 2; $i += 2) { // Process groups of 2
            $bits = strpos(self::ALPHANUMERIC_CHARSET, $value[$i]) * 45;

            $bits += strpos(self::ALPHANUMERIC_CHARSET, $value[$i + 1]);

            $data->append($bits, 11);
        }

        if ($i < $length) { // Handle the last single character if length is odd
            $data->append(strpos(self::ALPHANUMERIC_CHARSET, $value[$i]), 6);  // @phpstan-ignore argument.type
        }

        return new self(
            mode: Mode::ALPHANUMERIC,
            length: $length,
            data: $data,
        );
    }

    /**
     * @param list<int> $bytes
     */
    public static function fromBytes(array $bytes): self
    {
        $length = count($bytes);

        $data = new BitBuffer();

        for ($i = 0; $i < $length; $i++) {
            $data->append($bytes[$i] & 0xFF, 8);
        }

        return new self(
            mode: Mode::BYTE,
            data: $data,
            length: $length,
        );
    }
}
