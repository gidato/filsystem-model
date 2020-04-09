<?php

namespace Gidato\Filesystem\Model;

use Tightenco\Collect\Support\Collection;
use RuntimeException;
use InvalidArgumentException;

class Glob extends Path implements GlobParent
{
    private $parent;

    public function __construct(GlobParent $parent, string $name)
    {
        $this->validateName($name);
        $this->setParent($parent);
        $this->setName($name);
    }

    protected function validateName(string $name): void
    {
        if (strpbrk($name, "/%:|\"<>") !== false) {
            throw new InvalidArgumentException('Invalid characters in path');
        }
    }

    protected function setParent(GlobParent $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): GlobParent
    {
        return $this->parent;
    }

    public function with(string $path): Glob
    {
        $path = trim($path, '/');
        if (empty($path)) {
            return $this;
        }

        $parts = explode('/', $path);
        $first = array_shift($parts);

        $glob = new self($this, $first);

        if (count($parts)) {
            return $glob->with(implode('/', $parts));
        }

        return $glob;
    }

    public function glob(): array
    {
        if (false === ($matching = $this->getFilesystem()->glob($this->getFullPath(), GLOB_NOSORT))) {
            throw new RuntimeException(sprintf(
                'Failed to glob file path (%s)',
                (string) $this->getPath() ?: (string) $this->getFullPath()
            ));
        }

        $matching = new Collection($matching);
        return $matching
            ->map(function ($path) {
                return $this->getBase()->with($path);
            })
            ->all();
    }

    public function __toString()
    {
        return (string) $this->getPath();
    }

    /**
     * allow direct access via simulated property to some fields
     */
    public function __get($name)
    {
        $fields = ['parent'];
        if (in_array($name, $fields)) {
            $method = 'get' . ucfirst($name);
            if (method_exists($this, $method)) {
                return $this->$method();
            }
        }

        return parent::__get($name);
    }
}
