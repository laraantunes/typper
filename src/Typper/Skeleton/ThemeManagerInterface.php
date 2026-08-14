<?php
/**
 * Laralabs Typper
 */

namespace Typper\Skeleton;

use Typper\Skeleton\ContentInterface;

/**
 * The Interface skeleton to ThemeManager object
 */
interface ThemeManagerInterface
{
    /**
     * Loads a template by a given path
     *
     * @param string $path
     */
    public function fromPath(string $path);
}

