<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_File
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Test class for Zend_File_Transfer_Adapter_Http
 *
 * @category   Zend
 * @package    Zend_File
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_File
 */
class Zend_File_Transfer_Adapter_HttpTest extends PHPUnit\Framework\TestCase
{
    protected $adapter;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp(): void
    {
        $_FILES = array(
            'txt' => array(
                'name'     => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'test.txt',
                'type'     => 'plain/text',
                'size'     => 8,
                'tmp_name' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'test.txt',
                'error'    => 0));
        $this->adapter = new Zend_File_Transfer_Adapter_HttpTest_MockAdapter();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    public function testEmptyAdapter()
    {
        $files = $this->adapter->getFileName();
        $this->assertStringContainsString('test.txt', $files);
    }

    public function testAutoSetUploadValidator()
    {
        $validators = array(
            new Zend_Validate_File_Count(1),
            new Zend_Validate_File_Extension('jpg'),
        );
        $this->adapter->setValidators($validators);
        $test = $this->adapter->getValidator('Upload');
        $this->assertInstanceOf(Zend_Validate_File_Upload::class, $test);
    }

    /**
     */
    public function testSendingFiles()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->send();
    }

    /**
     */
    public function testFileIsSent()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->isSent();
    }

    public function testFileIsUploaded()
    {
        $this->assertTrue($this->adapter->isUploaded());
    }

    public function testFileIsNotUploaded()
    {
        $this->assertFalse($this->adapter->isUploaded('unknownFile'));
    }

    public function testFileIsNotFiltered()
    {
        $this->assertFalse($this->adapter->isFiltered('unknownFile'));
        $this->assertFalse($this->adapter->isFiltered());
    }

    public function testFileIsNotReceived()
    {
        $this->assertFalse($this->adapter->isReceived('unknownFile'));
        $this->assertFalse($this->adapter->isReceived());
    }

    public function testReceiveUnknownFile()
    {
        try {
            $this->assertFalse($this->adapter->receive('unknownFile'));
        } catch (Zend_File_Transfer_Exception $e) {
            $this->assertStringContainsString('not find', $e->getMessage());
        }
    }

    /**
     * @group ZF-12451
     */
    public function testReceiveEmptyArray()
    {
        $_SERVER['CONTENT_LENGTH'] = 10;
        $_FILES                    = array();

        $adapter = new Zend_File_Transfer_Adapter_Http();
        $this->assertFalse($adapter->receive(array()));
    }

    public function testReceiveValidatedFile()
    {
        $_FILES = array(
            'txt' => array(
                'name'     => 'unknown.txt',
                'type'     => 'plain/text',
                'size'     => 8,
                'tmp_name' => 'unknown.txt',
                'error'    => 0));
        $adapter = new Zend_File_Transfer_Adapter_HttpTest_MockAdapter();
        $this->assertFalse($adapter->receive());
    }

    public function testReceiveIgnoredFile()
    {
        $this->adapter->setOptions(array('ignoreNoFile' => true));
        $this->assertTrue($this->adapter->receive());
    }

    public function testReceiveWithRenameFilter()
    {
        $this->adapter->addFilter('Rename', array('target' => '/testdir'));
        $this->adapter->setOptions(array('ignoreNoFile' => true));
        $this->assertTrue($this->adapter->receive());
    }

    public function testReceiveWithRenameFilterButWithoutDirectory()
    {
        $this->adapter->setDestination(dirname(__FILE__));
        $this->adapter->addFilter('Rename', array('overwrite' => false));
        $this->adapter->setOptions(array('ignoreNoFile' => true));
        $this->assertTrue($this->adapter->receive());
    }

    public function testMultiFiles()
    {
        $_FILES = array(
            'txt' => array(
                'name'     => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'test.txt',
                'type'     => 'plain/text',
                'size'     => 8,
                'tmp_name' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'test.txt',
                'error'    => 0),
            'exe' => array(
                'name' => array(
                    0 => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'file1.txt',
                    1 => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'file2.txt'),
                'type' => array(
                    0 => 'plain/text',
                    1 => 'plain/text'),
                'size' => array(
                    0 => 8,
                    1 => 8),
                'tmp_name' => array(
                    0 => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'file1.txt',
                    1 => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'file2.txt'),
                'error' => array(
                    0 => 0,
                    1 => 0)));
        $adapter = new Zend_File_Transfer_Adapter_HttpTest_MockAdapter();
        $adapter->setOptions(array('ignoreNoFile' => true));
        $this->assertTrue($adapter->receive('exe'));
        $this->assertEquals(
            array('exe_0_' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'file1.txt',
                  'exe_1_' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'file2.txt'),
            $adapter->getFileName('exe', false)
        );
    }

    public function testNoUploadInProgress()
    {
        if (!(ini_get('apc.enabled') && (bool) ini_get('apc.rfc1867') && is_callable('apc_fetch')) &&
            !is_callable('uploadprogress_get_info')) {
            $this->markTestSkipped('Whether APC nor UploadExtension available');
            return;
        }

        $status = Zend_File_Transfer_Adapter_HttpTest_MockAdapter::getProgress();
        $this->assertStringContainsString('No upload in progress', $status);
    }

    public function testUploadProgressFailure()
    {
        if (!(ini_get('apc.enabled') && (bool) ini_get('apc.rfc1867') && is_callable('apc_fetch')) &&
            !is_callable('uploadprogress_get_info')) {
            $this->markTestSkipped('Whether APC nor UploadExtension available');
            return;
        }

        $_GET['progress_key'] = 'mykey';
        $status               = Zend_File_Transfer_Adapter_HttpTest_MockAdapter::getProgress();
        $this->assertEquals(array(
            'total'   => 100,
            'current' => 100,
            'rate'    => 10,
            'id'      => 'mykey',
            'done'    => false,
            'message' => '100B - 100B'), $status);

        $this->adapter->switchApcToUP();
        $status = Zend_File_Transfer_Adapter_HttpTest_MockAdapter::getProgress($status);
        $this->assertEquals(array(
            'total'          => 100,
            'bytes_total'    => 100,
            'current'        => 100,
            'bytes_uploaded' => 100,
            'rate'           => 10,
            'speed_average'  => 10,
            'cancel_upload'  => true,
            'message'        => 'The upload has been canceled',
            'done'           => true,
            'id'             => 'mykey'), $status);
    }

    public function testUploadProgressAdapter()
    {
        if (!(ini_get('apc.enabled') && (bool) ini_get('apc.rfc1867') && is_callable('apc_fetch')) &&
            !is_callable('uploadprogress_get_info')) {
            $this->markTestSkipped('Whether APC nor UploadExtension available');
            return;
        }

        $_GET['progress_key'] = 'mykey';
        $adapter              = new Zend_ProgressBar_Adapter_Console();
        $status               = array('progress' => $adapter, 'session' => 'upload');
        $status               = Zend_File_Transfer_Adapter_HttpTest_MockAdapter::getProgress($status);
        $this->assertArrayHasKey('total', $status);
        $this->assertArrayHasKey('current', $status);
        $this->assertArrayHasKey('rate', $status);
        $this->assertArrayHasKey('id', $status);
        $this->assertArrayHasKey('message', $status);
        $this->assertArrayHasKey('progress', $status);
        $this->assertTrue($status['progress'] instanceof Zend_ProgressBar);

        $this->adapter->switchApcToUP();
        $status = Zend_File_Transfer_Adapter_HttpTest_MockAdapter::getProgress($status);
        $this->assertArrayHasKey('total', $status);
        $this->assertArrayHasKey('current', $status);
        $this->assertArrayHasKey('rate', $status);
        $this->assertArrayHasKey('id', $status);
        $this->assertArrayHasKey('message', $status);
        $this->assertArrayHasKey('progress', $status);
        $this->assertTrue($status['progress'] instanceof Zend_ProgressBar);
    }

    public function testValidationOfPhpExtendsFormError()
    {
        $_SERVER['CONTENT_LENGTH'] = 10;

        $_FILES  = array();
        $adapter = new Zend_File_Transfer_Adapter_HttpTest_MockAdapter();
        $this->assertFalse($adapter->isValidParent());
        $this->assertStringContainsString('exceeds the defined ini size', current($adapter->getMessages()));
    }
}

class Zend_File_Transfer_Adapter_HttpTest_MockAdapter extends Zend_File_Transfer_Adapter_Http
{
    public function __construct()
    {
        self::$_callbackApc = array('Zend_File_Transfer_Adapter_HttpTest_MockAdapter', 'apcTest');
        parent::__construct();
    }

    public function isValid($files = null)
    {
        return true;
    }

    public function isValidParent($files = null)
    {
        return parent::isValid($files);
    }

    public static function isApcAvailable()
    {
        return true;
    }

    public static function apcTest($id)
    {
        return array('total' => 100, 'current' => 100, 'rate' => 10);
    }

    public static function uPTest($id)
    {
        return array('bytes_total' => 100, 'bytes_uploaded' => 100, 'speed_average' => 10, 'cancel_upload' => true);
    }

    public function switchApcToUP()
    {
        self::$_callbackApc            = null;
        self::$_callbackUploadProgress = array('Zend_File_Transfer_Adapter_HttpTest_MockAdapter', 'uPTest');
    }
}
