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
 * Test class for Zend_File_Transfer_Adapter_Abstract
 *
 * @category   Zend
 * @package    Zend_File
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_File
 */
class Zend_File_Transfer_Adapter_AbstractTest extends PHPUnit\Framework\TestCase
{
    /**
     * @var Zend_File_Transfer_Adapter_AbstractTest_MockAdapter
     */
    protected $adapter;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->adapter = new Zend_File_Transfer_Adapter_AbstractTest_MockAdapter();
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

    /**
     */
    public function testAdapterShouldThrowExceptionWhenRetrievingPluginLoaderOfInvalidType()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->getPluginLoader('bogus');
    }

    public function testAdapterShouldHavePluginLoaderForValidators()
    {
        $loader = $this->adapter->getPluginLoader('validate');
        $this->assertInstanceOf(Zend_Loader_PluginLoader::class, $loader);
    }

    public function testAdapterShouldAllowAddingCustomPluginLoader()
    {
        $loader = new Zend_Loader_PluginLoader();
        $this->adapter->setPluginLoader($loader, 'filter');
        $this->assertSame($loader, $this->adapter->getPluginLoader('filter'));
    }

    /**
     */
    public function testAddingInvalidPluginLoaderTypeToAdapterShouldRaiseException()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $loader = new Zend_Loader_PluginLoader();
        $this->adapter->setPluginLoader($loader, 'bogus');
    }

    public function testAdapterShouldProxyAddingPluginLoaderPrefixPath()
    {
        $loader = $this->adapter->getPluginLoader('validate');
        $this->adapter->addPrefixPath('Foo_Valid', 'Foo/Valid/', 'validate');
        $paths = $loader->getPaths('Foo_Valid');
        $this->assertIsArray($paths);
    }

    public function testPassingNoTypeWhenAddingPrefixPathToAdapterShouldGeneratePathsForAllTypes()
    {
        $this->adapter->addPrefixPath('Foo', 'Foo');
        $validateLoader = $this->adapter->getPluginLoader('validate');
        $filterLoader   = $this->adapter->getPluginLoader('filter');
        $paths          = $validateLoader->getPaths('Foo_Validate');
        $this->assertIsArray($paths);
        $paths = $filterLoader->getPaths('Foo_Filter');
        $this->assertIsArray($paths);
    }

    /**
     */
    public function testPassingInvalidTypeWhenAddingPrefixPathToAdapterShouldThrowException()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->addPrefixPath('Foo', 'Foo', 'bogus');
    }

    public function testAdapterShouldProxyAddingMultiplePluginLoaderPrefixPaths()
    {
        $validatorLoader = $this->adapter->getPluginLoader('validate');
        $filterLoader    = $this->adapter->getPluginLoader('filter');
        $this->adapter->addPrefixPaths(array(
            'validate' => array('prefix' => 'Foo_Valid', 'path' => 'Foo/Valid/'),
            'filter'   => array(
                'Foo_Filter' => 'Foo/Filter/',
                'Baz_Filter' => array(
                    'Baz/Filter/',
                    'My/Baz/Filter/',
                ),
            ),
            array('type' => 'filter', 'prefix' => 'Bar_Filter', 'path' => 'Bar/Filter/'),
        ));
        $paths = $validatorLoader->getPaths('Foo_Valid');
        $this->assertIsArray($paths);
        $paths = $filterLoader->getPaths('Foo_Filter');
        $this->assertIsArray($paths);
        $paths = $filterLoader->getPaths('Bar_Filter');
        $this->assertIsArray($paths);
        $paths = $filterLoader->getPaths('Baz_Filter');
        $this->assertIsArray($paths);
        $this->assertCount(2, $paths);
    }

    public function testValidatorPluginLoaderShouldRegisterPathsForBaseAndFileValidatorsByDefault()
    {
        $loader = $this->adapter->getPluginLoader('validate');
        $paths  = $loader->getPaths('Zend_Validate');
        $this->assertIsArray($paths);
        $paths = $loader->getPaths('Zend_Validate_File');
        $this->assertIsArray($paths);
    }

    public function testAdapterShouldAllowAddingValidatorInstance()
    {
        $validator = new Zend_Validate_File_Count(array('min' => 1, 'max' => 1));
        $this->adapter->addValidator($validator);
        $test = $this->adapter->getValidator('Zend_Validate_File_Count');
        $this->assertSame($validator, $test);
    }

    public function testAdapterShouldAllowAddingValidatorViaPluginLoader()
    {
        $this->adapter->addValidator('Count', false, array('min' => 1, 'max' => 1));
        $test = $this->adapter->getValidator('Count');
        $this->assertInstanceOf(Zend_Validate_File_Count::class, $test);
    }

    /**
     */
    public function testAdapterhShouldRaiseExceptionWhenAddingInvalidValidatorType()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->addValidator(new Zend_Filter_BaseName);
    }

    public function testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader()
    {
        $validators = array(
            'count'           => array('min' => 1, 'max' => 1),
            'Exists'          => 'C:\temp',
            array('validator' => 'Upload', 'options' => array(realpath(__FILE__))),
            new Zend_Validate_File_Extension('jpg'),
        );
        $this->adapter->addValidators($validators);
        $test = $this->adapter->getValidators();
        $this->assertIsArray($test);
        $this->assertCount(4, $test, var_export($test, 1));
        $count = array_shift($test);
        $this->assertInstanceOf(Zend_Validate_File_Count::class, $count);
        $exists = array_shift($test);
        $this->assertInstanceOf(Zend_Validate_File_Exists::class, $exists);
        $size = array_shift($test);
        $this->assertInstanceOf(Zend_Validate_File_Upload::class, $size);
        $ext = array_shift($test);
        $this->assertInstanceOf(Zend_Validate_File_Extension::class, $ext);
        $orig = array_pop($validators);
        $this->assertSame($orig, $ext);
    }

    public function testGetValidatorShouldReturnNullWhenNoMatchingIdentifierExists()
    {
        $this->assertNull($this->adapter->getValidator('Alpha'));
    }

    public function testAdapterShouldAllowPullingValidatorsByFile()
    {
        $this->adapter->addValidator('Alpha', false, false, 'foo');
        $validators = $this->adapter->getValidators('foo');
        $this->assertCount(1, $validators);
        $validator = array_shift($validators);
        $this->assertInstanceOf(Zend_Validate_Alpha::class, $validator);
    }

    public function testCallingSetValidatorsOnAdapterShouldOverwriteExistingValidators()
    {
        $this->testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader();
        $validators = array(
            new Zend_Validate_File_Count(1),
            new Zend_Validate_File_Extension('jpg'),
        );
        $this->adapter->setValidators($validators);
        $test = $this->adapter->getValidators();
        $this->assertSame($validators, array_values($test));
    }

    public function testAdapterShouldAllowRetrievingValidatorInstancesByClassName()
    {
        $this->testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader();
        $ext = $this->adapter->getValidator('Zend_Validate_File_Extension');
        $this->assertInstanceOf(Zend_Validate_File_Extension::class, $ext);
    }

    public function testAdapterShouldAllowRetrievingValidatorInstancesByPluginName()
    {
        $this->testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader();
        $count = $this->adapter->getValidator('Count');
        $this->assertInstanceOf(Zend_Validate_File_Count::class, $count);
    }

    public function testAdapterShouldAllowRetrievingAllValidatorsAtOnce()
    {
        $this->testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader();
        $validators = $this->adapter->getValidators();
        $this->assertIsArray($validators);
        $this->assertCount(4, $validators);
        foreach ($validators as $validator) {
            $this->assertInstanceOf(Zend_Validate_Interface::class, $validator);
        }
    }

    public function testAdapterShouldAllowRemovingValidatorInstancesByClassName()
    {
        $this->testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader();
        $this->assertTrue($this->adapter->hasValidator('Zend_Validate_File_Extension'));
        $this->adapter->removeValidator('Zend_Validate_File_Extension');
        $this->assertFalse($this->adapter->hasValidator('Zend_Validate_File_Extension'));
    }

    public function testAdapterShouldAllowRemovingValidatorInstancesByPluginName()
    {
        $this->testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader();
        $this->assertTrue($this->adapter->hasValidator('Count'));
        $this->adapter->removeValidator('Count');
        $this->assertFalse($this->adapter->hasValidator('Count'));
    }

    public function testRemovingNonexistentValidatorShouldDoNothing()
    {
        $this->testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader();
        $validators = $this->adapter->getValidators();
        $this->assertFalse($this->adapter->hasValidator('Alpha'));
        $this->adapter->removeValidator('Alpha');
        $this->assertFalse($this->adapter->hasValidator('Alpha'));
        $test = $this->adapter->getValidators();
        $this->assertSame($validators, $test);
    }

    public function testAdapterShouldAllowRemovingAllValidatorsAtOnce()
    {
        $this->testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoader();
        $this->adapter->clearValidators();
        $validators = $this->adapter->getValidators();
        $this->assertIsArray($validators);
        $this->assertCount(0, $validators);
    }

    public function testValidationShouldReturnTrueForValidTransfer()
    {
        $this->adapter->addValidator('Count', false, array(1, 3), 'foo');
        $this->assertTrue($this->adapter->isValid('foo'));
    }

    public function testValidationShouldReturnTrueForValidTransferOfMultipleFiles()
    {
        $this->assertTrue($this->adapter->isValid(null));
    }

    public function testValidationShouldReturnFalseForInvalidTransfer()
    {
        $this->adapter->addValidator('Extension', false, 'png', 'foo');
        $this->assertFalse($this->adapter->isValid('foo'));
    }

    public function testValidationShouldThrowExceptionForNonexistentFile()
    {
        $this->assertFalse($this->adapter->isValid('bogus'));
    }

    public function testErrorMessagesShouldBeEmptyByDefault()
    {
        $messages = $this->adapter->getMessages();
        $this->assertIsArray($messages);
        $this->assertCount(0, $messages);
    }

    public function testErrorMessagesShouldBePopulatedAfterInvalidTransfer()
    {
        $this->testValidationShouldReturnFalseForInvalidTransfer();
        $messages = $this->adapter->getMessages();
        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }

    public function testErrorCodesShouldBeNullByDefault()
    {
        $errors = $this->adapter->getErrors();
        $this->assertIsArray($errors);
        $this->assertCount(0, $errors);
    }

    public function testErrorCodesShouldBePopulatedAfterInvalidTransfer()
    {
        $this->testValidationShouldReturnFalseForInvalidTransfer();
        $errors = $this->adapter->getErrors();
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    public function testAdapterShouldHavePluginLoaderForFilters()
    {
        $loader = $this->adapter->getPluginLoader('filter');
        $this->assertInstanceOf(Zend_Loader_PluginLoader::class, $loader);
    }

    public function testFilterPluginLoaderShouldRegisterPathsForBaseAndFileFiltersByDefault()
    {
        $loader = $this->adapter->getPluginLoader('filter');
        $paths  = $loader->getPaths('Zend_Filter');
        $this->assertIsArray($paths);
        $paths = $loader->getPaths('Zend_Filter_File');
        $this->assertIsArray($paths);
    }

    public function testAdapterShouldAllowAddingFilterInstance()
    {
        $filter = new Zend_Filter_StringToLower();
        $this->adapter->addFilter($filter);
        $test = $this->adapter->getFilter('Zend_Filter_StringToLower');
        $this->assertSame($filter, $test);
    }

    public function testAdapterShouldAllowAddingFilterViaPluginLoader()
    {
        $this->adapter->addFilter('StringTrim');
        $test = $this->adapter->getFilter('StringTrim');
        $this->assertInstanceOf(Zend_Filter_StringTrim::class, $test);
    }

    /**
     */
    public function testAdapterhShouldRaiseExceptionWhenAddingInvalidFilterType()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->addFilter(new Zend_Validate_File_Extension('jpg'));
    }

    public function testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader()
    {
        $filters = array(
            'Word_SeparatorToCamelCase' => array('separator' => ' '),
            array('filter'              => 'Alpha', 'options' => array(true)),
            new Zend_Filter_BaseName(),
        );
        $this->adapter->addFilters($filters);
        $test = $this->adapter->getFilters();
        $this->assertIsArray($test);
        $this->assertCount(3, $test, var_export($test, 1));
        $count = array_shift($test);
        $this->assertInstanceOf(Zend_Filter_Word_SeparatorToCamelCase::class, $count);
        $size = array_shift($test);
        $this->assertInstanceOf(Zend_Filter_Alpha::class, $size);
        $ext  = array_shift($test);
        $orig = array_pop($filters);
        $this->assertSame($orig, $ext);
    }

    public function testGetFilterShouldReturnNullWhenNoMatchingIdentifierExists()
    {
        $this->assertNull($this->adapter->getFilter('Alpha'));
    }

    public function testAdapterShouldAllowPullingFiltersByFile()
    {
        $this->adapter->addFilter('Alpha', false, 'foo');
        $filters = $this->adapter->getFilters('foo');
        $this->assertCount(1, $filters);
        $filter = array_shift($filters);
        $this->assertInstanceOf(Zend_Filter_Alpha::class, $filter);
    }

    public function testCallingSetFiltersOnAdapterShouldOverwriteExistingFilters()
    {
        $this->testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader();
        $filters = array(
            new Zend_Filter_StringToUpper(),
            new Zend_Filter_Alpha(),
        );
        $this->adapter->setFilters($filters);
        $test = $this->adapter->getFilters();
        $this->assertSame($filters, array_values($test));
    }

    public function testAdapterShouldAllowRetrievingFilterInstancesByClassName()
    {
        $this->testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader();
        $ext = $this->adapter->getFilter('Zend_Filter_BaseName');
        $this->assertInstanceOf(Zend_Filter_BaseName::class, $ext);
    }

    public function testAdapterShouldAllowRetrievingFilterInstancesByPluginName()
    {
        $this->testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader();
        $count = $this->adapter->getFilter('Alpha');
        $this->assertInstanceOf(Zend_Filter_Alpha::class, $count);
    }

    public function testAdapterShouldAllowRetrievingAllFiltersAtOnce()
    {
        $this->testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader();
        $filters = $this->adapter->getFilters();
        $this->assertIsArray($filters);
        $this->assertCount(3, $filters);
        foreach ($filters as $filter) {
            $this->assertInstanceOf(Zend_Filter_Interface::class, $filter);
        }
    }

    public function testAdapterShouldAllowRemovingFilterInstancesByClassName()
    {
        $this->testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader();
        $this->assertTrue($this->adapter->hasFilter('Zend_Filter_BaseName'));
        $this->adapter->removeFilter('Zend_Filter_BaseName');
        $this->assertFalse($this->adapter->hasFilter('Zend_Filter_BaseName'));
    }

    public function testAdapterShouldAllowRemovingFilterInstancesByPluginName()
    {
        $this->testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader();
        $this->assertTrue($this->adapter->hasFilter('Alpha'));
        $this->adapter->removeFilter('Alpha');
        $this->assertFalse($this->adapter->hasFilter('Alpha'));
    }

    public function testRemovingNonexistentFilterShouldDoNothing()
    {
        $this->testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader();
        $filters = $this->adapter->getFilters();
        $this->assertFalse($this->adapter->hasFilter('Int'));
        $this->adapter->removeFilter('Int');
        $this->assertFalse($this->adapter->hasFilter('Int'));
        $test = $this->adapter->getFilters();
        $this->assertSame($filters, $test);
    }

    public function testAdapterShouldAllowRemovingAllFiltersAtOnce()
    {
        $this->testAdapterShouldAllowAddingMultipleFiltersAtOnceUsingBothInstancesAndPluginLoader();
        $this->adapter->clearFilters();
        $filters = $this->adapter->getFilters();
        $this->assertIsArray($filters);
        $this->assertCount(0, $filters);
    }

    public function testTransferDestinationShouldBeMutable()
    {
        $directory = dirname(__FILE__);
        $this->adapter->setDestination($directory);
        $destinations = $this->adapter->getDestination();
        $this->assertIsArray($destinations);
        foreach ($destinations as $file => $destination) {
            $this->assertEquals($directory, $destination);
        }

        $newdirectory = dirname(__FILE__)
                      . DIRECTORY_SEPARATOR . '..'
                      . DIRECTORY_SEPARATOR . '..'
                      . DIRECTORY_SEPARATOR . '..'
                      . DIRECTORY_SEPARATOR . '_files';
        $this->adapter->setDestination($newdirectory, 'foo');
        $this->assertEquals($newdirectory, $this->adapter->getDestination('foo'));
        $this->assertEquals($directory, $this->adapter->getDestination('bar'));
    }

    public function testAdapterShouldAllowRetrievingDestinationsForAnArrayOfSpecifiedFiles()
    {
        $this->adapter->setDestination(dirname(__FILE__));
        $destinations = $this->adapter->getDestination(array('bar', 'baz'));
        $this->assertIsArray($destinations);
        $directory = dirname(__FILE__);
        foreach ($destinations as $file => $destination) {
            $this->assertTrue(in_array($file, array('bar', 'baz')));
            $this->assertEquals($directory, $destination);
        }
    }

    public function testSettingAndRetrievingOptions()
    {
        $this->assertEquals(
            array(
                'bar'     => array('ignoreNoFile' => false, 'useByteString' => true),
                'baz'     => array('ignoreNoFile' => false, 'useByteString' => true),
                'foo'     => array('ignoreNoFile' => false, 'useByteString' => true, 'detectInfos' => true),
                'file_0_' => array('ignoreNoFile' => false, 'useByteString' => true),
                'file_1_' => array('ignoreNoFile' => false, 'useByteString' => true),
            ),
            $this->adapter->getOptions()
        );

        $this->adapter->setOptions(array('ignoreNoFile' => true));
        $this->assertEquals(
            array(
                'bar'     => array('ignoreNoFile' => true, 'useByteString' => true),
                'baz'     => array('ignoreNoFile' => true, 'useByteString' => true),
                'foo'     => array('ignoreNoFile' => true, 'useByteString' => true, 'detectInfos' => true),
                'file_0_' => array('ignoreNoFile' => true, 'useByteString' => true),
                'file_1_' => array('ignoreNoFile' => true, 'useByteString' => true),
            ),
            $this->adapter->getOptions()
        );

        $this->adapter->setOptions(array('ignoreNoFile' => false), 'foo');
        $this->assertEquals(
            array(
                'bar'     => array('ignoreNoFile' => true, 'useByteString' => true),
                'baz'     => array('ignoreNoFile' => true, 'useByteString' => true),
                'foo'     => array('ignoreNoFile' => false, 'useByteString' => true, 'detectInfos' => true),
                'file_0_' => array('ignoreNoFile' => true, 'useByteString' => true),
                'file_1_' => array('ignoreNoFile' => true, 'useByteString' => true),
            ),
            $this->adapter->getOptions()
        );
    }

    public function testGetAllAdditionalFileInfos()
    {
        $files = $this->adapter->getFileInfo();
        $this->assertCount(5, $files);
        $this->assertEquals('baz.text', $files['baz']['name']);
    }

    public function testGetAdditionalFileInfosForSingleFile()
    {
        $files = $this->adapter->getFileInfo('baz');
        $this->assertCount(1, $files);
        $this->assertEquals('baz.text', $files['baz']['name']);
    }

    /**
     */
    public function testGetAdditionalFileInfosForUnknownFile()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $files = $this->adapter->getFileInfo('unknown');
    }

    /**
     */
    public function testGetUnknownOption()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->setOptions(array('unknownOption' => 'unknown'));
    }

    /**
     */
    public function testGetFileIsNotImplemented()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->getFile();
    }

    /**
     */
    public function testAddFileIsNotImplemented()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->addFile('foo');
    }

    /**
     */
    public function testGetTypeIsNotImplemented()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->getType();
    }

    /**
     */
    public function testAddTypeIsNotImplemented()
    {
        $this->expectException(\Zend_File_Transfer_Exception::class);

        $this->adapter->addType('foo');
    }

    public function testAdapterShouldAllowRetrievingFileName()
    {
        $path = dirname(__FILE__)
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '_files';
        $this->adapter->setDestination($path);
        $this->assertEquals($path . DIRECTORY_SEPARATOR . 'foo.jpg', $this->adapter->getFileName('foo'));
    }

    public function testAdapterShouldAllowRetrievingFileNameWithoutPath()
    {
        $path = dirname(__FILE__)
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '_files';
        $this->adapter->setDestination($path);
        $this->assertEquals('foo.jpg', $this->adapter->getFileName('foo', false));
    }

    public function testAdapterShouldAllowRetrievingAllFileNames()
    {
        $path = dirname(__FILE__)
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '_files';
        $this->adapter->setDestination($path);
        $files = $this->adapter->getFileName();
        $this->assertIsArray($files);
        $this->assertEquals($path . DIRECTORY_SEPARATOR . 'bar.png', $files['bar']);
    }

    public function testAdapterShouldAllowRetrievingAllFileNamesWithoutPath()
    {
        $path = dirname(__FILE__)
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '..'
              . DIRECTORY_SEPARATOR . '_files';
        $this->adapter->setDestination($path);
        $files = $this->adapter->getFileName(null, false);
        $this->assertIsArray($files);
        $this->assertEquals('bar.png', $files['bar']);
    }

    public function testExceptionForUnknownHashValue()
    {
        try {
            $this->adapter->getHash('foo', 'unknown_hash');
            $this->fail();
        } catch (Zend_Exception $e) {
            $this->assertStringContainsString('Unknown hash algorithm', $e->getMessage());
        }
    }

    public function testIgnoreHashValue()
    {
        $this->adapter->addInvalidFile();
        $return = $this->adapter->getHash('crc32', 'test');
        $this->assertEquals(array(), $return);
    }

    public function testEmptyTempDirectoryDetection()
    {
        $this->adapter->_tmpDir = '';
        $this->assertEmpty($this->adapter->_tmpDir, 'Empty temporary directory');
    }

    public function testTempDirectoryDetection()
    {
        $this->adapter->getTmpDir();
        $this->assertTrue(!empty($this->adapter->_tmpDir), 'Temporary directory filled');
    }

    public function testTemporaryDirectoryAccessDetection()
    {
        $this->adapter->_tmpDir = '.';
        $path                   = '/NoPath/To/File';
        $this->assertFalse($this->adapter->isPathWriteable($path));
        $this->assertTrue($this->adapter->isPathWriteable($this->adapter->_tmpDir));
    }

    public function testFileSizeButNoFileFound()
    {
        try {
            $this->assertEquals(10, $this->adapter->getFileSize());
            $this->fail();
        } catch (Zend_File_Transfer_Exception $e) {
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }
    }

    public function testIgnoreFileSize()
    {
        $this->adapter->addInvalidFile();
        $return = $this->adapter->getFileSize('test');
        $this->assertEquals(array(), $return);
    }

    public function testFileSizeByTmpName()
    {
        $options = $this->adapter->getOptions();
        $this->assertTrue($options['baz']['useByteString']);
        $this->assertEquals('1.14kB', $this->adapter->getFileSize('baz.text'));
        $this->adapter->setOptions(array('useByteString' => false));
        $options = $this->adapter->getOptions();
        $this->assertFalse($options['baz']['useByteString']);
        $this->assertEquals(1172, $this->adapter->getFileSize('baz.text'));
    }

    public function testMimeTypeButNoFileFound()
    {
        try {
            $this->assertEquals('image/jpeg', $this->adapter->getMimeType());
            $this->fail();
        } catch (Zend_File_Transfer_Exception $e) {
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }
    }

    public function testIgnoreMimeType()
    {
        $this->adapter->addInvalidFile();
        $return = $this->adapter->getMimeType('test');
        $this->assertEquals(array(), $return);
    }

    public function testMimeTypeByTmpName()
    {
        $this->assertEquals('text/plain', $this->adapter->getMimeType('baz.text'));
    }

    public function testSetOwnErrorMessage()
    {
        $this->adapter->addValidator('Count', false, array('min' => 5, 'max' => 5, 'messages' => array(Zend_Validate_File_Count::TOO_FEW => 'Zu wenige')));
        $this->assertFalse($this->adapter->isValid('foo'));
        $message = $this->adapter->getMessages();
        $this->assertContains('Zu wenige', $message);

        try {
            $this->assertEquals('image/jpeg', $this->adapter->getMimeType());
            $this->fail();
        } catch (Zend_File_Transfer_Exception $e) {
            $this->assertStringContainsString('does not exist', $e->getMessage());
        }
    }

    public function testTransferDestinationAtNonExistingElement()
    {
        $directory = dirname(__FILE__);
        $this->adapter->setDestination($directory, 'nonexisting');
        $this->assertEquals($directory, $this->adapter->getDestination('nonexisting'));
        try {
            $this->assertIsString($this->adapter->getDestination('reallynonexisting'));
            $this->fail();
        } catch (Exception $e) {
            $this->assertStringContainsString('not find', $e->getMessage());
        }
    }

    /**
     * @ZF-7376
     */
    public function testSettingMagicFile()
    {
        $this->adapter->setOptions(array('magicFile' => 'test/file'));
        $this->assertEquals(
            array(
                'bar' => array('magicFile' => 'test/file', 'ignoreNoFile' => false, 'useByteString' => true),
            ),
            $this->adapter->getOptions('bar')
        );
    }

    /**
     * @ZF-8693
     */
    public function testAdapterShouldAllowAddingMultipleValidatorsAtOnceUsingBothInstancesAndPluginLoaderForDifferentFiles()
    {
        $validators = array(
            array('MimeType', true, array('image/jpeg')), // no files
            array('FilesSize', true, array('max' => '1MB', 'messages' => 'файл больше 1MБ')), // no files
            array('Count', true, array('min' => 1, 'max' => '1', 'messages' => 'файл не 1'), 'bar'), // 'bar' from config
            array('MimeType', true, array('image/jpeg'), 'bar'), // 'bar' from config
        );

        $this->adapter->addValidators($validators, 'foo'); // set validators to 'foo'

        $test = $this->adapter->getValidators();
        $this->assertCount(3, $test);

        //test files specific validators
        $test = $this->adapter->getValidators('foo');
        $this->assertCount(2, $test);
        $mimeType = array_shift($test);
        $this->assertInstanceOf(Zend_Validate_File_MimeType::class, $mimeType);
        $filesSize = array_shift($test);
        $this->assertInstanceOf(Zend_Validate_File_FilesSize::class, $filesSize);

        $test = $this->adapter->getValidators('bar');
        $this->assertCount(2, $test);
        $filesSize = array_shift($test);
        $this->assertInstanceOf(Zend_Validate_File_Count::class, $filesSize);
        $mimeType = array_shift($test);
        $this->assertInstanceOf(Zend_Validate_File_MimeType::class, $mimeType);

        $test = $this->adapter->getValidators('baz');
        $this->assertCount(0, $test);
    }

    /**
     * @ZF-9132
     */
    public function testSettingAndRetrievingDetectInfosOption()
    {
        $this->assertEquals(array(
            'foo' => array(
                'ignoreNoFile'  => false,
                'useByteString' => true,
                'detectInfos'   => true)), $this->adapter->getOptions('foo'));
        $this->adapter->setOptions(array('detectInfos' => false));
        $this->assertEquals(array(
            'foo' => array(
                'ignoreNoFile'  => false,
                'useByteString' => true,
                'detectInfos'   => false)), $this->adapter->getOptions('foo'));
    }

    /**
     * @group GH-65
     */
    public function testSetDestinationWithNonExistingPathShouldThrowException()
    {
        // Create temporary directory
        $directory = dirname(__FILE__) . '/_files/destination';
        if (!is_dir($directory)) {
            @mkdir($directory);
        }
        chmod($directory, 0655);

        // Test
        try {
            $this->adapter->setDestination($directory);
            $this->fail('Destination is writable');
        } catch (Zend_File_Transfer_Exception $e) {
            $this->assertEquals(
                'The given destination is not writable',
                $e->getMessage()
            );
        }

        // Remove temporary directory
        @rmdir($directory);
    }
}

class Zend_File_Transfer_Adapter_AbstractTest_MockAdapter extends Zend_File_Transfer_Adapter_Abstract
{
    public $received = false;

    public $_tmpDir;

    public function __construct()
    {
        $testfile     = dirname(__FILE__) . '/_files/test.txt';
        $this->_files = array(
            'foo' => array(
                'name'      => 'foo.jpg',
                'type'      => 'image/jpeg',
                'size'      => 126976,
                'tmp_name'  => '/tmp/489127ba5c89c',
                'options'   => array('ignoreNoFile' => false, 'useByteString' => true, 'detectInfos' => true),
                'validated' => false,
                'received'  => false,
                'filtered'  => false,
            ),
            'bar' => array(
                'name'      => 'bar.png',
                'type'      => 'image/png',
                'size'      => 91136,
                'tmp_name'  => '/tmp/489128284b51f',
                'options'   => array('ignoreNoFile' => false, 'useByteString' => true),
                'validated' => false,
                'received'  => false,
                'filtered'  => false,
            ),
            'baz' => array(
                'name'      => 'baz.text',
                'type'      => 'text/plain',
                'size'      => 1172,
                'tmp_name'  => $testfile,
                'options'   => array('ignoreNoFile' => false, 'useByteString' => true),
                'validated' => false,
                'received'  => false,
                'filtered'  => false,
            ),
            'file_0_' => array(
                'name'      => 'foo.jpg',
                'type'      => 'image/jpeg',
                'size'      => 126976,
                'tmp_name'  => '/tmp/489127ba5c89c',
                'options'   => array('ignoreNoFile' => false, 'useByteString' => true),
                'validated' => false,
                'received'  => false,
                'filtered'  => false,
            ),
            'file_1_' => array(
                'name'      => 'baz.text',
                'type'      => 'text/plain',
                'size'      => 1172,
                'tmp_name'  => $testfile,
                'options'   => array('ignoreNoFile' => false, 'useByteString' => true),
                'validated' => false,
                'received'  => false,
                'filtered'  => false,
            ),
            'file' => array(
                'name'       => 'foo.jpg',
                'multifiles' => array(0 => 'file_0_', 1 => 'file_1_')
            ),
        );
    }

    public function send($options = null)
    {
        return;
    }

    public function receive($options = null)
    {
        $this->received = true;
        return;
    }

    public function isSent($file = null)
    {
        return false;
    }

    public function isReceived($file = null)
    {
        return $this->received;
    }

    public function isUploaded($files = null)
    {
        return true;
    }

    public function isFiltered($files = null)
    {
        return true;
    }

    public static function getProgress()
    {
        return;
    }

    public function getTmpDir()
    {
        $this->_tmpDir = parent::_getTmpDir();
    }

    public function isPathWriteable($path)
    {
        return parent::_isPathWriteable($path);
    }

    public function addInvalidFile()
    {
        $this->_files += array(
            'test' => array(
                'name'      => 'test.txt',
                'type'      => 'image/jpeg',
                'size'      => 0,
                'tmp_name'  => '',
                'options'   => array('ignoreNoFile' => true, 'useByteString' => true),
                'validated' => false,
                'received'  => false,
                'filtered'  => false,
            )
        );
    }
}
