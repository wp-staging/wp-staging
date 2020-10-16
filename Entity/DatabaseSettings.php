<?php

//TODO PHP7.x; declare(strict_types=1);
//TODO PHP7.x; type-hints and return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Entity;

use WPStaging\Framework\Entity\AbstractEntity;

class DatabaseSettings extends AbstractEntity
{
    const DEFAULT_COPY_QUERY_LIMIT = 10000;

    const DEFAULT_SEARCH_REPLACE_LIMIT = 5000;

    /** @var int */
    private $copyQueryLimit;

    /** @var int */
    private $searchReplaceLimit;

    /**
     * @return int
     */
    public function getCopyQueryLimit()
    {
        return $this->copyQueryLimit?: self::DEFAULT_COPY_QUERY_LIMIT;
    }

    /**
     * @param int $copyQueryLimit
     */
    public function setCopyQueryLimit($copyQueryLimit)
    {
        $this->copyQueryLimit = $copyQueryLimit;
    }

    /**
     * @return int
     */
    public function getSearchReplaceLimit()
    {
        return $this->searchReplaceLimit?: self::DEFAULT_SEARCH_REPLACE_LIMIT;
    }

    /**
     * @param int $searchReplaceLimit
     */
    public function setSearchReplaceLimit($searchReplaceLimit)
    {
        $this->searchReplaceLimit = $searchReplaceLimit;
    }
}