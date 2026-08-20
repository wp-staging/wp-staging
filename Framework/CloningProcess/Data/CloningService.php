<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;

abstract class CloningService
{
 
    protected $dto;




    public function setDataCloningDto(DataCloningDto $dto)
    {
        $this->dto = $dto;
    }





    public function execute()
    {
        try {
            return $this->internalExecute();
        } catch (FatalException $e) {
            $this->abortExecution($e->getMessage());
            return false;
        } catch (\RuntimeException $e) {
            $this->log($e->getMessage(), Logger::TYPE_ERROR);
 
            return true;
        }
    }





    abstract protected function internalExecute();





    protected function log($message, $type = Logger::TYPE_INFO)
    {
        $this->dto->getJob()->log("DB Data Step " . $this->dto->getStepNumber() . ": " . $message, $type);
    }





    protected function debugLog($message, $type = Logger::TYPE_INFO)
    {
        $this->dto->getJob()->debugLog($message, $type);
    }




    protected function abortExecution($message = '')
    {
        $this->log($message, Logger::TYPE_FATAL);
        $this->dto->getJob()->returnException($message);
    }





    protected function getDefineRegex($string)
    {
        return "/define\s*\(\s*['\"]" . $string . "['\"]\s*,\s*(.*)\s*\);/";
    }







    protected function getOptionTableWithoutBasePrefix($blogID)
    {
        if ($blogID === '0' || $blogID === '1') {
            return 'options';
        }

        return $blogID . '_options';
    }






    protected function isNetworkClone()
    {
        return $this->dto->getJob()->isNetworkClone();
    }
}
