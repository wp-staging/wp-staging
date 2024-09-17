<?php

namespace WPStaging\Framework\Utils;

/**
 * The class is responsible for converting data to/from binary and/or hex format
 * It uses pack, unpack and bin2hex functions internally
 */
class DataEncoder
{
    /**
     * The pack mode for 64-bit integer
     * P -> Little Endianness
     * @var string
     */
    const PACK_MODE_64BIT = 'P';

    /**
     * The pack mode for 32-bit integer
     * V -> Little Endianness
     * @var string
     */
    const PACK_MODE_32BIT = 'V';

    /** @var string */
    protected $packMode;

    public function __construct()
    {
        $this->packMode = PHP_INT_SIZE === 8 ? self::PACK_MODE_64BIT : self::PACK_MODE_32BIT;
    }

    /**
     * @param string $format
     * @param int[] $intArray
     * @return string
     */
    public function intArrayToHex(string $format, array $intArray): string
    {
        if (empty($format)) {
            throw new \InvalidArgumentException('Format cannot be empty');
        }

        if (empty($intArray)) {
            throw new \InvalidArgumentException('Int array cannot be empty');
        }

        $formats = str_split($format);
        if (count($formats) !== count($intArray)) {
            throw new \InvalidArgumentException('The number of characters in formats and integers in array must be equal');
        }

        if (preg_match('/[^1-8]/', $format)) {
            throw new \InvalidArgumentException('Invalid format');
        }

        $index  = 0;
        $result = '';
        foreach ($formats as $format) {
            // let try catch to re throw for index position
            try {
                $bytes = intval($format);
                if (!is_int($bytes)) {
                    throw new \InvalidArgumentException('Invalid format');
                }

                $result .= $this->intToHex($intArray[$index], $bytes);
            } catch (\InvalidArgumentException $ex) {
                throw new \InvalidArgumentException($ex->getMessage() . ' at index ' . $index);
            } catch (\Exception $ex) {
                throw new \InvalidArgumentException($ex->getMessage() . ' at index ' . $index);
            }

            $index++;
        }

        return $result;
    }

    public function intToHex(int $value, int $bytes = 8): string
    {
        if ($value < 0 && PHP_INT_SIZE === 8) {
            throw new \InvalidArgumentException('Invalid value');
        }

        if ($bytes < 1 || $bytes > 8) {
            throw new \InvalidArgumentException('Invalid number of bytes');
        }

        // convert bytes to int
        $maxInt = (2 ** ($bytes * 8)) - 1;
        if ($value > $maxInt) {
            throw new \InvalidArgumentException('Pack: Value is too large for the given number of bytes');
        }

        $pack = pack($this->packMode, $value);
        // Early bail for 64bit system or 32bit system when packing 4 or less bytes
        if ($bytes <= PHP_INT_SIZE) {
            return bin2hex(substr($pack, 0, $bytes));
        }

        $hex = bin2hex($pack);

        // This will pad the hex string with zeros if the number of bytes is less than 8 but greater than 4 for 32bit system
        return $hex . str_repeat("00", $bytes - PHP_INT_SIZE);
    }

    /**
     * @param string $format
     * @param string $hex
     *
     * @throws \InvalidArgumentException
     * @return int[]
     */
    public function hexToIntArray(string $format, string $hex): array
    {
        if (empty($format)) {
            throw new \InvalidArgumentException('Format cannot be empty');
        }

        if (preg_match('/[^1-8]/', $format)) {
            throw new \InvalidArgumentException('Invalid format: ' . $format);
        }

        if (empty($hex)) {
            throw new \InvalidArgumentException('Hex string cannot be empty');
        }

        if (strlen($hex) % 2 !== 0) {
            throw new \InvalidArgumentException('Invalid hex string: ' . $hex);
        }

        // check for invalid characters in hex
        if (preg_match('/[^0-9a-fA-F]/', $hex)) {
            throw new \InvalidArgumentException('Invalid hex string: ' . $hex);
        }

        $formats  = str_split($format);
        $index    = 0;
        $intArray = [];
        foreach ($formats as $format) {
            $bytes  = intval($format);
            $length = $bytes * 2;

            if ($index + $length > strlen($hex)) {
                throw new \InvalidArgumentException('Hex string is short according to format');
            }

            $subHex = substr($hex, $index, $length);

            $intArray[] = $this->hexToInt($subHex, $bytes);
            $index     += $length;
        }

        if ($index !== strlen($hex)) {
            throw new \InvalidArgumentException('Hex string is long according to format');
        }

        return $intArray;
    }

    public function hexToInt(string $hex, int $bytes = 8): int
    {
        if ($bytes < 1 || $bytes > 8) {
            throw new \InvalidArgumentException('Invalid number of bytes');
        }

        if (empty($hex)) {
            throw new \InvalidArgumentException('Hex string cannot be empty');
        }

        if (strlen($hex) / 2 > $bytes) {
            throw new \InvalidArgumentException('Hex string is longer than the given number of bytes');
        }

        if (strlen($hex) % 2 !== 0) {
            throw new \InvalidArgumentException('Invalid hex string: ' . $hex);
        }

        // check for invalid characters in hex
        if (preg_match('/[^0-9a-fA-F]/', $hex)) {
            throw new \InvalidArgumentException('Invalid hex string: ' . $hex);
        }

        $binary = hex2bin($hex);
        if ($bytes < PHP_INT_SIZE) {
            $binary = str_pad($binary, PHP_INT_SIZE, "\x00", STR_PAD_RIGHT);
        }

        // Early bail for 64bit system or 32bit system when unpacking 4 or less bytes
        if ($bytes <= PHP_INT_SIZE) {
            return unpack($this->packMode, $binary)[1];
        }

        // For 32bit system when unpacking more than 4 bytes, let first check if those are only zeros, if not throw exception
        $extraData = substr($binary, PHP_INT_SIZE);
        $extraZero = str_repeat("\x00", $bytes - PHP_INT_SIZE);
        if ($extraData !== $extraZero) {
            throw new \InvalidArgumentException('Unpack: Value is too large for the given number of bytes');
        }

        $dataToUnpack = substr($binary, 0, PHP_INT_SIZE);

        return unpack($this->packMode, $dataToUnpack)[1];
    }
}
