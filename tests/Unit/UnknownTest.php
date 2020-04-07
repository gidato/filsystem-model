<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Memory;
use Gidato\Filesystem\Models\Base;
use Gidato\Filesystem\Models\Unknown;
use InvalidArgumentException;

class UnknownTest extends TestCase
{
    protected $filesystem;
    protected $parent;
    protected $base;
    protected $path;

    public function setUp() : void
    {
        $this->filesystem = new Memory();
        $this->filesystem->mkdir('/test');
        $this->base = new Base('/test', $this->filesystem);
        $this->path = new Unknown($this->base, 'unknown');
    }

    public function testValidName()
    {
        $this->assertEquals('unknown', $this->path->getName());
        $this->assertEquals('unknown', $this->path->name);
    }

    public function testValidPath()
    {
        $this->assertEquals('/test/unknown', $this->path->getFullPath());
        $this->assertEquals('/test/unknown', $this->path->fullPath);
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new Unknown($this->base, '>');
    }

}
