<?php

namespace WPStaging\Backup\FileHeader;

/**
 * Type registry for entries stored in FileHeader::extraField via ExtraFieldCodec.
 *
 * Wire values are 1 byte each. They are part of the on-disk backup format and must
 * never change once shipped. New types take an unused value from the reserved range.
 *
 * Reserved range: 0x04 - 0xFE (inclusive) is reserved for future use.
 *
 * @see ExtraFieldCodec
 */
final class ExtraFieldType
{
    /**
     * AES-256-CTR initialisation vector. Value is 16 raw bytes.
     */
    const IV = 0x01;

    /**
     * HMAC-SHA256 authentication tag. Value is 32 raw bytes.
     */
    const HMAC = 0x02;

    /**
     * Multipart-tail marker for the terminal segment of a split file.
     * Value is ASCII "<size>:<crc>".
     */
    const TAIL = 0x03;

    /**
     * Parser-only sentinel returned for backups written before the TLV format
     * (backup version 2.0.0). Holds the original raw extraField bytes verbatim.
     * Never written by the encoder, never accepted by the decoder on the wire.
     */
    const LEGACY_RAW = 0xFF;

    /**
     * Fixed wire sizes for known types. Entries listed here are validated by
     * ExtraFieldCodec::encode() so a 2.1.0 producer cannot violate the format
     * contract. Variable-length types (e.g. TAIL) are deliberately absent.
     *
     * @var array<int,int>
     */
    const FIXED_WIRE_SIZES = [
        self::IV   => 16,
        self::HMAC => 32,
    ];
}
