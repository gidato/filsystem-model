<?php

namespace Gidato\Filesystem\Model;

use Gidato\Filesystem\Filesystem;
use RuntimeException;
use InvalidArgumentException;

interface GlobParent
{
    public function getFilesystem() : Filesystem;
    public function getPath() : string;
    public function getBase() : Base;
}
