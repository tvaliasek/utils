<?php

namespace Tvaliasek\Utils;

/**
 * Various utility functions
 *
 * @author tvaliasek
 */
class Tooler
{

    /**
     * Recursive delete folder and its content
     * @param string $path
     */
    public static function recurDelete(string $path): void
    {
        if (is_dir($path)) {
            $contents = array_diff(scandir($path), array('.', '..'));
            if (!empty($contents)) {
                foreach ($contents as $item) {
                    if (is_dir($path . '/' . $item)) {
                        self::recurDelete($path . '/' . $item);
                    } else {
                        self::unlinkIfExists($path . '/' . $item);
                    }
                }
            }
            rmdir($path);
        } else {
            self::unlinkIfExists($path);
        }
    }

    /**
     * Recursive move of folder and its content to new location
     * @param string $path
     * @param string $destination
     */
    public static function recurMove(string $path, string $destination) : void
    {
        if (is_dir($path)) {
            if (file_exists($destination)) {
                self::recurDelete($destination);
            }
            if (!is_dir($destination)) {
                mkdir($destination, 0754, true);
            }
            $contents = array_diff(scandir($path), array('.', '..'));
            if (!empty($contents)) {
                foreach ($contents as $item) {
                    if (is_dir($path . '/' . $item)) {
                        self::recurMove($path . '/' . $item, $destination . '/' . $item);
                    } else {
                        copy($path . '/' . $item, $destination . '/' . $item);
                        chmod($destination . '/' . $item, 0754);
                    }
                }
            }
        }
        self::recurDelete($path);
    }

    /**
     * @param string $folder
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isEmptyFolder(string $folder) : bool
    {
        if (is_dir($folder)) {
            $contents = array_diff(scandir($folder), ['.', '..']);
            if (count($contents) === 0) {
                return true;
            }
        } else {
            throw new \InvalidArgumentException('Path '.$folder.' is not an folder.');
        }
        return false;
    }

    /**
     * Delete file or folder (recursive) on specified path if it exists
     * @param string $path
     */
    public static function unlinkIfExists(string $path)
    {
        if (file_exists($path)) {
            if (is_dir($path)) {
                self::recurDelete($path);
            } else {
                unlink($path);
            }
        }
    }

    /***
     * Get the directory size
     * @param string $directory
     * @return int
     */
    public static function getDirSize(string $directory)
    {
        $size = 0;
        foreach (new \RecursiveDirectoryIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * Recursively creates folder
     * @param string $path
     * @param int $mode
     */
    public static function createFolder(string $path, int $mode = 0754) : void
    {
        if (!is_dir($path)) {
            mkdir($path, $mode, true);
        }
    }
}
