<?php

namespace Gidato\Filesystem\Model;

use App\Model\Base\Base;
use App\Support\Service\Filesystem;
use Tightenco\Collect\Support\Collection;
use RuntimeException;
use InvalidArgumentException;

abstract class RealPath extends Path
{
    private $parent;

    protected function setParent(Directory $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): Directory
    {
        return $this->parent;
    }

    protected function validateName(string $name): void
    {
        if (preg_match('/^\.\?$/', $name)) {
            throw new InvalidArgumentException('Path name cannot be . or ..');
        }

        if (strpbrk($name, "/%:|\"<>?*\\") !== false) {
            throw new InvalidArgumentException('Invalid characters in path');
        }
    }

    public function isReadOnly(): bool
    {
        return
            $this->getParent()->isReadOnly() ||
            (
                $this->getFilesystem()->file_exists($this->getFullPath()) &&
                !$this->getFilesystem()->is_writable($this->getFullPath())
            );
    }


    public function exists(): bool
    {
        return $this->getFilesystem()->file_exists($this->getFullPath());
    }

    public function linkTo(RealPath $target): void
    {
        if ($this->exists()) {
            throw new RuntimeException(sprintf(
                'Destination of link (%s) already exists',
                (string) $this->getPath()
            ));
        }

        if (!$target->exists()) {
            throw new RuntimeException(sprintf(
                'Target of link (%s) does not exist',
                (string) $target->getPath()
            ));
        }

        if (
            false === $this->getFilesystem()->symlink(
                $target->getFullPath(),
                $this->getFullPath()
            )
        ) {
            throw new RuntimeException(sprintf(
                'Failed to create link from %s to %s',
                (string) $this->getPath(),
                (string) $target->getPath()
            ));
        }
    }

    public function linkFrom(RealPath $source): void
    {
        $source->linkTo($this);
    }

    public function isLink(): bool
    {
        return $this->getFilesystem()->is_link($this->getFullPath());
    }

    public function getLinkTarget(): RealPath
    {
        if (!$this->isLink()) {
            throw new RuntimeException(sprintf('Path is not a link (%s)', (string) $this->getPath()));
        }

        if (($target = $this->getFilesystem()->readlink($this->getFullPath())) === false) {
            throw new RuntimeException(sprintf('Failed to read link for %s', (string) $this->getPath()));
        }

        return $this->getBase()->with($target);
    }

    public function unlink(): void
    {
        if (!$this->isLink() && $this->exists()) {
            throw new RuntimeException(sprintf('Path is not a link (%s)', (string) $this->getPath()));
        }

        if (!$this->isLink()) {
            return;
        }

        if (false === $this->getFilesystem()->unlink($this->getFullPath())) {
            throw new RuntimeException(sprintf(
                'Failed to remove link (%s)',
                (string) $this->getPath()
            ));
        }
    }

    public function __toString()
    {
        return (string) $this->getName();
    }

    public function isDirectory(): bool
    {
        return false;
    }

    public function isFile(): bool
    {
        return false;
    }

    public function hasParent(): bool
    {
        return true;
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
