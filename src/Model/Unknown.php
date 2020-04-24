<?php

namespace Gidato\Filesystem\Model;

class Unknown extends RealPath
{
    public function __construct(Directory $parent, string $name)
    {
        $this->validateName($name);
        $this->setParent($parent);
        $this->setName($name);
    }

    public function diff(Path $comparison) : bool
    {
        return get_class($comparison) != get_class($this);
    }
}
