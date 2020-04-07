<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Memory;
use Gidato\Filesystem\Models\FileTypes;
use Gidato\Filesystem\Models\BasicFile;
use Gidato\Filesystem\Models\JsonFile;
use InvalidArgumentException;

class FileTypesTest extends TestCase
{
    private $handler;

    public function setUp() : void
    {
        $this->handler = new FileTypes();
    }

    public function testGetClassForJsonFile()
    {
        $this->assertEquals(JsonFile::class, $this->handler->getFileClassForName('test.json'));
    }

    public function testGetClassForXlsFile()
    {
        // anything but json
        $this->assertEquals(BasicFile::class, $this->handler->getFileClassForName('test.xlsx'));
    }

    public function testGetClassForConfigJsonFile()
    {
        $this->handler->addType('config.json', ConfigJsonFile::class);
        $this->assertEquals(ConfigJsonFile::class, $this->handler->getFileClassForName('config.json'));
    }

    public function testAppendFileTypeWrongOrder()
    {
        $this->handler->addType('.gz', ZipFile::class, FileTypes::APPEND);
        $this->handler->addType('.enc.gz', EncryptedFile::class, FileTypes::APPEND);
        $this->assertEquals(ZipFile::class, $this->handler->getFileClassForName('test.enc.gz'));
        $this->assertEquals(ZipFile::class, $this->handler->getFileClassForName('test.gz'));
    }

    public function testAppendFileTypeCorrectOrder()
    {
        $this->handler->addType('.enc.gz', EncryptedFile::class, FileTypes::APPEND);
        $this->handler->addType('.gz', ZipFile::class, FileTypes::APPEND);
        $this->assertEquals(EncryptedFile::class, $this->handler->getFileClassForName('test.enc.gz'));
        $this->assertEquals(ZipFile::class, $this->handler->getFileClassForName('test.gz'));
    }

    public function testAddingClassWhichDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class what-no-class does not exist');
        $this->handler->addType('.what', 'what-no-class');
    }

    public function testAddingClassWhereSuffixAlreadyExists()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Suffix '.json' already set up");
        $this->handler->addType('.json', ConfigJsonFile::class);
    }

    public function testReplacingClassWhereSuffixAlreadyExists()
    {
        $this->handler->replaceType('.json', ConfigJsonFile::class);
        $this->assertEquals(ConfigJsonFile::class, $this->handler->getFileClassForName('test.json'));
    }

    public function testReplacingClassWhereSuffixDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Suffix 'config.json' has not been set up");
        $this->handler->replaceType('config.json', ConfigJsonFile::class);
    }

    public function testReplacingClassWhereClassDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class what-no-class does not exist');
        $this->handler->replaceType('.what', 'what-no-class');
    }
}

class ConfigJsonFile extends JsonFile
{
}

class ZipFile extends BasicFile
{
}

class EncryptedFile extends BasicFile
{
}
