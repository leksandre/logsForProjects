<?php

// load Monolog library
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class DatadogLogger extends CFileLogRoute {
    
    /**
     * @var integer maximum log file size
     */
    private $_maxFileSize=2048; // in KB
    /**
     * @var integer number of log files used for rotation
     */
    private $_maxLogFiles=5;
    /**
     * @var string directory storing log files
     */
    private $_logPath;
    /**
     * @var string log file name
     */
    private $_logFile='application.log';
    /**
     * @var boolean Whether to rotate primary log by copy and truncate
     * which is more compatible with log tailers. Defaults to false.
     * @since 1.1.14
     */
    public $rotateByCopy=true;
    
    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init();
        if($this->getLogPath()===null)
            $this->setLogPath(Yii::app()->getRuntimePath());
    }
    
    /**
     * @return string directory storing log files. Defaults to application runtime path.
     */
    public function getLogPath()
    {
        return $this->_logPath;
    }
    
    /**
     * @param string $value directory for storing log files.
     * @throws CException if the path is invalid
     */
    public function setLogPath($value)
    {
        $this->_logPath=realpath($value);
        if($this->_logPath===false || !is_dir($this->_logPath) || !is_writable($this->_logPath))
            throw new CException(Yii::t('yii','CFileLogRoute.logPath "{path}" does not point to a valid directory. Make sure the directory exists and is writable by the Web server process.',
                                        array('{path}'=>$value)));
    }
    
    /**
     * @return string log file name. Defaults to 'application.log'.
     */
    public function getLogFile()
    {
        return $this->_logFile;
    }
    
    /**
     * @param string $value log file name
     */
    public function setLogFile($value)
    {
        $this->_logFile=$value;
    }
    
    /**
     * @return integer maximum log file size in kilo-bytes (KB). Defaults to 1024 (1MB).
     */
    public function getMaxFileSize()
    {
        return $this->_maxFileSize;
    }
    
    /**
     * @param integer $value maximum log file size in kilo-bytes (KB).
     */
    public function setMaxFileSize($value)
    {
        if(($this->_maxFileSize=(int)$value)<1)
            $this->_maxFileSize=1;
    }
    
    /**
     * @return integer number of files used for rotation. Defaults to 5.
     */
    public function getMaxLogFiles()
    {
        return $this->_maxLogFiles;
    }
    
    /**
     * @param integer $value number of files used for rotation.
     */
    public function setMaxLogFiles($value)
    {
        if(($this->_maxLogFiles=(int)$value)<1)
            $this->_maxLogFiles=1;
    }
    
    /**
     * Saves log messages in files.
     * @param array $logs list of log messages
     */
    protected function processLogs($logs) {
        
        // create a log channel
        
        if (!isset(Yii::app()->params['client_portal'])) {
            $jsonlog = new Logger('console'); // by default
        } else {
            $jsonlog = new Logger(Yii::app()->params['client_portal']);
        }
        
        // create a Json formatter
        $formatter = new JsonFormatter();
        $logFile = $this->getLogPath() . DIRECTORY_SEPARATOR . $this->getLogFile();
        
        
        if (@filesize($logFile) > $this->getMaxFileSize() * 1024) {  //rotate big files
            $this->rotateFiles();
        }
        
        // create a handler
        $stream = new StreamHandler($logFile, Logger::DEBUG);
        $stream->setFormatter($formatter);
        
        // bind
        $jsonlog->pushHandler($stream);
        
        foreach ($logs as $log) {
            if (is_array($log[0])) {
                $log[0] = json_encode($log[0]);
            }
            
            switch ($log[1]) {  /// This logger implements logging with different methods names
                /// and level names are not the same
                case CLogger::LEVEL_TRACE: //  this is debug level like the next
                case CLogger::LEVEL_PROFILE:
                    $jsonlog->debug($log[0], [
                        $log[2],
                        $log[3],
                        $log[4]??''  // PHP ENV VARS will be here
                    ]);
                    break;
                case CLogger::LEVEL_INFO:
                    $jsonlog->info($log[0], [
                        $log[2],
                        $log[3],
                        $log[4]??''
                    ]);
                    break;
                case CLogger::LEVEL_WARNING:
                    $jsonlog->warning($log[0], [
                        $log[2],
                        $log[3],
                        $log[4]??''
                    ]);
                    break;
                case CLogger::LEVEL_ERROR:
                    $jsonlog->error($log[0], [
                        $log[2],
                        $log[3],
                        $log[4]??''
                    ]);
                    break;
            }
            
        }
    }
    
    /**
     * Formats a log message given different fields.
     * @param string $message message content
     * @param integer $level message level
     * @param string $category message category
     * @param integer $time timestamp
     * @return string formatted message
     */
    protected function formatLogMessage($message,$level,$category,$time)
    {
        return @date('Y/m/d H:i:s',$time)." [$level] [$category] $message\n";
    }
    
    /**
     * Rotates log files.
     */
    protected function rotateFiles()
    {
        $file=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
        $max=$this->getMaxLogFiles();
        for($i=$max;$i>0;--$i)
        {
            $rotateFile=$file.'.'.$i;
            if(is_file($rotateFile))
            {
                // suppress errors because it's possible multiple processes enter into this section
                if($i===$max)
                    @unlink($rotateFile);
                else
                    @rename($rotateFile,$file.'.'.($i+1));
            }
        }
        if(is_file($file))
        {
            // suppress errors because it's possible multiple processes enter into this section
            if($this->rotateByCopy)
            {
                @copy($file,$file.'.1');
                if($fp=@fopen($file,'a'))
                {
                    @ftruncate($fp,0);
                    @fclose($fp);
                }
            }
            else
                @rename($file,$file.'.1');
        }
        // clear stat cache after moving files so later file size check is not cached
        clearstatcache();
    }
    
}
