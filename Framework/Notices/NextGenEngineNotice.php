<?php

namespace WPStaging\Framework\Notices;










class NextGenEngineNotice extends BooleanNotice
{



    const OPTION_NAME = 'wpstg_next_gen_engine_notice';

    public function getOptionName(): string
    {
        return self::OPTION_NAME;
    }
}
