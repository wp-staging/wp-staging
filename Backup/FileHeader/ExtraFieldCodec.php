<?php

namespace WPStaging\Backup\FileHeader;

/**
 * Type-Length-Value codec for FileHeader::extraField.
 *
 * Wire format (all multi-byte integers are big-endian):
 *
 *   bytes  = [magic:2] [entry] [entry] ...
 *   magic  = 0x57 0x54   ("WT")
 *   entry  = [type:1] [length:2] [value:length]
 *
 * Decode rules:
 *   - Empty input              -> []
 *   - Input without "WT" magic -> [LEGACY_RAW => $bytes] (pre-2.1.0 raw value)
 *   - Magic present            -> entries are parsed to end of input
 *   - Type 0xFF on the wire    -> UnexpectedValueException (LEGACY_RAW is parser-only)
 *   - Truncated entry          -> UnexpectedValueException
 *   - Unknown types            -> preserved in the map and round-tripped
 *
 * Magic-byte collision: backups produced before backup version 2.1.0 always
 * wrote an empty extraField, so no legacy bytes can ever start with "WT".
 *
 * @see ExtraFieldType
 */
final class ExtraFieldCodec
{
    /**
     * Two-byte magic prefix that distinguishes a TLV-encoded extraField from a
     * legacy raw value. Chosen as ASCII "WT" (W = WP Staging, T = TLV).
     */
    const MAGIC = "\x57\x54";

    /**
     * Maximum number of bytes a single entry value may hold. Bounded by the
     * 2-byte big-endian length field.
     */
    const MAX_VALUE_LENGTH = 65535;

    /**
     * Encode a map of TLV entries into a byte string.
     *
     * The LEGACY_RAW sentinel is parser-only and is silently skipped on encode
     * so that round-tripping a legacy value through decode/encode does not
     * smuggle the sentinel back onto disk.
     *
     * @param array<int,string> $entries Type-keyed map of value bytes.
     * @return string
     * @throws \UnexpectedValueException When a type is out of range, a value
     *                                   exceeds MAX_VALUE_LENGTH, or a known
     *                                   type with a fixed wire size receives a
     *                                   value of the wrong length.
     */
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

        // No real entries (e.g. caller passed only LEGACY_RAW): emit empty rather than a bare magic.
        if ($out === self::MAGIC) {
            return '';
        }

        return $out;
    }

    /**
     * Decode a byte string into a map of TLV entries.
     *
     * @param string $bytes
     * @return array<int,string>
     * @throws \UnexpectedValueException When the input has the magic prefix but
     *                                   is malformed (truncated header, length
     *                                   overrun, duplicate type, or carries the
     *                                   parser-only LEGACY_RAW type on the wire).
     */
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
