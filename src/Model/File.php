<?php

namespace Gidato\Filesystem\Model;

use RuntimeException;
use InvalidArgumentException;

abstract class File extends RealPath
{
    protected function validatePathIsNotDirectory(): void
    {
        if ($this->getFilesystem()->is_dir($this->getFullPath())) {
            throw new InvalidArgumentException('Path is a directory - cannot be used as a file');
        }
    }

    public static function castFrom(RealPath $path): File
    {
        if ($path->isDirectory()) {
            throw new InvalidArgumentException('Cannot cast a directory to a file');
        }

        if (!$path instanceof static) {
            $path = new static($path->getParent(), $path->getName());
        }

        return $path;
    }

    public function copyTo(RealPath $target): void
    {
        if ($target->isFile()) {
            throw new InvalidArgumentException('Destination should be a directory');
        }

        if ($target->isReadOnly()) {
            throw new InvalidArgumentException('Destination is read only');
        }

        if (!$target->isDirectory()) {
            $target = Directory::castFrom($target);
        }

        if (!$target->exists()) {
            $target->create();
        }

        if (
            false === $this->getFilesystem()->copy(
                $this->getFullPath(),
                $target->getFullPath() . '/' . $this->getName()
            )
        ) {
            throw new RuntimeException(sprintf(
                'Failed to copy from %s to %s',
                (string) $this->getPath(),
                (string) $target->getPath()
            ));
        }
    }

    public function delete(bool $force = false): void
    {
        if (!$this->exists()) {
            return;
        }

        if ($this->isReadOnly() && !$force) {
            throw new RuntimeException(sprintf(
                'File cannot be deleted, read only (%s)',
                (string) $this->getPath()
            ));
        }

        if (false === $this->getFilesystem()->unlink($this->getFullPath())) {
            throw new RuntimeException(sprintf(
                'Failed to delete file (%s)',
                (string) $this->getPath()
            ));
        }
    }

    public function getSize() : ?int
    {
        return $this->exists()
            ? $this->getFilesystem()->filesize($this->getFullPath())
            : null;
    }

    public function isFile(): bool
    {
        return true;
    }

    /**
     * allow direct access via simulated property to some fields
     */
    public function __get($name)
    {
        $fields = ['contents', 'size'];
        if (in_array($name, $fields)) {
            $method = 'get' . ucfirst($name);
            return $this->$method();
        }

        return parent::__get($name);
    }
}
