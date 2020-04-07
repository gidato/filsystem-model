<?php

namespace Gidato\Filesystem\Models;

use RuntimeException;
use InvalidArgumentException;

class JsonFile extends File
{
    public function __construct(Directory $parent, string $name)
    {
        $this->validateName($name);
        $this->setParent($parent);
        $this->setName($name);
        $this->validatePathIsNotDirectory();
    }

    protected function validateName(string $name): void
    {
        parent::validateName($name);
        if ('.json' != substr($name, -5)) {
            throw new InvalidArgumentException('extension must be .json');
        }
    }

    public function create(): void
    {
        if ($this->exists()) {
            throw new RuntimeException(sprintf(
                'File exists (%s)',
                (string) $this->getPath()
            ));
        }

        $this->setContents([]);
    }

    public function getContents(): array
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

        return $this->decode($contents);
    }

    public function setContents(array $contents): void
    {
        if ($this->isReadOnly()) {
            throw new RuntimeException(sprintf(
                'File is read only (%s)',
                (string) $this->getPath()
            ));
        }

        if (!$this->getParent()->exists()) {
            $this->getParent()->create();
        }

        $contents = $this->encode($contents);

        if (false === $this->getFilesystem()->file_put_contents($this->getFullPath(), $contents)) {
            throw new RuntimeException(sprintf(
                'Failed to write to file (%s)',
                (string) $this->getPath()
            ));
        }
    }

    protected function encode(array $contents): string
    {
        return json_encode($contents);
    }

    protected function decode($contents): array
    {
        return json_decode($contents, true) ?? [];
    }
}
