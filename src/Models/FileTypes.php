<?php

namespace Gidato\Filesystem\Models;

use InvalidArgumentException;

/**
 * FileTypes
 *
 * Keeps track of file classes and how to determine them based on the filename suffix
 *
 * This is a singleton - not nice I know.
 * However, to make this service available in several framworks, not much else can be relied upon
 * while allowing someone to extend the file types available
 *
 */

class FileTypes
{
    public const APPEND = false;
    public const PREPEND = true;

    private $fileTypes = [
        [ '.json' => JsonFile::class ],
    ];

    public function addType(string $suffix, string $class, bool $location = self::PREPEND) : void
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class {$class} does not exist");
        }

        foreach ($this->fileTypes as $relationship) {
            $existingSuffix = key($relationship);
            if ($existingSuffix == $suffix) {
                throw new InvalidArgumentException("Suffix '{$suffix}' already set up");
            }
        }

        $relationship = [$suffix => $class];
        if ($location == self::PREPEND) {
            array_unshift($this->fileTypes, $relationship);
        } else {
            array_push($this->fileTypes, $relationship);
        }
    }

    public function replaceType(string $suffix, string $class) : void
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class {$class} does not exist");
        }

        foreach ($this->fileTypes as $key => $relationship) {
            $existingSuffix = key($relationship);
            if ($existingSuffix == $suffix) {
                $this->fileTypes[$key] = [$suffix => $class];
                return;
            }
        }

        throw new InvalidArgumentException("Suffix '{$suffix}' has not been set up");
    }


    public function getFileClassForName(string $filename) : string
    {
        foreach ($this->fileTypes as $relationship) {
            $suffix = key($relationship);
            $class = $relationship[$suffix];

            if (substr($filename, -strlen($suffix)) == $suffix) {
                return $class;
            }
        }
        return BasicFile::class;
    }
}
