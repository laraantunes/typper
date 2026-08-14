<?php
/**
 * Laralabs Typper
 */

namespace Typper\Skeleton;

/**
 * The Interface skeleton to Content object
 */
interface ContentInterface
{
    /**
     * Gets a Content object based on a given path
     *
     * @param string $path
     * @return self
     */
    public static function fromPath(string $path): ContentInterface;

    /**
     * Builds a Content object with a given array
     *
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): ContentInterface;
}

