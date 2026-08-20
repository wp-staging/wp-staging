<?php

namespace WPStaging\Backup\FileHeader;











final class ExtraFieldType
{



    const IV = 0x01;




    const HMAC = 0x02;





    const TAIL = 0x03;






    const LEGACY_RAW = 0xFF;








    const FIXED_WIRE_SIZES = [
        self::IV   => 16,
        self::HMAC => 32,
    ];
}
