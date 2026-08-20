<?php

namespace WPStaging\Backup\FileHeader;























final class ExtraFieldCodec
{




    const MAGIC = "\x57\x54";





    const MAX_VALUE_LENGTH = 65535;















    public function encode(array $entries): string
    {
        if (empty($entries)) {
            return '';
        }

        $out = self::MAGIC;
        foreach ($entries as $type => $value) {
            if ($type === ExtraFieldType::LEGACY_RAW) {
                continue;
            }

            if (!is_int($type) || $type < 0 || $type > 0xFF) {
                throw new \UnexpectedValueException(sprintf('ExtraFieldCodec: type %s is out of the 0x00-0xFF range.', var_export($type, true)));
            }

            $length = strlen($value);
            if ($length > self::MAX_VALUE_LENGTH) {
                throw new \UnexpectedValueException(sprintf('ExtraFieldCodec: value for type 0x%02X is %d bytes, exceeding the %d-byte limit.', $type, $length, self::MAX_VALUE_LENGTH));
            }

            if (isset(ExtraFieldType::FIXED_WIRE_SIZES[$type]) && $length !== ExtraFieldType::FIXED_WIRE_SIZES[$type]) {
                throw new \UnexpectedValueException(sprintf('ExtraFieldCodec: type 0x%02X requires exactly %d bytes, got %d.', $type, ExtraFieldType::FIXED_WIRE_SIZES[$type], $length));
            }

            $out .= chr($type) . pack('n', $length) . $value;
        }

 
        if ($out === self::MAGIC) {
            return '';
        }

        return $out;
    }











    public function decode(string $bytes): array
    {
        if ($bytes === '') {
            return [];
        }

        if (substr($bytes, 0, 2) !== self::MAGIC) {
            return [ExtraFieldType::LEGACY_RAW => $bytes];
        }

        $entries = [];
        $offset  = 2;
        $total   = strlen($bytes);

        while ($offset < $total) {
            if ($total - $offset < 3) {
                throw new \UnexpectedValueException('ExtraFieldCodec: truncated entry header.');
            }

            $type   = ord($bytes[$offset]);
            $length = unpack('n', substr($bytes, $offset + 1, 2))[1];
            $offset += 3;

            if ($type === ExtraFieldType::LEGACY_RAW) {
                throw new \UnexpectedValueException(sprintf('ExtraFieldCodec: type 0x%02X is reserved as a parser-only sentinel and is not valid on the wire.', ExtraFieldType::LEGACY_RAW));
            }

            if ($total - $offset < $length) {
                throw new \UnexpectedValueException(sprintf('ExtraFieldCodec: declared length %d for type 0x%02X overruns end of input.', $length, $type));
            }

            if (array_key_exists($type, $entries)) {
                throw new \UnexpectedValueException(sprintf('ExtraFieldCodec: duplicate entry of type 0x%02X.', $type));
            }

            $entries[$type] = substr($bytes, $offset, $length);
            $offset += $length;
        }

        return $entries;
    }
}
