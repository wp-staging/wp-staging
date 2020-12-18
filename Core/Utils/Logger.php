<?php

/**
 * This class is not PSR-3 compliant. Currently just added the basic functionality to make the "change" easier
 * in the future. For now, there are just few things to make transition easy.
 *
 * Todo: Replace all instances of Strings with actual "string"
 */

namespace WPStaging\Core\Utils;

use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Vendor\Psr\Log\LogLevel;

/**
 * Class Logger
 * @package WPStaging\Core\Utils
 */
class Logger implements LoggerInterface
{
    const TYPE_ERROR    = "ERROR";

    const TYPE_CRITICAL = "CRITICAL";

    const TYPE_FATAL    = "FATAL";

    const TYPE_WARNING  = "WARNING";

    const TYPE_INFO     = "INFO";

    const TYPE_DEBUG     = "DEBUG";

    /**
     * Log directory (full path)
     * @var Strings
     */
    private $logDir;

    /**
     * Log file extension
     * @var Strings
     */
    private $logExtension   = "log";

    /**
     * Messages to log
     * @var array
     */
    private $messages       = [];

    /**
     * Forced filename for the log
     * @var null|Strings
     */
    private $fileName       = null;

    /**
     * Logger constructor.
     *
     * @param null|Strings $logDir
     * @param null|Strings $logExtension
     *
     * @throws \Exception
     */
    public function __construct($logDir = null, $logExtension = null)
    {
        // Set log directory
        if (!empty($logDir) && is_dir($logDir))
        {
            $this->logDir = rtrim($logDir, "/\\") . DIRECTORY_SEPARATOR;
        }
        // Set default
        else
        {

            $this->logDir = \WPStaging\Core\WPStaging::getContentDir() . "logs" . DIRECTORY_SEPARATOR;

        }

        // Set log extension
        if (!empty($logExtension))
        {
            $this->logExtension = $logExtension;
        }

        // If cache directory doesn't exists, create it
        if (!is_dir($this->logDir) && !@mkdir($this->logDir, 0755, true))
        {
            /**
             * There's no need to throw this exception, which causes a fatal,
             * as a warning is already displayed on:
             *
             * @see \WPStaging\Backend\Notices\Notices::messages
             */
            // throw new \Exception("Failed to create log directory!");
        }
    }

    public function __destruct()
    {
        $this->commit();
    }

    /**
     * @param Strings $level
     * @param Strings $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->add($message, $level);
    }

    /**
     * @param Strings $message
     * @param Strings $type
     */
    public function add($message, $type = self::TYPE_ERROR)
    {
        $this->messages[] = [
            "type"      => $type,
            "date"      => date("Y/m/d H:i:s"),
            "message"   => $message
        ];
    }

    /**
     * @return null|Strings
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param Strings $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return bool
     */
    public function commit()
    {
        if (empty($this->messages))
        {
            return true;
        }

        $messageString = '';
        foreach ($this->messages as $message)
        {
            if (is_array($message)) {
                $messageString .= "[{$message["type"]}]-[{$message["date"]}] {$message["message"]}" . PHP_EOL;
            }
        }

        if (strlen($messageString) < 1)
        {
            return true;
        }

        //return (@file_put_contents($this->getLogFile(), $messageString, FILE_APPEND | LOCK_EX));
        return (@file_put_contents($this->getLogFile(), $messageString, FILE_APPEND));
    }

    /**
     * @param null|Strings $file
     *
     * @return Strings
     */
    public function read($file = null)
    {
        return @file_get_contents($this->getLogFile($file));
    }

    /**
     * @param null|Strings $fileName
     *
     * @return Strings
     */
    public function getLogFile($fileName = null)
    {
        // Default
        if ($fileName === null)
        {
            $fileName = ($this->fileName !== null) ? $this->fileName : date("Y_m_d");
        }

        return $this->logDir . $fileName . '.' . $this->logExtension;
    }

    /**
     * Delete a log file
     *
     * @param Strings $logFileName
     *
     * @return bool
     * @throws \Exception
     */
    public function delete($logFileName)
    {
        $logFile = $this->logDir . $logFileName . '.' . $this->logExtension;

        if (@unlink($logFile) === false)
        {
            throw new \Exception("Couldn't delete cache: {$logFileName}. Full Path: {$logFile}");
        }

        return true;
    }

    /**
     * @return Strings
     */
    public function getLogDir()
    {
        return $this->logDir;
    }

    /**
     * @return Strings
     */
    public function getLogExtension()
    {
        return $this->logExtension;
    }

    /**
     * Get last element of logging data array
     * @return string
     */
    public function getLastLogMsg()
    {
        // return all messages
        if (count ($this->messages) > 1){
            return $this->messages;
        }else{
            // Return last message
            return $this->messages[]=array_pop($this->messages);
        }
    }

    /**
     * Get running time in seconds
     * @return int
     */
    public function getRunningTime()
    {
        $str_time = $this->messages[0]["date"];
        return $str_time;
    }

    /**
     * @inheritDoc
     */
    public function emergency($message, array $context = [])
    {
        $this->add($message, LogLevel::EMERGENCY);
    }

    /**
     * @inheritDoc
     */
    public function alert($message, array $context = [])
    {
        $this->add($message, LogLevel::ALERT);
    }

    /**
     * @inheritDoc
     */
    public function critical($message, array $context = [])
    {
        $this->add($message, LogLevel::CRITICAL);
    }

    /**
     * @inheritDoc
     */
    public function error($message, array $context = [])
    {
        $this->add($message, LogLevel::ERROR);
    }

    /**
     * @inheritDoc
     */
    public function warning($message, array $context = [])
    {
        $this->add($message, LogLevel::WARNING);
    }

    /**
     * @inheritDoc
     */
    public function notice($message, array $context = [])
    {
        $this->add($message, LogLevel::NOTICE);
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = [])
    {
        $this->add($message, LogLevel::INFO);
    }

    /**
     * @inheritDoc
     */
    public function debug($message, array $context = [])
    {
        $this->add($message, LogLevel::DEBUG);
    }
}
