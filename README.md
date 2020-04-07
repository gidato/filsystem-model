# Gidato / Filesystem-Objects

Converts the file system to simple node objects, and limits access within tree from a set base

## Installation
```

composer require gidato/filesystem-objects

```

## Example Use

```php
<?php

use Gidato\Filesystem\Models\Base;
$base = new Base('/test/dir');
$directory = $base->with('sample');
$directory->create();


use Gidato\Filesystem\Models\ReadOnlyBase;
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


```php
<?php
namespace Gidato\Filesystem\Models;

Class Base extends Directory implements GlobParent {

  /* Methods */
  public  __construct(string $path, ?Gidato\Filesystem\Filesystem $filesystem = , ?FileTypes $fileTypesHandler = );
  public  getBase();
  public  getFilesystem();
  public  getFileTypesHandler();
  public  isReadOnly();
  public  getPath();
  public  getFullPath();
  public  hasParent();
  public  getName();
  public  __toString();
  public  with(string $path);

  /* Inherited Methods */
  public static  Directory::castFrom(RealPath $path);
  public  Directory::create();
  public  Directory::copyTo(RealPath $target);
  public  Directory::copyFrom(RealPath $source);
  public  Directory::empty(bool $force = );
  public  Directory::isEmpty();
  public  Directory::isNotEmpty();
  public  Directory::delete(bool $force = );
  public  Directory::getFiles();
  public  Directory::getDirectories();
  public  Directory::list();
  public  Directory::directory(string $name);
  public  Directory::file(string $name);
  public  Directory::unknown(string $name);
  public  Directory::isDirectory();
  public  Directory::withFile(string $path);
  public  Directory::withDirectory(string $path);
  public  RealPath::getParent();
  public  RealPath::exists();
  public  RealPath::linkTo(RealPath $target);
  public  RealPath::linkFrom(RealPath $source);
  public  RealPath::isLink();
  public  RealPath::getLinkTarget();
  public  RealPath::unlink();
  public  RealPath::isFile();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Class BasicFile extends File {

  /* Methods */
  public  __construct(Directory $parent, string $name);
  public  create();
  public  getContents();
  public  setContents(string $contents);
  public  appendContents(string $contents);

  /* Inherited Methods */
  public static  File::castFrom(RealPath $path);
  public  File::copyTo(RealPath $target);
  public  File::delete(bool $force = );
  public  File::isFile();
  public  RealPath::getParent();
  public  RealPath::isReadOnly();
  public  RealPath::exists();
  public  RealPath::linkTo(RealPath $target);
  public  RealPath::linkFrom(RealPath $source);
  public  RealPath::isLink();
  public  RealPath::getLinkTarget();
  public  RealPath::unlink();
  public  RealPath::__toString();
  public  RealPath::isDirectory();
  public  RealPath::hasParent();
  public  Path::getBase();
  public  Path::getFilesystem();
  public  Path::getFileTypesHandler();
  public  Path::getPath();
  public  Path::getFullPath();
  public  Path::getName();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Class Directory extends RealPath implements GlobParent {

  /* Methods */
  public  __construct(Directory $parent, string $name);
  public static  castFrom(RealPath $path);
  public  create();
  public  copyTo(RealPath $target);
  public  copyFrom(RealPath $source);
  public  empty(bool $force = );
  public  isEmpty();
  public  isNotEmpty();
  public  delete(bool $force = );
  public  getFiles();
  public  getDirectories();
  public  list();
  public  directory(string $name);
  public  file(string $name);
  public  unknown(string $name);
  public  isDirectory();
  public  with(string $path);
  public  withFile(string $path);
  public  withDirectory(string $path);

  /* Inherited Methods */
  public  RealPath::getParent();
  public  RealPath::isReadOnly();
  public  RealPath::exists();
  public  RealPath::linkTo(RealPath $target);
  public  RealPath::linkFrom(RealPath $source);
  public  RealPath::isLink();
  public  RealPath::getLinkTarget();
  public  RealPath::unlink();
  public  RealPath::__toString();
  public  RealPath::isFile();
  public  RealPath::hasParent();
  public  Path::getBase();
  public  Path::getFilesystem();
  public  Path::getFileTypesHandler();
  public  Path::getPath();
  public  Path::getFullPath();
  public  Path::getName();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Abstract Class File extends RealPath {

  /* Methods */
  public static  castFrom(RealPath $path);
  public  copyTo(RealPath $target);
  public  delete(bool $force = );
  public  isFile();

  /* Inherited Methods */
  public  RealPath::getParent();
  public  RealPath::isReadOnly();
  public  RealPath::exists();
  public  RealPath::linkTo(RealPath $target);
  public  RealPath::linkFrom(RealPath $source);
  public  RealPath::isLink();
  public  RealPath::getLinkTarget();
  public  RealPath::unlink();
  public  RealPath::__toString();
  public  RealPath::isDirectory();
  public  RealPath::hasParent();
  public  Path::getBase();
  public  Path::getFilesystem();
  public  Path::getFileTypesHandler();
  public  Path::getPath();
  public  Path::getFullPath();
  public  Path::getName();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Class FileTypes {

  /* Methods */
  public  addType(string $suffix, string $class, bool $location = 1);
  public  replaceType(string $suffix, string $class);
  public  getFileClassForName(string $filename);
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Class Glob extends Path implements GlobParent {

  /* Methods */
  public  __construct(GlobParent $parent, string $name);
  public  getParent();
  public  with(string $path);
  public  glob();
  public  __toString();

  /* Inherited Methods */
  public  Path::getBase();
  public  Path::getFilesystem();
  public  Path::getFileTypesHandler();
  public  Path::getPath();
  public  Path::getFullPath();
  public  Path::hasParent();
  public  Path::getName();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Interface GlobParent {

  /* Methods */
  public  getFilesystem();
  public  getPath();
  public  getBase();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Class JsonFile extends File {

  /* Methods */
  public  __construct(Directory $parent, string $name);
  public  create();
  public  getContents();
  public  setContents(array $contents);

  /* Inherited Methods */
  public static  File::castFrom(RealPath $path);
  public  File::copyTo(RealPath $target);
  public  File::delete(bool $force = );
  public  File::isFile();
  public  RealPath::getParent();
  public  RealPath::isReadOnly();
  public  RealPath::exists();
  public  RealPath::linkTo(RealPath $target);
  public  RealPath::linkFrom(RealPath $source);
  public  RealPath::isLink();
  public  RealPath::getLinkTarget();
  public  RealPath::unlink();
  public  RealPath::__toString();
  public  RealPath::isDirectory();
  public  RealPath::hasParent();
  public  Path::getBase();
  public  Path::getFilesystem();
  public  Path::getFileTypesHandler();
  public  Path::getPath();
  public  Path::getFullPath();
  public  Path::getName();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Abstract Class Path {

  /* Methods */
  public  getBase();
  public  getFilesystem();
  public  getFileTypesHandler();
  public  getPath();
  public  getFullPath();
  public  hasParent();
  public  getName();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Class ReadOnlyBase extends Base implements GlobParent {

  /* Methods */
  public  __construct(string $path, ?Gidato\Filesystem\Filesystem $filesystem = );

  /* Inherited Methods */
  public  Base::getBase();
  public  Base::getFilesystem();
  public  Base::getFileTypesHandler();
  public  Base::isReadOnly();
  public  Base::getPath();
  public  Base::getFullPath();
  public  Base::hasParent();
  public  Base::getName();
  public  Base::__toString();
  public  Base::with(string $path);
  public static  Directory::castFrom(RealPath $path);
  public  Directory::create();
  public  Directory::copyTo(RealPath $target);
  public  Directory::copyFrom(RealPath $source);
  public  Directory::empty(bool $force = );
  public  Directory::isEmpty();
  public  Directory::isNotEmpty();
  public  Directory::delete(bool $force = );
  public  Directory::getFiles();
  public  Directory::getDirectories();
  public  Directory::list();
  public  Directory::directory(string $name);
  public  Directory::file(string $name);
  public  Directory::unknown(string $name);
  public  Directory::isDirectory();
  public  Directory::withFile(string $path);
  public  Directory::withDirectory(string $path);
  public  RealPath::getParent();
  public  RealPath::exists();
  public  RealPath::linkTo(RealPath $target);
  public  RealPath::linkFrom(RealPath $source);
  public  RealPath::isLink();
  public  RealPath::getLinkTarget();
  public  RealPath::unlink();
  public  RealPath::isFile();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Abstract Class RealPath extends Path {

  /* Methods */
  public  getParent();
  public  isReadOnly();
  public  exists();
  public  linkTo(RealPath $target);
  public  linkFrom(RealPath $source);
  public  isLink();
  public  getLinkTarget();
  public  unlink();
  public  __toString();
  public  isDirectory();
  public  isFile();
  public  hasParent();

  /* Inherited Methods */
  public  Path::getBase();
  public  Path::getFilesystem();
  public  Path::getFileTypesHandler();
  public  Path::getPath();
  public  Path::getFullPath();
  public  Path::getName();
}
```



```php
<?php
namespace Gidato\Filesystem\Models;

Class Unknown extends RealPath {

  /* Methods */
  public  __construct(Directory $parent, string $name);

  /* Inherited Methods */
  public  RealPath::getParent();
  public  RealPath::isReadOnly();
  public  RealPath::exists();
  public  RealPath::linkTo(RealPath $target);
  public  RealPath::linkFrom(RealPath $source);
  public  RealPath::isLink();
  public  RealPath::getLinkTarget();
  public  RealPath::unlink();
  public  RealPath::__toString();
  public  RealPath::isDirectory();
  public  RealPath::isFile();
  public  RealPath::hasParent();
  public  Path::getBase();
  public  Path::getFilesystem();
  public  Path::getFileTypesHandler();
  public  Path::getPath();
  public  Path::getFullPath();
  public  Path::getName();
}
```

