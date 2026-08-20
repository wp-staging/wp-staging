<?php

namespace WPStaging\Backup;






class FileHeaderAttribute
{
    const COMPRESSED = 0b0000000000000001;

    const REQUIRE_PREVIOUS_PART = 0b0000000000000010;

    const REQUIRE_NEXT_PART = 0b0000000000000100;
}
