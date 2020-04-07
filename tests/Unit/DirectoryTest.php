<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gidato\Filesystem\Memory;
use Gidato\Filesystem\Models\RealPath;
use Gidato\Filesystem\Models\Base;
use Gidato\Filesystem\Models\Directory;
use Gidato\Filesystem\Models\Glob;
use Gidato\Filesystem\Models\BasicFile;
use Gidato\Filesystem\Models\JsonFile;
use Gidato\Filesystem\Models\Unknown;
use Mockery;
use InvalidArgumentException;
use RuntimeException;

class DirectoryTest extends TestCase
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
        $this->path = new Directory($this->base, 'testdir');
    }

    public function tearDown() : void
    {
        Mockery::close();
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters in path');
        $path = new Directory($this->base, '>');
    }
        public function testValidName()
    {
        $this->assertEquals('testdir', $this->path->getName());
        $this->assertEquals('testdir', $this->path->name);
    }

    public function testValidPath()
    {
        $this->assertEquals('testdir', $this->path->getPath());
        $this->assertEquals('testdir', $this->path->path);
    }

    public function testValidFullPath()
    {
        $this->assertEquals('/test/testdir', $this->path->getFullPath());
        $this->assertEquals('/test/testdir', $this->path->fullPath);
    }

    public function testCreationWhenNotExists()
    {
        $this->assertFalse($this->path->exists());
        $this->path->create();
        $this->assertTrue($this->path->exists());
    }

    public function testCreateWhenExists()
    {
        $this->assertFalse($this->path->exists());
        $this->path->create();
        $this->assertTrue($this->path->exists());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory exists (testdir)');
        $this->path->create();
    }

    public function testCreateWhenDoesNotExistAndMoreThanOneLevelDeep()
    {
        $path = new Directory($this->path, 'next_level');
        $path->create();
        $this->assertEquals('/test/testdir/next_level', $path->getFullPath());
    }

    public function testCreateFails()
    {
        // mock the filesystem to force the error
        $filesystem = Mockery::mock(Memory::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testdir')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testdir')->andReturn(false);
        $filesystem->shouldReceive('mkdir')->with('/test/testdir', 0777, true)->andReturn(false);

        $base = new Base('/test', $filesystem);
        $path = new Directory($base, 'testdir');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create directory (testdir)');
        $path->create();
    }

    public function testCastFromUnknown()
    {
        $path = Directory::castFrom(new Unknown($this->base, 'unknown'));
        $this->assertInstanceOf(Directory::class, $path);
        $this->assertSame('/test/unknown', $path->fullPath);
    }

    public function testCastFromFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cast a file to a directory');
        $path = Directory::castFrom(new BasicFile($this->base, 'file'));
    }

    public function testCastFromDirectory()
    {
        $this->assertSame($this->path, Directory::castFrom($this->path));
    }

    public function testCopyWhenTargetIsAFile()
    {
        $target = $this->base->withFile('file');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination should be a directory');
        $this->path->copyTo($target);
    }

    public function testCopyWhenTargetIsUnknownAndDoesNotExist()  // not a directory at the moment
    {
        $this->path->create();

        $this->path->withFile('file')->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->path->withDirectory('sub_dir')->withFile('/file')->create();

        $target = $this->base->with('unknown'); // gets an unknown path
        $this->path->copyTo($target);

        $target = $this->base->with('unknown'); // refresh to get a directory this time;

        $this->assertTrue($target->withFile('file')->exists());
        $this->assertTrue($target->withDirectory('sub_dir')->exists());
        $this->assertTrue($target->withDirectory('sub_dir')->withFile('file')->exists());
    }


    public function testCopyWhenTargetIsDirectoryAndExists()
    {
        $this->path->create();

        $this->path->withFile('file')->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->path->withDirectory('sub_dir')->withFile('/file')->create();

        $target = $this->base->withDirectory('target');
        $target->create();
        $this->path->copyTo($target);

        $this->assertTrue($target->withFile('file')->exists());
        $this->assertTrue($target->withDirectory('sub_dir')->exists());
        $this->assertTrue($target->withDirectory('sub_dir')->withFile('file')->exists());
    }

    public function testCopyFrom()
    {
        $source = Mockery::mock(RealPath::class);
        $source->shouldReceive('copyTo')->with($this->path);
        $this->path->copyFrom($source);
        $this->assertTrue(true); // errors picked up in mockery;
    }

    public function testDeleteWhenNonExistant()
    {
        $this->path->delete();
        $this->assertFalse($this->path->exists()); // should all be fine;
    }

    public function testSuccessfulDeleteWhenDirIsLink()
    {
        $this->filesystem->mkdir('/test/linked_dir');
        $this->filesystem->symlink('/test/linked_dir', '/test/testdir');
        $this->path->delete();
        $this->assertFalse($this->path->exists());
    }

    public function testUnsuccessfulDeleteWhenItExistAndContainsFilesAndDirectories()
    {
        $this->path->create();

        $this->path->withFile('file')->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->path->withDirectory('sub_dir')->withFile('/file')->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory is not empty (testdir)');

        $this->path->delete();
    }

    public function testSuccessfulDeleteWhenItExistAndContainsFilesAndDirectories()
    {
        $this->path->create();

        $this->path->withFile('file')->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->path->withDirectory('sub_dir')->withFile('/file')->create();

        $this->path->delete(true);
        $this->assertFalse($this->path->exists());
    }

    public function testUnsuccessfulDelete()
    {
        // mock the filesystem to force the error
        $filesystem = Mockery::mock(Memory::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testdir')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testdir')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test/testdir')->andReturn(true);
        $filesystem->shouldReceive('is_link')->with('/test/testdir')->andReturn(false);
        $filesystem->shouldReceive('scandir')->with('/test/testdir')->andReturn(['..','.']);
        $filesystem->shouldReceive('rmdir')->with('/test/testdir')->andReturn(false);

        $base = new Base('/test', $filesystem);
        $path = new Directory($base, 'testdir');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete directory (testdir)');
        $path->delete();
    }

    public function testListWhenDirDoesntExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory doesn\'t exist (testdir)');
        $this->path->list();
    }

    public function testListWhenScanDirFails()
    {
        // mock the filesystem to force the error
        $filesystem = Mockery::mock(Memory::class);
        $filesystem->shouldReceive('is_file')->with('/test')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test')->andReturn(true);
        $filesystem->shouldReceive('is_writable')->with('/test')->andReturn(true);

        $filesystem->shouldReceive('is_file')->with('/test/testdir')->andReturn(false);
        $filesystem->shouldReceive('file_exists')->with('/test/testdir')->andReturn(true);
        $filesystem->shouldReceive('scandir')->with('/test/testdir')->andReturn(false);

        $base = new Base('/test', $filesystem);
        $path = new Directory($base, 'testdir');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read from directory (testdir)');
        $path->list();
    }

    public function testGetFiles()
    {
        $this->path->create();

        $this->path->withFile('file1')->create();
        $this->path->withFile('file2')->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->path->withDirectory('sub_dir')->withFile('/file')->create();

        $files = $this->path->getFiles();
        $this->assertCount(2, $files);
        $this->assertInstanceOf(BasicFile::class, $files[0]);
        $this->assertInstanceOf(BasicFile::class, $files[1]);
        $this->assertEquals('/test/testdir/file1', $files[0]->getFullPath());
        $this->assertEquals('/test/testdir/file2', $files[1]->getFullPath());

        $files2 = $this->path->files;
        $this->assertEquals($files, $files2);
    }

    public function testGetJsonFiles()
    {
        $this->path->create();

        $this->path->withFile('file1.json')->create();
        $this->path->withFile('file2.json')->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->path->withDirectory('sub_dir')->withFile('/file.json')->create();

        $files = $this->path->getFiles();
        $this->assertCount(2, $files);
        $this->assertInstanceOf(JsonFile::class, $files[0]);
        $this->assertInstanceOf(JsonFile::class, $files[1]);
        $this->assertEquals('/test/testdir/file1.json', $files[0]->getFullPath());
        $this->assertEquals('/test/testdir/file2.json', $files[1]->getFullPath());

        $files2 = $this->path->files;
        $this->assertEquals($files, $files2);
    }

    public function testGetDirectories()
    {
        $this->path->create();

        $this->path->withFile('file1')->create();
        $this->path->withFile('file2')->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->path->withDirectory('sub_dir')->withFile('/file')->create();

        $dirs = $this->path->getDirectories();
        $this->assertCount(1, $dirs);
        $this->assertInstanceOf(Directory::class, $dirs[0]);
        $this->assertEquals('/test/testdir/sub_dir', $dirs[0]->getFullPath());

        $dirs2 = $this->path->directories;
        $this->assertEquals($dirs, $dirs2);
    }

    public function testList()
    {
        $this->path->create();

        $this->path->withFile('file1')->create();
        $this->path->withFile('file2')->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->path->withDirectory('sub_dir')->withFile('/file')->create();

        $paths = $this->path->list();
        $this->assertCount(3, $paths);
        $this->assertInstanceOf(BasicFile::class, $paths[0]);
        $this->assertInstanceOf(BasicFile::class, $paths[1]);
        $this->assertInstanceOf(Directory::class, $paths[2]);
        $this->assertEquals('/test/testdir/file1', $paths[0]->getFullPath());
        $this->assertEquals('/test/testdir/file2', $paths[1]->getFullPath());
        $this->assertEquals('/test/testdir/sub_dir', $paths[2]->getFullPath());
    }

    public function testCreateSubdirectory()
    {
        $subDir = $this->path->directory('sub_dir');
        $this->assertInstanceOf(Directory::class, $subDir);
        $this->assertEquals('/test/testdir/sub_dir', $subDir->getFullPath());
    }

    public function testCreateSubdirectoryWhenUnknown()
    {
        $this->filesystem->mkdir('/test/testdir/sub_dir', 0777, true);
        $subDir = $this->path->unknown('sub_dir');
        $this->assertInstanceOf(Directory::class, $subDir);
        $this->assertEquals('/test/testdir/sub_dir', $subDir->getFullPath());
    }

    public function testCreateSubfile()
    {
        $subFile = $this->path->file('sub_file');
        $this->assertInstanceOf(BasicFile::class, $subFile);
        $this->assertEquals('/test/testdir/sub_file', $subFile->getFullPath());
    }

    public function testCreateSubfileWhenUnknown()
    {
        $this->filesystem->mkdir('/test/testdir');
        $this->filesystem->touch('/test/testdir/sub_file');
        $subFile = $this->path->unknown('sub_file');
        $this->assertInstanceOf(BasicFile::class, $subFile);
        $this->assertEquals('/test/testdir/sub_file', $subFile->getFullPath());
    }

    public function testCreateSubUnknownWhenUnknown()
    {
        $subUnknown = $this->path->unknown('sub_unknown');
        $this->assertInstanceOf(Unknown::class, $subUnknown);
        $this->assertEquals('/test/testdir/sub_unknown', $subUnknown->getFullPath());
    }

    public function testIfDirectory()
    {
        $this->assertTrue($this->path->isDirectory());
    }

    public function testIfEmptyWhenEmpty()
    {
        $this->path->create();
        $this->assertTrue($this->path->isEmpty());
        $this->assertFalse($this->path->isNotEmpty());
    }

    public function testIfEmptyWhenHasFiles()
    {
        $this->path->create();
        $this->path->withFile('file')->create();
        $this->assertFalse($this->path->isEmpty());
        $this->assertTrue($this->path->isNotEmpty());
    }

    public function testIfEmptyWhenHasSubdirectories()
    {
        $this->path->create();
        $this->path->withDirectory('sub_dir')->create();
        $this->assertFalse($this->path->isEmpty());
        $this->assertTrue($this->path->isNotEmpty());
    }

    public function testCreationOfEmptySubPath()
    {
        $this->assertSame($this->path, $this->path->with(''));
        $this->assertSame($this->path, $this->path->with('/'));
    }

    public function testCreationOfNestedSubPath()
    {
        $subUnknown = $this->path->with('dir/sub_unknown');
        $this->assertSame('/test/testdir/dir/sub_unknown', $subUnknown->getFullPath());
        $this->assertFalse($subUnknown->isDirectory());
        $this->assertFalse($subUnknown->isFile());
        $this->assertTrue($subUnknown->getParent()->isDirectory());
    }

    public function testCreationOfNestedFile()
    {
        $subUnknown = $this->path->withFile('dir/sub_unknown');
        $this->assertSame('/test/testdir/dir/sub_unknown', $subUnknown->getFullPath());
        $this->assertFalse($subUnknown->isDirectory());
        $this->assertTrue($subUnknown->isFile());
        $this->assertTrue($subUnknown->getParent()->isDirectory());
    }

    public function testCreationOfNestedFileWhichIsAlreadyAFile()
    {
        $this->filesystem->mkdir('/test/testdir/dir',0777,true);
        $this->filesystem->touch('/test/testdir/dir/sub_unknown');

        $subUnknown = $this->path->withFile('dir/sub_unknown');
        $this->assertSame('/test/testdir/dir/sub_unknown', $subUnknown->getFullPath());
        $this->assertFalse($subUnknown->isDirectory());
        $this->assertTrue($subUnknown->isFile());
        $this->assertTrue($subUnknown->getParent()->isDirectory());
    }

    public function testCreationOfNestedFileWhichIsAlreadyADirectory()
    {
        $this->filesystem->mkdir('/test/testdir/dir/sub_unknown',0777,true);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cast a directory to a file');
        $subUnknown = $this->path->withFile('dir/sub_unknown');
    }

    public function testCreationOfNestedDirectory()
    {
        $subUnknown = $this->path->withDirectory('dir/sub_unknown');
        $this->assertSame('/test/testdir/dir/sub_unknown', $subUnknown->getFullPath());
        $this->assertTrue($subUnknown->isDirectory());
        $this->assertFalse($subUnknown->isFile());
        $this->assertTrue($subUnknown->getParent()->isDirectory());
    }

    public function testCreationOfNestedDirectoryWhichIsAlreadyADirectory()
    {
        $this->filesystem->mkdir('/test/testdir/dir/sub_unknown',0777,true);
        $subUnknown = $this->path->withDirectory('dir/sub_unknown');
        $this->assertSame('/test/testdir/dir/sub_unknown', $subUnknown->getFullPath());
        $this->assertTrue($subUnknown->isDirectory());
        $this->assertFalse($subUnknown->isFile());
        $this->assertTrue($subUnknown->getParent()->isDirectory());
    }

    public function testCreationOfNestedDirectoryWhichIsAlreadyAFile()
    {
        $this->filesystem->mkdir('/test/testdir/dir',0777,true);
        $this->filesystem->touch('/test/testdir/dir/sub_unknown');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cast a file to a directory');
        $subUnknown = $this->path->withDirectory('dir/sub_unknown');
    }

    public function testWithGlobName()
    {
        $glob = $this->path->with('*');
        $this->assertInstanceOf(Glob::class, $glob);
        $this->assertEquals('/test/testdir/*', $glob->getFullPath());
    }



}
