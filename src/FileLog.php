<?php
namespace Waryway\PhpLogger;

use Waryway\PhpTraitsLibrary\Singleton;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class FileLog extends AbstractLogger {
	use Singleton;
	
	/**
	 * @var string 
	 */
	private $LogLevel;
	
	/**
	 * @var string Directory path of the log file.
	 */
	private $logFilePath;
	
	/**
	 * @var string Name of the log file.
	 */
	private $logFileName;
	
	/**
	 * @var string Extension of the log file.
	 */
	private $logFileExtension;
	
	/**
	 * @var int size in megabytes
	 */
	private $logFileMaxSize = '50.00'; 
	
	/**
	 * Initialize the singleton with a few default settings.
	 */
	protected function initialize() {
		
		$this->LogLevel = $this->determineErrorLevelNumeric(LogLevel::ERROR);
		
		if (($iniErrorLog = ini_get('error_log')) === false) {
			// I don't often die while coding. But when I do, it involves logging.
			die('Unable to initialize the logger. Please define the default INI log location.');
		}
		
		$this->setLogLocation($iniErrorLog);
    }
	
	/**
	 * @param $level string 'LogLevel::NewLevel'
	 * @param $message string Thelog message
	 * @param $context [] contextual options
	 */
	public function log($level, $message, array $context = []) {
		if ($this->determineErrorLevelNumeric($level) < $this->LogLevel) {
			return null;
		}
		
		// Allow the override of the log location via context.
		if (isset($context['error_log'])) {
			$this->setLogLocation($context['error_log']);
		}
		
		if($this->isLogFileTooBig(ini_get('error_log'))) {
			$this->setLogLocation($this->logFilePath . $this->logFileName .'.'. $this->logFileExtension);
		}
		
		error_log($message);
	}
	
	/**
	 * @param $newLogLevel string 'LogLevel::NewLevel'
	 */
	public function setLogLevel($newLogLevel) {
		if (defined('LogLevel::'.strtoupper($newLogLevel))) {
			$this->LogLevel = $this->determineErrorLevelNumeric($newLogLevel);
		}
	}
	
	/**
	 * @param $logFileMaxSize float in megabytes
	 */
	public function setLogFileMaxSize(float $logFileMaxSize) {
		$this->logFileMaxSize = $logFileMaxSize;
	}
	
	/**
	 * @param $errorLog string path to use for logging.
	 */
	public function setLogLocation($errorLog) {
		$errorLogInfo = pathinfo($errorLog);
		$this->logFilePath = isset($errorLogInfo['dirname']) ? $errorLogInfo['dirname'] : '';
		$this->logFileName = $errorLogInfo['filename'];
		$this->logFileExtension = isset($errorLogInfo['extension']) ? $errorLogInfo['extension'] : '';
		$this->logFileTimestamp = $this->determineTimestamp($this->logFilePath, $this->logFileName, $this->logFileExtension);
		
		if (!file_exists($this->logFilePath)) {
			mkdir($this->logFilePath, 0700, true);
		}
		
		// Staying consistent with the INI helps debugging and prevents 'lost' fatal errors, or complicated recovery log logic.
		ini_set('error_log', $this->logFilePath . $this->logFileName .'.'. $this->logFileTimestamp .'.'. $this->logFileExtension);
	}
	
	/**
	 * Find the timestamp the logfile needs to keep from being to large.
	 * If you are writing more then 50mb a minute, you might get a large log file...
	 * 
	 * @param string $filePath
	 * @param string $fileName
	 * @param string $fileExtension
	 * @return string
	 */
	private function determineTimestamp($filePath, $fileName, $fileExtension) {		
		 // yearmonthdayhourminute
		$formatArray =  str_split('ymdHi');
				
		do {
			$dateFormat = array_shift($formatArray);
			$date = date($dateFormat);
			$fullPath = $filePath . $fileName.'.'. $date .'.'. $fileExtension;			
		} while(!empty($formatArray) && file_exists($fullPath) && (isLogFileTooBig($fullPath)));
		
		return $date;
	}
	
	/**
	 * Check if a log file is too large.
	 * @test
	 * @param string $logFile The path to the logfile.
	 * @return bool If the log file is too big, based on the max log file size.
	 */
	private function isLogFileTooBig($logFile) {
		if (!file_exists($logFile)){
			touch($logFile);
		}
		
		clearstatcache($logFile); // without this,the file size still registers as 0.
		$size = filesize($logFile);
		return (($size == 0) ? false : bccomp($size, $this->logFileMaxSize * pow(1024, 2), 4) >= 0);
	}
	
	/**
	 * Return the 'value' of the log level, used for comparison.
	 * @test
	 * @param string
	 * @return int
	 */
	private function determineErrorLevelNumeric($level) {
		$value = 0;
		switch ($level) {
			case LogLevel::EMERGENCY:
				$value++;
			case LogLevel::ALERT:
				$value++;
			case LogLevel::CRITICAL:
				$value++;
			case LogLevel::ERROR:
				$value++;
			case LogLevel::WARNING:
				$value++;
			case LogLevel::NOTICE:
				$value++;
			case LogLevel::INFO:
				$value++;
			case LogLevel::DEBUG:
				$value++;
		}
		
		return $value;
	}
}

