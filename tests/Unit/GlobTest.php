<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Filesystem;
use Gidato\Filesystem\Memory;
use Gidato\Filesystem\Models\Base;
use Gidato\Filesystem\Models\Glob;
use Gidato\Filesystem\Models\Directory;
use Gidato\Filesystem\Models\BasicFile;
use Gidato\Filesystem\Models\JsonFile;
use Mockery;
use InvalidArgumentException;
use RuntimeException;

class GlobTest extends TestCase
{
    protected $filesystem;
    protected $parent;
    protected $path;

    public function setUp() : void
    {
        $this->filesystem = new Memory;
        $this->parent = new Base('/test', $this->filesystem);
        $this->path = new Glob($this->parent, '*');
    }

    public function tearDown() : void
    {
        Mockery::close();
    }

    public function testValidName()
    {
        $path = new Glob($this->parent, 'pathName');
        $this->assertEquals('pathName', $path->getName());
        $this->assertEquals('pathName', $path->name);

        $path = new Glob($this->parent, '*');
        $this->assertEquals('*', $path->getName());

        $path = new Glob($this->parent, '??');
        $this->assertEquals('??', $path->getName());
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new Glob($this->parent, '>');
    }

    public function testWithFurtherPath()
    {
        $glob = $this->path->with('fred/?ob/john/*');
        $this->assertEquals('/test/*/fred/?ob/john/*', $glob->getFullPath());
        $this->assertEquals('/test/*/fred/?ob/john/*', $glob->fullPath);
        $this->assertEquals('*/fred/?ob/john/*', $glob->path);
        $this->assertEquals('*/fred/?ob/john/*', (string )$glob);
    }

    public function testGetBase()
    {
        $this->assertSame($this->parent, $this->path->getBase());
        $this->assertSame($this->parent, $this->path->base);
    }

    public function testGlobbing()
    {
        $this->filesystem->mkdir('/test');
        $this->filesystem->mkdir('/test/testdir');
        $this->filesystem->touch('/test/testfile');
        $this->filesystem->touch('/test/testfile.json');
        $this->filesystem->mkdir('/test/testdir2');
        $this->filesystem->touch('/test/testdir/testfile');
        $paths = $this->path->glob();
        $this->assertCount(4, $paths);

        $this->assertInstanceOf(Directory::class, $paths[0]);
        $this->assertEquals('/test/testdir', $paths[0]->fullPath);

        $this->assertInstanceOf(BasicFile::class, $paths[1]);
        $this->assertEquals('/test/testfile', $paths[1]->fullPath);

        $this->assertInstanceOf(JsonFile::class, $paths[2]);
        $this->assertEquals('/test/testfile.json', $paths[2]->fullPath);

        $this->assertInstanceOf(Directory::class, $paths[3]);
        $this->assertEquals('/test/testdir2', $paths[3]->fullPath);
    }

}
