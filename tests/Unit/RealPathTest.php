<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Filesystem;
use Gidato\Filesystem\Model\RealPath;
use Gidato\Filesystem\Model\Base;
use Gidato\Filesystem\Model\Directory;
use Mockery;
use InvalidArgumentException;
use RuntimeException;

class RealPathTest extends TestCase
{
    protected $filesystem;
    protected $parent;
    protected $path;
    protected $linkTarget;
    protected $name = 'test_path';

    public function setUp() : void
    {
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->parent = Mockery::mock(Base::class);
        $this->path = new TestRealPath($this->parent, $this->name);
        $this->linkTarget = new TestRealPath($this->parent, 'link_target');
    }

    public function tearDown() : void
    {
        Mockery::close();
    }

    public function testValidName()
    {
        $path = new TestRealPath($this->parent, 'pathName');
        $this->assertEquals('pathName', $path->getName());
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new TestRealPath($this->parent, '>');
    }

    public function testGlobName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new TestRealPath($this->parent, '?');
    }

    public function testIfIsReadOnlyWhenParentIs()
    {
        $this->parent->shouldReceive('isReadOnly')->andReturn(true);
        $this->assertTrue($this->path->isReadOnly());
    }

    public function testIfIsReadOnlyWhenParentIsNotButFileIsNotWritable()
    {
        $this->parent->shouldReceive('isReadOnly')->andReturn(false);
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_writable')->with("full_path/{$this->name}")->andReturn(false);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(true);
        $this->assertTrue($this->path->isReadOnly());
    }

    public function testIfIsReadOnlyWhenParentIsNotAndFileIsWritable()
    {
        $this->parent->shouldReceive('isReadOnly')->andReturn(false);
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_writable')->with("full_path/{$this->name}")->andReturn(true);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(true);
        $this->assertFalse($this->path->isReadOnly());
    }

    public function testIfIsReadOnlyFileDoesNotExist()
    {
        $this->parent->shouldReceive('isReadOnly')->andReturn(false);
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(false);
        $this->assertFalse($this->path->isReadOnly());
    }

    public function testIfPathExistsWhenItDoes()
    {
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(true);
        $this->assertTrue($this->path->exists());
    }

    public function testIfPathExistsWhenItDoesNot()
    {
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(false);
        $this->assertFalse($this->path->exists());
    }

    public function testLinkingWhenPathAlreadyExists()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Destination of link (parent_path/{$this->name}) already exists");
        $this->path->linkTo($this->linkTarget);
    }

    public function testLinkingWhenTargetDoesNotExist()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(false);
        $this->filesystem->shouldReceive('file_exists')->with('full_path/link_target')->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Target of link (parent_path/link_target) does not exist');
        $this->path->linkTo($this->linkTarget);
    }


    public function testLinkingWhenSymlinkFails()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(false);
        $this->filesystem->shouldReceive('file_exists')->with('full_path/link_target')->andReturn(true);
        $this->filesystem->shouldReceive('symlink')->with('full_path/link_target', "full_path/{$this->name}")->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed to create link from parent_path/{$this->name} to parent_path/link_target");
        $this->path->linkTo($this->linkTarget);
    }

    public function testSuccessfulLinking()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(false);
        $this->filesystem->shouldReceive('file_exists')->with('full_path/link_target')->andReturn(true);
        $this->filesystem->shouldReceive('symlink')->with('full_path/link_target', "full_path/{$this->name}")->andReturn(true);

        $this->path->linkTo($this->linkTarget);
        $this->assertTrue(true); // basically something to stop phpunit squaking when no errors;
    }

    public function testSuccessfulLinkingFrom()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(false);
        $this->filesystem->shouldReceive('file_exists')->with('full_path/link_target')->andReturn(true);
        $this->filesystem->shouldReceive('symlink')->with('full_path/link_target', "full_path/{$this->name}")->andReturn(true);

        $this->linkTarget->linkFrom($this->path);
        $this->assertTrue(true); // basically something to stop phpunit squaking when no errors;
    }

    public function testIsLinkWhenNot()
    {
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(false);
        $this->assertFalse($this->path->isLink());
    }

    public function testIsLinkWhenItIs()
    {
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(true);
        $this->assertTrue($this->path->isLink());
    }

    public function testGetLinkTargetWhenNotALink()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Path is not a link (parent_path/{$this->name})");
        $this->path->getLinkTarget();
    }

    public function testGetLinkTargetWhenReadLinkFails()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(true);
        $this->filesystem->shouldReceive('readlink')->with("full_path/{$this->name}")->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed to read link for parent_path/{$this->name}");
        $this->path->getLinkTarget();
    }

    public function testGetLinkTargetWhenLinkExists()
    {
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->parent->shouldReceive('getBase')->andReturn($this->parent);
        $this->parent->shouldReceive('with')->with('full_path/new_path')->andReturn($this->linkTarget);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(true);
        $this->filesystem->shouldReceive('readlink')->with("full_path/{$this->name}")->andReturn('full_path/new_path');

        $this->assertSame($this->linkTarget, $this->path->getLinkTarget());
    }

    public function testUnlinkWhenNotALinkButIsAFile()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(false);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Path is not a link (parent_path/{$this->name})");
        $this->path->unlink();
    }

    public function testUnlinkWhenDoesNotExist()
    {
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(false);
        $this->filesystem->shouldReceive('file_exists')->with("full_path/{$this->name}")->andReturn(false);

        $this->path->unlink();
        // this should be OK as link is already deleted
        $this->assertTrue(true); // basically something to stop phpunit squaking when no errors;
    }

    public function testUnlinkWhenItIsALinkButFailsToRemove()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(true);
        $this->filesystem->shouldReceive('unlink')->with("full_path/{$this->name}")->once()->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed to remove link (parent_path/{$this->name})");
        $this->path->unlink();
    }

    public function testUnlinkWhenItIsALinkAndSuccessfulRemove()
    {
        $this->parent->shouldReceive('getPath')->andReturn('parent_path');
        $this->parent->shouldReceive('getFullPath')->andReturn('full_path');
        $this->parent->shouldReceive('getFilesystem')->andReturn($this->filesystem);
        $this->filesystem->shouldReceive('is_link')->with("full_path/{$this->name}")->andReturn(true);
        $this->filesystem->shouldReceive('unlink')->with("full_path/{$this->name}")->once()->andReturn(true);

        $this->path->unlink();
        // this should be OK as link is already deleted
        $this->assertTrue(true); // basically something to stop phpunit squaking when no errors;
    }

    public function testToStringWithAndWithoutDecorators()
    {
        $this->assertEquals($this->name, (string) $this->path);
    }

    public function testIfDirectory()
    {
        $this->assertFalse($this->path->isDirectory());
    }

    public function testIfFile()
    {
        $this->assertFalse($this->path->isFile());
    }
}

class TestRealPath extends RealPath
{
    public function __construct(Directory $parent, string $name)
    {
        $this->validateName($name);
        $this->setName($name);
        $this->setParent($parent);
    }
}
