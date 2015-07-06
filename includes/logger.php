<?php
/**
* Simple Logger Class
*
* @author Josh Nesbitt <josh@josh-nesbitt.net>
*
* By default will write to path/to/logger/ + log/filename.log
* Author url: https://raw.githubusercontent.com/joshnesbitt/logger/master/lib/logger.php
*
**/
class wpstgLogger {
  var $file, $path, $level, $stream;
  const INFO  = 4;
  const DEBUG = 3;
  const WARN  = 2;
  const ERROR = 1;
  const FATAL = 0;
  
	function __construct($file, $level)
	{
		$this->file = $file;
		$this->level = $level;
		$this->path = str_replace('\\', '/', WPSTG_PLUGIN_DIR . "logs/$this->file");
                $this->folder = str_replace('\\', '/', WPSTG_PLUGIN_DIR . "logs");
		$this->start();
	}
	
	function info($string)
	{
	  return $this->check_level(self::INFO) ? true : $this->log($string);
	}
	
	function warn($string)
	{
	  return $this->check_level(self::WARN) ? true : $this->log($string);
	}
	
	function debug($string)
	{
	  return $this->check_level(self::DEBUG) ? true : $this->log($string);
	}
	
	function error($string)
	{
	  return $this->check_level(self::ERROR) ? true : $this->log($string);
	}
	
	function fatal($string)
	{
	  return $this->check_level(self::FATAL) ? true : $this->log($string);
	}
	
	function clear()
	{
	  $this->close();
	  $this->open("w");
	  $this->close();
	  $this->open();
	}
	
	private function check_level($level)
	{
	  return $this->level < $level;
	}
	
        /* Start logging
         */
	private function log($string)
	{
          global $wpstg_options;  
          //$enabled = isset($wpstg_options['debug_mode']) ? $wpstg_options['debug_mode'] : false;
          //if ($enabled)
	  $this->write("[". date('l jS F Y : h:i:sa') . "] ". $string . "\r\n");
          
          return false;
	}
  
	private function write($string)
	{
	  return fwrite($this->stream, $string);
	}
  
        /* Check if wp-staging debug mode is enabled
         * 
         * @return bool
         */
	private function start()
	{
          global $wpstg_options;  
          //$enabled = isset($wpstg_options['debug_mode']) ? $wpstg_options['debug_mode'] : false;
          //if ($enabled)
            return $this->open();
          
          return false;
	}
	
        /* Check if directory is writable
         * 
         * @return bool
         */
        
        function checkDir(){
            $writable = is_writable($this->folder);
            if ($writable)
                return true;
            
            return false;
        }
        
        /* Open the log file
         * 
         * @return stream
         */
	private function open($mode="a")
	{
          if ($this->checkDir())   
	  return $this->stream = fopen($this->path, $mode);
          //or die("Cannot write to file '$this->path', please ensure '$this->path' is writable.");
	}
	
	private function close()
	{
	  return fclose($this->stream);
	}
	
}

?>