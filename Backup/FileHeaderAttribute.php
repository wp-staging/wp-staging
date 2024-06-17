<?php

namespace WPStaging\Backup;

/**
 * FileHeader has 2 bytes field reserved for attributes. That can contain attributes about the file like compressed, encrypted etc.
 * This class contains the attributes that can be set in that attribute field.
 * Each const in this class should be in 16-bit (2 bytes) representation.
 */
class FileHeaderAttribute
{
    const COMPRESSED = 0b0000000000000001;

    const REQUIRE_PREVIOUS_PART = 0b0000000000000010;

    const REQUIRE_NEXT_PART = 0b0000000000000100;
}
