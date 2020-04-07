<?php

namespace Gidato\Filesystem\Models;

use RuntimeException;
use InvalidArgumentException;

class BasicFile extends File
{
    public function __construct(Directory $parent, string $name)
    {
        $this->validateName($name);
        $this->setParent($parent);
        $this->setName($name);
        $this->validatePathIsNotDirectory();
    }

    public function create(): void
    {
        if ($this->exists()) {
            throw new RuntimeException(sprintf(
                'File exists (%s)',
                (string) $this->getPath()
            ));
        }

        $this->setContents('');
    }

    public function getContents(): string
    {
        if (!$this->exists()) {
            throw new RuntimeException(sprintf(
                'File does not exist (%s)',
                (string) $this->getPath()
            ));
        }

        $contents = $this->getFilesystem()->file_get_contents($this->getFullPath());
        if (false === $contents) {
            throw new RuntimeException(sprintf(
                'Failed to read from file (%s)',
                (string) $this->getPath()
            ));
        }
        return $contents;
    }

    public function setContents(string $contents): void
    {
        $this->fileWrite($contents, false);
    }

    public function appendContents(string $contents): void
    {
        $this->fileWrite($contents, true);
    }

    private function fileWrite(string $contents, bool $append): void
    {
        if ($this->isReadOnly()) {
            throw new RuntimeException(sprintf(
                'File is read only (%s)',
                (string) $this->getPath()
            ));
        }

        $flags = $append ? FILE_APPEND : 0;

        if (!$this->getParent()->exists()) {
            $this->getParent()->create();
        }

        if (false === $this->getFilesystem()->file_put_contents($this->getFullPath(), $contents, $flags)) {
            throw new RuntimeException(sprintf(
                'Failed to write to file (%s)',
                (string) $this->getPath()
            ));
        }
    }
}
