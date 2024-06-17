<?php

namespace WPStaging\Framework\Utils;

use InvalidArgumentException;

/**
 * The class is responsible for converting data to/from binary and/or hex format
 * It uses pack, unpack and bin2hex functions internally
 */
class DataEncoder
{
    /**
     * The pack mode for 64-bit integer
     * P -> Big Endianness
     * @var string
     */
    const PACK_MODE = 'P';

    /**
     * @param string $format
     * @param int[] $intArray
     * @return string
     */
    public function intArrayToHex(string $format, array $intArray): string
    {
        if (empty($format)) {
            throw new InvalidArgumentException('Format cannot be empty');
        }

        if (empty($intArray)) {
            throw new InvalidArgumentException('Int array cannot be empty');
        }

        $formats = str_split($format);
        if (count($formats) !== count($intArray)) {
            throw new InvalidArgumentException('The number of characters in formats and integers in array must be equal');
        }

        if (preg_match('/[^1-8]/', $format)) {
            throw new InvalidArgumentException('Invalid format');
        }

        $index  = 0;
        $result = '';
        foreach ($formats as $format) {
            // let try catch to re throw for index position
            try {
                $bytes = intval($format);
                if (!is_int($bytes)) {
                    throw new InvalidArgumentException('Invalid format');
                }

                $result .= $this->intToHex($intArray[$index], $bytes);
            } catch (InvalidArgumentException $ex) {
                throw new InvalidArgumentException($ex->getMessage() . ' at index ' . $index);
            }

            $index++;
        }

        return $result;
    }

    public function intToHex(int $value, int $bytes = 8): string
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Invalid value');
        }

        if ($bytes < 1 || $bytes > 8) {
            throw new InvalidArgumentException('Invalid number of bytes');
        }

        // convert bytes to int
        $maxInt = (2 ** ($bytes * 8)) - 1;
        if ($value > $maxInt) {
            throw new InvalidArgumentException('Value is too large for the given number of bytes');
        }

        $pack = pack(self::PACK_MODE, $value);
        return bin2hex(substr($pack, 0, $bytes));
    }

    /**
     * @param string $format
     * @param string $hex
     *
     * @throws InvalidArgumentException
     * @return int[]
     */
    public function hexToIntArray(string $format, string $hex): array
    {
        if (empty($format)) {
            throw new InvalidArgumentException('Format cannot be empty');
        }

        if (preg_match('/[^1-8]/', $format)) {
            throw new InvalidArgumentException('Invalid format');
        }

        if (empty($hex)) {
            throw new InvalidArgumentException('Hex string cannot be empty');
        }

        if (strlen($hex) % 2 !== 0) {
            throw new InvalidArgumentException('Invalid hex string');
        }

        // check for invalid characters in hex
        if (preg_match('/[^0-9a-fA-F]/', $hex)) {
            throw new InvalidArgumentException('Invalid hex string');
        }

        $formats  = str_split($format);
        $index    = 0;
        $intArray = [];
        foreach ($formats as $format) {
            $bytes  = intval($format);
            $length = $bytes * 2;

            if ($index + $length > strlen($hex)) {
                throw new InvalidArgumentException('Hex string is short according to format');
            }

            $subHex = substr($hex, $index, $length);

            $intArray[] = $this->hexToInt($subHex, $bytes);
            $index     += $length;
        }

        if ($index !== strlen($hex)) {
            throw new InvalidArgumentException('Hex string is long according to format');
        }

        return $intArray;
    }

    public function hexToInt(string $hex, int $bytes = 8): int
    {
        if ($bytes < 1 || $bytes > 8) {
            throw new InvalidArgumentException('Invalid number of bytes');
        }

        if (empty($hex)) {
            throw new InvalidArgumentException('Hex string cannot be empty');
        }

        if (strlen($hex) / 2 > $bytes) {
            throw new InvalidArgumentException('Hex string is longer than the given number of bytes');
        }

        if (strlen($hex) % 2 !== 0) {
            throw new InvalidArgumentException('Invalid hex string');
        }

        // check for invalid characters in hex
        if (preg_match('/[^0-9a-fA-F]/', $hex)) {
            throw new InvalidArgumentException('Invalid hex string');
        }

        $binary = hex2bin($hex);
        if ($bytes !== 8) {
            $binary = str_pad($binary, 8, "\x00", STR_PAD_RIGHT);
        }

        return unpack(self::PACK_MODE, $binary)[1];
    }
}
