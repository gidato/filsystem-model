<?php

namespace Gidato\Filesystem\Model;

use Gidato\Filesystem\Filesystem;
use Tightenco\Collect\Support\Collection;
use RuntimeException;
use InvalidArgumentException;

abstract class Path
{
    private $name;

    public function getBase(): Base
    {
        return $this->getParent()->getBase();
    }

    public function getFilesystem(): Filesystem
    {
        return $this->getParent()->getFilesystem();
    }

    public function getFileTypesHandler(): FileTypes
    {
        return $this->getParent()->getFileTypesHandler();
    }

    public function getPath(): string
    {
        return ltrim($this->getParent()->getPath() . '/' . $this->getName(), '/');
    }

    public function getFullPath(): string
    {
        return $this->getParent()->getFullPath() . '/' . $this->getName();
    }

    public function hasParent(): bool
    {
        return true;
    }

    protected function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * allow direct access via simulated property to some fields
     */
    public function __get($name)
    {
        $fields = ['base', 'name', 'path', 'fullPath'];
        if (in_array($name, $fields)) {
            $method = 'get' . ucfirst($name);
            if (method_exists($this, $method)) {
                return $this->$method();
            }
        }

        throw new RuntimeException('No property available named ' . $name);
    }
}
