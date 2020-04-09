# Gidato / Filesystem-Model

Converts the file system to simple node objects, and limits access within tree from a set base

## Installation
```

composer require gidato/filesystem-model

```

## Example Use

```php
<?php

use Gidato\Filesystem\Model\Base;
$base = new Base('/test/dir');
$directory = $base->with('sample');
$directory->create();


use Gidato\Filesystem\Model\ReadOnlyBase;
$base = new Base('/test/read_only_dir');
$base->isReadOnly() === true;

```

## Useful Information

Note that all files and directories must have a **Base** parent.  This can be a **ReadOnlyBase** parent, but must exist to limit file access.

Within the base, you can have directories and files. Files can be a **BasicFile**, ie anything, or a **JsonFile**.  Others can be added to the **FileTypes** handler which can be passed into the Base, or can be obtained from the base if not set at creation.

eg.

```php
<?php

$base->getFileTypesHandler()->addType('.xslx', ExcelFile::class);

```

Also, **Glob** paths can be created under any directory, including **Base**.

Everything is a **Path**

**RealPath** includes everything except **Glob**


## Interfaces, Classes and Methods
