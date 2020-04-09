<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Filesystem;
use Gidato\Filesystem\Model\Path;
use Gidato\Filesystem\Model\Base;
use Gidato\Filesystem\Model\Directory;
use Mockery;

class PathTest extends TestCase
{
    protected $filesystem;
    protected $parent;
    protected $path;

    public function setUp() : void
    {
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->parent = Mockery::mock(Base::class);
        $this->name = 'test_path';
        $this->path = new TestPath($this->parent, $this->name);
    }

    public function tearDown() : void
    {
        Mockery::close();
    }

    public function testRetrievingBase()
    {
        $this->parent->shouldReceive('getBase')->andReturn($this->parent);
        $this->assertSame($this->parent, $this->path->getBase());
        $this->assertSame($this->parent, $this->path->base);
    }

    public function testRetrievingFilesystem()
    {
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->assertSame($this->filesystem, $this->path->getFilesystem());
    }

    public function testGettingName()
    {
        $this->assertEquals($this->name, $this->path->getName());
        $this->assertEquals($this->name, $this->path->name);
    }

    public function testGettingPath()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->assertEquals("parent_path/{$this->name}", $this->path->getPath());
        $this->assertEquals("parent_path/{$this->name}", $this->path->path);
    }

    public function testGettingFullPath()
    {
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->assertEquals("full_path/{$this->name}", $this->path->getFullPath());
        $this->assertEquals("full_path/{$this->name}", $this->path->fullPath);
    }
}

class TestPath extends Path
{
    private $parent;

    public function __construct(Directory $parent, string $name)
    {
        $this->setName($name);
        $this->setParent($parent);
    }

    private function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }
}
