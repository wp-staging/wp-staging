<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Component\Dto;

interface InitiableDtoInterface
{
    /**
     * @return bool
     */
    public function isInit();

    /**
     * @param bool $isInit
     * @return void
     */
    public function setInit($isInit);
}