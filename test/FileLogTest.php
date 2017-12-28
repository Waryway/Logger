<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Waryway\PhpTraitsLibrary\Singleton;
use Waryway\PhpLogger\FileLog;
use Psr\Log\LogLevel;

/**
 * Class testFileLog
 * @covers FileLog
 */
class FileLogTest extends TestCase
{
    /**
     * The object under test.
     *
     * @var FileLog
     */
    private $object;

    /**
     * Sets up the fixture.
     *
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
		$this->object = FileLog::instance();
		clearstatcache();
    }
	
	public function tearDown()
    {
		if (file_exists(__DIR__.DIRECTORY_SEPARATOR.'somefile.txt')) {
			unlink(__DIR__.DIRECTORY_SEPARATOR.'somefile.txt');
		}
    }

	public function testInitialize() {
		$reflectionObject = new ReflectionObject($this->object);
		$reflectionProperty = $reflectionObject->getProperty('LogLevel');
		$reflectionProperty->setAccessible(true);
		$this->assertTrue(is_int($reflectionProperty->getValue($this->object)));
	}
	
	public function testDetermineTimestamp() {

		$reflectionObject = new ReflectionObject($this->object);
		$reflectionMethod = $reflectionObject->getMethod('DetermineTimestamp');
		$reflectionMethod->setAccessible(true);
		
		$result = $reflectionMethod->invokeArgs($this->object, [__DIR__.DIRECTORY_SEPARATOR, 'somefile','txt']);
		$this->assertEquals(date('y'), $result, 'Expected the current year as the timestamp');	
	}
	
	public function testIsLogFileTooBig() {
		$reflectionObject = new ReflectionObject($this->object);
		$reflectionMethod = $reflectionObject->getMethod('isLogFileTooBig');
		$reflectionMethod->setAccessible(true);
		
		$this->assertFalse($reflectionMethod->invokeArgs($this->object,[__DIR__.DIRECTORY_SEPARATOR.'somefile.txt']), 'Expecting an empty file, thus small enough.');
		
		$this->object->setLogFileMaxSize((float)0.0001);
		file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'somefile.txt', 'This is a really long string, it needs to be sufficient to exceed the "max" file size. This is a really long string, it needs to be sufficient to exceed the "max" file size. This is a really long string, it needs to be sufficient to exceed the "max" file size. This is a really long string, it needs to be sufficient to exceed the "max" file size.');
		
		
		$this->assertTrue($reflectionMethod->invokeArgs($this->object, [__DIR__.DIRECTORY_SEPARATOR.'somefile.txt']), 'Expecting a file to have some data, thus be too large.');
		unlink(__DIR__.DIRECTORY_SEPARATOR.'somefile.txt');
	}
	
	public function testDetermineErrorLevelNumeric() {
		$reflectionObject = new ReflectionObject($this->object);
		$reflectionMethod = $reflectionObject->getMethod('determineErrorLevelNumeric');
		$reflectionMethod->setAccessible(true);
		$actualErrorLevel = $reflectionMethod->invokeArgs($this->object, [LogLevel::WARNING]);
		$this->assertEquals(4, $actualErrorLevel, 'Expected a four to come back for a warning.');
		
		$actualErrorLevel = $reflectionMethod->invokeArgs($this->object, [LogLevel::ERROR]);
		$this->assertEquals(5, $actualErrorLevel, 'Expected a five to come back for an ERROR.');
	}
	
}
