<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;

abstract class CloningService
{
    /** @var DataCloningDto $dto */
    protected $dto;

    /**
     * CloningService constructor.
     */
    public function __construct(DataCloningDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * Public function to execute the job
     * @return bool
     */
    public function execute()
    {
        try {
            return $this->internalExecute();
        } catch (FatalException $e) {
            $this->abortExecution($e->getMessage());
            return false;
        } catch (\RuntimeException $e) {
            $this->log($e->getMessage(), Logger::TYPE_ERROR);
            //If we were to return false, the task would be repeated in an endless loop. This way the execution continues
            return true;
        }
    }

    /**
     * @return bool
     * @throws \RuntimeException
     */
    abstract protected function internalExecute();

    /**
     * @param string $message
     * @param string $type
     */
    protected function log($message, $type = Logger::TYPE_INFO)
    {
        $this->dto->getJob()->log("DB Data Step " . $this->dto->getStepNumber() . ": " . $message, $type);
    }

    /**
     * @param string $message
     * @param string $type
     */
    protected function debugLog($message, $type = Logger::TYPE_INFO)
    {
        $this->dto->getJob()->debugLog($message, $type);
    }

    /**
     * @param string $message
     */
    protected function abortExecution($message = '')
    {
        $this->log($message, Logger::TYPE_FATAL);
        $this->dto->getJob()->returnException($message);
    }

    /**
     * Returns a wp-config.php define(XXX) regex for a particular string
     * @var string $string
     */
    protected function getDefineRegex($string)
    {
        return "/define\s*\(\s*['\"]" . $string . "['\"]\s*,\s*(.*)\s*\);/";
    }

    /**
     * Get Option Table Without Base Prefix
     *
     * @param string $blogID
     * @return string
     */
    protected function getOptionTableWithoutBasePrefix($blogID)
    {
        if ($blogID === '0' || $blogID === '1') {
            return 'options';
        }

        return $blogID . '_options';
    }

    /**
     * Is the current clone network?
     *
     * @return bool
     */
    protected function isNetworkClone()
    {
        return $this->dto->getJob()->isNetworkClone();
    }
}
