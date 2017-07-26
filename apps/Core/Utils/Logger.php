<?php
namespace WPStaging\Utils;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\WPStaging;

/**
 * Class Logger
 * @package WPStaging\Utils
 */
class Logger
{
    const TYPE_ERROR    = "ERROR";

    const TYPE_CRITICAL = "CRITICAL";

    const TYPE_FATAL    = "FATAL";

    const TYPE_WARNING  = "WARNING";

    const TYPE_INFO     = "INFO";
    
    const TYPE_DEBUG     = "DEBUG";

    /**
     * Log directory (full path)
     * @var string
     */
    private $logDir;

    /**
     * Log file extension
     * @var string
     */
    private $logExtension   = "log";

    /**
     * Messages to log
     * @var array
     */
    private $messages       = array();

    /**
     * Forced filename for the log
     * @var null|string
     */
    private $fileName       = null;

    /**
     * Logger constructor.
     * @param null|string $logDir
     * @param null|string $logExtension
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

            $this->logDir = \WPStaging\WPStaging::getContentDir() . "logs" . DIRECTORY_SEPARATOR;
            
        }

        // Set log extension
        if (!empty($logExtension))
        {
            $this->logExtension = $logExtension;
        }

        // If cache directory doesn't exists, create it
        if (!is_dir($this->logDir) && !@mkdir($this->logDir, 0775, true))
        {
            throw new \Exception("Failed to create log directory!");
        }
    }

    /**
     * @param string $message
     * @param string $type
     */
    public function log($message, $type = self::TYPE_ERROR)
    {
        $this->add($message, $type);
        $this->commit();
    }

    /**
     * @param string $message
     * @param string $type
     */
    public function add($message, $type = self::TYPE_ERROR)
    {  
        $this->messages[] = array(
            "type"      => $type,
            "date"      => date("Y/m/d H:i:s"),
            "message"   => $message
        );
    }

    /**
     * @return null|string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
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
            $messageString .= "[{$message["type"]}]-[{$message["date"]}] {$message["message"]}".PHP_EOL;
        }

        if (1 > strlen($messageString))
        {
            return true;
        }

        return (@file_put_contents($this->getLogFile(), $messageString, FILE_APPEND | LOCK_EX));
    }

    /**
     * @param null|string $file
     * @return string
     */
    public function read($file = null)
    {
        return @file_get_contents($this->getLogFile($file));
    }

    /**
     * @param null|string $fileName
     * @return string
     */
    public function getLogFile($fileName = null)
    {
        // Default
        if (null === $fileName)
        {
            $fileName = (null !== $this->fileName) ? $this->fileName : date("Y_m_d");
        }

        return $this->logDir . $fileName . '.' . $this->logExtension;
    }

    /**
     * Delete a log file
     * @param string $logFileName
     * @return bool
     * @throws \Exception
     */
    public function delete($logFileName)
    {
        $logFile = $this->logDir . $logFileName . '.' . $this->logExtension;

        if (false === @unlink($logFile))
        {
            throw new \Exception("Couldn't delete cache: {$logFileName}. Full Path: {$logFile}");
        }

        return true;
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return $this->logDir;
    }

    /**
     * @return string
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
    public function getRunningTime(){
        $str_time = $this->messages[0]["date"];
        return $str_time;
    }
}