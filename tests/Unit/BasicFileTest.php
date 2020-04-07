<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Filesystem;
use Gidato\Filesystem\Memory;
use Gidato\Filesystem\Models\BasicFile;
use Gidato\Filesystem\Models\Base;
use Gidato\Filesystem\Models\ReadOnlyBase;
use Gidato\Filesystem\Models\Unknown;
use Gidato\Filesystem\Models\Directory;
use Mockery;
use InvalidArgumentException;
use RuntimeException;

class BasicFileTest extends TestCase
{
    protected $filesystem;
    protected $parent;
    protected $path;

    public function setUp() : void
    {
        $this->filesystem = new Memory();
        $this->filesystem->mkdir('/test');
        $this->parent = new Base('/test', $this->filesystem);
        $this->path = new BasicFile($this->parent, 'testfile');
    }

    public function tearDown() : void
    {
        Mockery::close();
    }

    public function testValidName()
    {
        $path = new BasicFile($this->parent, 'filename');
        $this->assertEquals('filename', $path->getName());
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new BasicFile($this->parent, '>');
    }

    public function testGlobName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new BasicFile($this->parent, '?');
    }

    public function testWhenFileIsADirectory()
    {
        $this->filesystem->mkdir('/test/pathname', 0755, true);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path is a directory - cannot be used as a file');
        $path = new BasicFile($this->parent, 'pathname');
    }

    public function testCastingFromUnknown()
    {
        $path = new Unknown($this->parent, 'unknown');
        $file = BasicFile::castFrom($path);
        $this->assertInstanceOf(BasicFile::class, $file);
        $this->assertEquals('unknown', $path->getName());
    }

    public function testCastingFromFile()
    {
        $path = new BasicFile($this->parent, 'filename');
        $file = BasicFile::castFrom($path);
        $this->assertSame($path, $file);
    }

    public function testCastingFromDirectory()
    {
        $path = new Directory($this->parent, 'filename');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cast a directory to a file');
        $file = BasicFile::castFrom($path);
    }

    public function testCreatingAFileThatDoesNotExist()
    {
        $this->path->create();
        $this->assertEquals('', $this->filesystem->file_get_contents('/test/testfile'));
    }

    public function testCreatingAFileThatExists()
    {
        $this->path->create();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File exists (testfile)');
        $this->path->create();
    }

    public function testGetFileContents()
    {
        $this->filesystem->file_put_contents('/test/testfile','test-contents');
        $this->assertEquals('test-contents', $this->path->getContents());
        $this->assertEquals('test-contents', $this->path->contents);
    }

    public function testGetFileContentsWhenFileDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File does not exist (testfile)');
        $this->path->getContents();
    }

    public function testGetFileContentsWhenReadError()
    {
        $filesystem = Mockery::Mock(Filesystem::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testfile')->andReturn(true);
        $filesystem->shouldReceive('is_dir')->with('/test/testfile')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testfile')->andReturn(true);
        $filesystem->shouldReceive('file_get_contents')->with('/test/testfile')->andReturn(false);

        $base = new Base('/test', $filesystem);
        $path = new BasicFile($base, 'testfile');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read from file (testfile)');
        $path->getContents();
    }

    public function testSetContentsWhenNoFile()
    {
        $this->path->setContents('some-contents');
        $this->assertEquals('some-contents', $this->filesystem->file_get_contents('/test/testfile'));
    }

    public function testSetContentsWhenFileExists()
    {
        $this->filesystem->file_put_contents('/test/testfile', 'previous-contents');
        $this->path->setContents('some-contents');
        $this->assertEquals('some-contents', $this->filesystem->file_get_contents('/test/testfile'));
    }

    public function testAppendContentsWhenNoFile()
    {
        $this->path->appendContents('some-contents');
        $this->assertEquals('some-contents', $this->filesystem->file_get_contents('/test/testfile'));
    }

    public function testAppendContentsWhenFileExists()
    {
        $this->filesystem->file_put_contents('/test/testfile', 'previous-contents');
        $this->path->appendContents('some-contents');
        $this->assertEquals('previous-contentssome-contents', $this->filesystem->file_get_contents('/test/testfile'));
    }


    public function testCopyToWhenDestinationIsAFile()
    {
        $destination = $this->parent->withFile('destfile');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination should be a directory');
        $this->path->copyTo($destination);
    }

    public function testCopyToWhenDestinationIsReadOnly()
    {
        $destination = $this->parent->withDirectory('dest');
        $this->filesystem->mkdir('/test/dest',0444);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination is read only');
        $this->path->copyTo($destination);
    }

    public function testCopyToWhenDestinationDoesNotExistAndIsUnknown()
    {
        $this->filesystem->touch('/test/testfile');
        $destination = new Unknown($this->parent, 'dest');
        $this->path->copyTo($destination);
        $this->assertEquals('', $this->filesystem->file_get_contents('/test/dest/testfile'));
    }

    public function testCopyToWhenDestinationDoesNotExistAndIsDirectory()
    {
        $this->filesystem->touch('/test/testfile');
        $destination = new Directory($this->parent, 'dest');
        $this->path->copyTo($destination);
        $this->assertEquals('', $this->filesystem->file_get_contents('/test/dest/testfile'));
    }

    public function testCopyToWhenDestinationExists()
    {
        $this->filesystem->touch('/test/testfile');
        $this->filesystem->mkdir('/test/dest');
        $destination = new Directory($this->parent, 'dest');
        $this->path->copyTo($destination);
        $this->assertEquals('', $this->filesystem->file_get_contents('/test/dest/testfile'));
    }

    public function testCopyToWhenCopyFails()
    {
        $filesystem = Mockery::Mock(Filesystem::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testfile')->andReturn(true);
        $filesystem->shouldReceive('is_dir')->with('/test/testfile')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testfile')->andReturn(true);
        $filesystem->shouldReceive('copy')->with('/test/testfile', '/test/dest/testfile')->andReturn(false);

        $destination = new Directory($this->parent, 'dest');
        $base = new Base('/test', $filesystem);
        $path = new BasicFile($base, 'testfile');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to copy from testfile to dest');

        $path->copyTo($destination);
    }

    public function testDeleteSuccess()
    {
        $this->filesystem->touch('/test/testfile');
        $this->path->delete();
        $this->assertFalse($this->path->exists());
    }

    public function testDeleteWhenNotExists()
    {
        $this->path->delete();
        $this->assertFalse($this->path->exists());
    }

    public function testDeleteWhenReadOnly()
    {
        $base = new ReadOnlyBase('/test', $this->filesystem);
        $path = new BasicFile($base, 'testfile');
        $this->filesystem->touch('/test/testfile');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File cannot be deleted, read only (testfile)');
        $path->delete();
    }

    public function testDeleteWhenReadOnlyAndForce()
    {
        $base = new ReadOnlyBase('/test', $this->filesystem);
        $path = new BasicFile($base, 'testfile');
        $this->filesystem->touch('/test/testfile');
        $path->delete(true);
        $this->assertFalse($path->exists());
    }

    public function testDeleteWhenUnlinkFails()
    {
        $filesystem = Mockery::Mock(Filesystem::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testfile')->andReturn(true);
        $filesystem->shouldReceive('is_dir')->with('/test/testfile')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testfile')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test/testfile')->andReturn(true);
        $filesystem->shouldReceive('unlink')->with('/test/testfile')->andReturn(false);

        $base = new Base('/test', $filesystem);
        $path = new BasicFile($base, 'testfile');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete file (testfile)');

        $path->delete();
    }

    public function testIfDirectory()
    {
        $this->assertFalse($this->path->isDirectory());
    }

    public function testIfFile()
    {
        $this->assertTrue($this->path->isFile());
    }

}
