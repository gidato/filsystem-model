<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Memory;
use Gidato\Filesystem\Model\Base;

class BaseTest extends TestCase
{
    protected $filesystem;
    protected $base;

    public function setUp() : void
    {
        $this->filesystem = new Memory();
        $this->filesystem->mkdir('/test');
        $this->base = new Base('/test', $this->filesystem);
    }

    public function testGetName()
    {
        $this->assertEquals('/', $this->base->getName());
        $this->assertEquals('/', (string) $this->base);
    }

    public function testGetPath()
    {
        $this->assertEquals('/', $this->base->getPath());
    }

    public function testGetFullPath()
    {
        $this->assertEquals('/test', $this->base->getFullPath());
    }

    public function testGetBase()
    {
        $this->assertEquals($this->base, $this->base->getBase());
    }

    public function testGetFilesystem()
    {
        $this->assertEquals($this->filesystem, $this->base->getFilesystem());
    }

    public function testIsReadOnly()
    {
        $this->assertFalse($this->base->isReadOnly());
    }

    public function testIsReadOnlyWhenFilesystemIsReadOnly()
    {
        $this->filesystem->mkdir('/ro_test',0444);
        $readOnlyBase = new Base('/ro_test', $this->filesystem);
        $this->assertTrue($readOnlyBase->isReadOnly());
    }

    public function testHasParent()
    {
        $this->assertFalse($this->base->hasParent());
    }

    public function testWithRelativePath()
    {
        $this->assertEquals('/test/unknown', $this->base->with('/unknown')->fullPath);
    }

    public function testWithAbsolutePath()
    {
        $this->assertEquals('/test/unknown', $this->base->with('/test/unknown')->fullPath);
    }
}
