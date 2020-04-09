<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Filesystem;
use Gidato\Filesystem\Memory;
use Gidato\Filesystem\Model\BasicFile;
use Gidato\Filesystem\Model\JsonFile;
use Gidato\Filesystem\Model\Base;
use Gidato\Filesystem\Model\ReadOnlyBase;
use Gidato\Filesystem\Model\Unknown;
use Gidato\Filesystem\Model\Directory;
use Mockery;
use InvalidArgumentException;
use RuntimeException;

class JsonFileTest extends TestCase
{
    protected $filesystem;
    protected $parent;
    protected $path;

    public function setUp() : void
    {
        $this->filesystem = new Memory();
        $this->filesystem->mkdir('/test');
        $this->parent = new Base('/test', $this->filesystem);
        $this->path = new JsonFile($this->parent, 'testfile.json');
    }

    public function tearDown() : void
    {
        Mockery::close();
    }

    public function testValidName()
    {
        $path = new JsonFile($this->parent, 'filename.json');
        $this->assertEquals('filename.json', $path->getName());
    }

    public function testInvalidNameNotJson()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('extension must be .json');
        $path = new JsonFile($this->parent, 'filename');
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new JsonFile($this->parent, '>');
    }

    public function testGlobName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new JsonFile($this->parent, '?');
    }

    public function testWhenFileIsADirectory()
    {
        $this->filesystem->mkdir('/test/pathname.json', 0755, true);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path is a directory - cannot be used as a file');
        $path = new JsonFile($this->parent, 'pathname.json');
    }

    public function testCastingFromUnknown()
    {
        $path = new Unknown($this->parent, 'unknown');
        $file = BasicFile::castFrom($path);
        $this->assertInstanceOf(BasicFile::class, $file);
        $this->assertEquals('unknown', $path->getName());
    }

    public function testCastingFromJsonFile()
    {
        $path = new JsonFile($this->parent, 'filename.json');
        $file = JsonFile::castFrom($path);
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
        $this->assertEquals('[]', $this->filesystem->file_get_contents('/test/testfile.json'));
    }

    public function testCreatingAFileThatExists()
    {
        $this->path->create();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File exists (testfile.json)');
        $this->path->create();
    }

    public function testGetFileContents()
    {
        $this->filesystem->file_put_contents('/test/testfile.json',json_encode(['a'=>'b']));
        $this->assertEquals(['a' => 'b'], $this->path->getContents());
        $this->assertEquals(['a' => 'b'], $this->path->contents);
    }

    public function testGetFileContentsWhenFileDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File does not exist (testfile.json)');
        $this->path->getContents();
    }

    public function testGetFileContentsWhenReadError()
    {
        $filesystem = Mockery::Mock(Filesystem::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testfile.json')->andReturn(true);
        $filesystem->shouldReceive('is_dir')->with('/test/testfile.json')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testfile.json')->andReturn(true);
        $filesystem->shouldReceive('file_get_contents')->with('/test/testfile.json')->andReturn(false);

        $base = new Base('/test', $filesystem);
        $path = new JsonFile($base, 'testfile.json');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read from file (testfile.json)');
        $path->getContents();
    }

    public function testSetContentsWhenNoJsonFile()
    {
        $this->path->setContents(['a' => 'b']);
        $this->assertEquals(json_encode(['a' => 'b']), $this->filesystem->file_get_contents('/test/testfile.json'));
    }

    public function testSetContentsWhenFileExists()
    {
        $this->filesystem->file_put_contents('/test/testfile.json', json_encode(['c' => 'd']));
        $this->path->setContents(['a' => 'b']);
        $this->assertEquals(json_encode(['a' => 'b']), $this->filesystem->file_get_contents('/test/testfile.json'));
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
        $this->filesystem->touch('/test/testfile.json');
        $destination = new Unknown($this->parent, 'dest');
        $this->path->copyTo($destination);
        $this->assertEquals('', $this->filesystem->file_get_contents('/test/dest/testfile.json'));
    }

    public function testCopyToWhenDestinationDoesNotExistAndIsDirectory()
    {
        $this->filesystem->touch('/test/testfile.json');
        $destination = new Directory($this->parent, 'dest');
        $this->path->copyTo($destination);
        $this->assertEquals('', $this->filesystem->file_get_contents('/test/dest/testfile.json'));
    }

    public function testCopyToWhenDestinationExists()
    {
        $this->filesystem->touch('/test/testfile.json');
        $this->filesystem->mkdir('/test/dest');
        $destination = new Directory($this->parent, 'dest');
        $this->path->copyTo($destination);
        $this->assertEquals('', $this->filesystem->file_get_contents('/test/dest/testfile.json'));
    }

    public function testCopyToWhenCopyFails()
    {
        $filesystem = Mockery::Mock(Filesystem::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testfile.json')->andReturn(true);
        $filesystem->shouldReceive('is_dir')->with('/test/testfile.json')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testfile.json')->andReturn(true);
        $filesystem->shouldReceive('copy')->with('/test/testfile.json', '/test/dest/testfile.json')->andReturn(false);

        $destination = new Directory($this->parent, 'dest');
        $base = new Base('/test', $filesystem);
        $path = new JsonFile($base, 'testfile.json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to copy from testfile.json to dest');

        $path->copyTo($destination);
    }

    public function testDeleteSuccess()
    {
        $this->filesystem->touch('/test/testfile.json');
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
        $path = new JsonFile($base, 'testfile.json');
        $this->filesystem->touch('/test/testfile.json');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File cannot be deleted, read only (testfile.json)');
        $path->delete();
    }

    public function testDeleteWhenReadOnlyAndForce()
    {
        $base = new ReadOnlyBase('/test', $this->filesystem);
        $path = new JsonFile($base, 'testfile.json');
        $this->filesystem->touch('/test/testfile.json');
        $path->delete(true);
        $this->assertFalse($path->exists());
    }

    public function testDeleteWhenUnlinkFails()
    {
        $filesystem = Mockery::Mock(Filesystem::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testfile.json')->andReturn(true);
        $filesystem->shouldReceive('is_dir')->with('/test/testfile.json')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testfile.json')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test/testfile.json')->andReturn(true);
        $filesystem->shouldReceive('unlink')->with('/test/testfile.json')->andReturn(false);

        $base = new Base('/test', $filesystem);
        $path = new JsonFile($base, 'testfile.json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete file (testfile.json)');

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
