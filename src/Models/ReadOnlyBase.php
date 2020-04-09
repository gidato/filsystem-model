<?php

namespace Gidato\Filesystem\Model;

use Gidato\Filesystem\Filesystem;

use InvalidArgumentException;

class ReadOnlyBase extends Base
{
    public function __construct(string $path, ?Filesystem $filesystem = null )
    {
        parent::__construct($path, $filesystem);
        $this->setReadOnly(true);
    }
}
