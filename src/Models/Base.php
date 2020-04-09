<?php

namespace Gidato\Filesystem\Model;

use Gidato\Filesystem\Filesystem;
use Gidato\Filesystem\Disk;

use InvalidArgumentException;

class Base extends Directory
{
    private $filesystem;
    private $fileTypesHandler;
    private $baseDirectory;
    private $readOnly = false;

    public function __construct(string $path, ?Filesystem $filesystem = null, ?FileTypes $fileTypesHandler = null)
    {
        $this->setBaseDirectory($path);
        $this->setFilesystem($filesystem ?? new Disk());
        $this->setFileTypesHandler($fileTypesHandler ?? new FileTypes());
        $this->validatePathIsNotFile();
    }

    protected function setBaseDirectory(string $baseDirectory): void
    {
        $this->baseDirectory = rtrim($baseDirectory, '/');
    }

    public function getBase(): Base
    {
        return $this;
    }

    protected function setFilesystem(Filesystem $filesystem): void
    {
        $this->filesystem = $filesystem;
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    protected function setFileTypesHandler(FileTypes $fileTypesHandler): void
    {
        $this->fileTypesHandler = $fileTypesHandler;
    }

    public function getFileTypesHandler(): FileTypes
    {
        return $this->fileTypesHandler;
    }

    protected function setReadOnly(): void
    {
        $this->readOnly = true;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly || !$this->filesystem->is_writable($this->getFullPath());
    }

    public function getPath(): string
    {
        return '/';
    }

    public function getFullPath(): string
    {
        return $this->baseDirectory;
    }

    public function hasParent(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return '/';
    }


    public function __toString()
    {
        return $this->getName();
    }

    /**
     * generates the path structure from a string passed in
     * added from the base point.
     */
    public function with(string $path): Path
    {
        if (substr($path, 0, strlen($this->baseDirectory) + 1) == $this->baseDirectory . '/') {
            $path = substr($path, strlen($this->baseDirectory) + 1);
        }

        return parent::with($path);
    }
}
