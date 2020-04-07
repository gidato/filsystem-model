<?php

namespace Gidato\Filesystem\Models;

use Tightenco\Collect\Support\Collection;
use RuntimeException;
use InvalidArgumentException;
use Throwable;

class Directory extends RealPath implements GlobParent
{
    public function __construct(Directory $parent, string $name)
    {
        $this->validateName($name);
        $this->setParent($parent);
        $this->setName($name);
        $this->validatePathIsNotFile();
    }

    protected function validatePathIsNotFile(): void
    {
        if ($this->getFilesystem()->is_file($this->getFullPath())) {
            throw new InvalidArgumentException('Path is a file - cannot be used as a directory');
        }
    }

    public static function castFrom(RealPath $path): Directory
    {
        if ($path->isFile()) {
            throw new InvalidArgumentException('Cannot cast a file to a directory');
        }

        if (!$path instanceof static) {
            $path = new static($path->getParent(), $path->getName());
        }

        return $path;
    }

    public function create(): void
    {
        if ($this->exists()) {
            throw new RuntimeException(sprintf(
                'Directory exists (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }

        if ($this->isReadOnly()) {
            throw new RuntimeException(sprintf(
                'Directory is read only (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }

        if (false === $this->getFilesystem()->mkdir($this->getFullPath(), 0777, true)) {
            throw new RuntimeException(sprintf(
                'Failed to create directory (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }
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

        foreach ($this->getFiles() as $file) {
            $file->copyTo($target);
        }

        foreach ($this->getDirectories() as $directory) {
            $directory->copyTo($target->directory($directory->getName()));
        }
    }

    public function copyFrom(RealPath $source): void
    {
        $source->copyTo($this);
    }

    public function empty(bool $force = false): void
    {
        foreach ($this->list() as $path) {
            $path->delete($force);
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->list());
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function delete(bool $force = false): void
    {
        if (!$this->exists()) {
            return;
        }

        if (!$this->hasParent()) {
            throw new RuntimeException('Cannot delete the base');
        }

        if ($this->isReadOnly()) {
            throw new RuntimeException(sprintf(
                'Directory is read only (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }

        if ($this->isLink()) {
            $this->unlink();
            return;
        }

        if ($this->isNotEmpty() && !$force) {
            throw new RuntimeException(sprintf(
                'Directory is not empty (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }

        $this->empty($force);

        if (false === $this->getFilesystem()->rmdir($this->getFullPath())) {
            throw new RuntimeException(sprintf(
                'Failed to delete directory (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }
    }

    public function getFiles(): array
    {
        return (new Collection($this->list()))
            ->filter(function ($path) {
                return $path->isFile();
            })
            ->values()
            ->all();
    }

    public function getDirectories(): array
    {
        return (new Collection($this->list()))
            ->filter(function ($path) {
                return $path->isDirectory();
            })
            ->values()
            ->all();
    }

    public function list() : array
    {
        if (!$this->exists()) {
            throw new RuntimeException(sprintf(
                'Directory doesn\'t exist (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }

        if (false === ($filesAndDirectoryNames = $this->getFilesystem()->scandir($this->getFullPath()))) {
            throw new RuntimeException(sprintf(
                'Failed to read from directory (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }

        return (new Collection($filesAndDirectoryNames))
            ->diff(['.','..'])
            ->map(function ($name) {
                return $this->unknown($name);
            })
            ->values()
            ->all();
    }

    public function directory(string $name): Directory
    {
        return new Directory($this, $name);
    }

    public function file(string $name): File
    {
        return $this->generateFileFromPath(new Unknown($this, $name));
    }

    public function unknown(string $name): RealPath
    {
        $unknown = new Unknown($this, $name);

        if ($this->getFilesystem()->is_dir($unknown->getFullPath())) {
            return Directory::castFrom($unknown);
        }

        if ($this->getFilesystem()->is_file($unknown->getFullPath())) {
            return $this->generateFileFromPath($unknown);
        }

        return $unknown;
    }

    private function generateFileFromPath(RealPath $unknown): File
    {
        $fileClass = $this->getFileTypesHandler()->getFileClassForName($unknown->name);
        return $fileClass::castFrom($unknown);
    }

    public function isDirectory(): bool
    {
        return true;
    }

    /**
     * generates the path structure from a string passed in
     * added to the structure already here.
     */
    public function with(string $path): Path
    {
        $path = trim($path, '/');
        if (empty($path)) {
            return $this;
        }

        $parts = explode('/', $path);
        $first = array_shift($parts);

        try {
            $node = $this->unknown($first);
            if (count($parts)) {
                $node = Directory::castFrom($node);
            }
        } catch (Throwable $e) {
            if ($e->getMessage() == 'Invalid characters in path') {
                // see if it is a glob
                $node = new Glob($this, $first);
            } else {
                throw $e;
            }
        }

        if (count($parts)) {
            return $node->with(implode('/', $parts));
        }

        return $node;
    }

    public function withFile(string $path): File
    {
        return $this->generateFileFromPath($this->with($path));
    }

    public function withDirectory(string $path): Directory
    {
        $path = $this->with($path);
        return Directory::castFrom($path);
    }

    /**
     * allow direct access via simulated property to some fields
     */
    public function __get($name)
    {
        $fields = ['files', 'directories'];
        if (in_array($name, $fields)) {
            $method = 'get' . ucfirst($name);
            return $this->$method();
        }

        return parent::__get($name);
    }
}
