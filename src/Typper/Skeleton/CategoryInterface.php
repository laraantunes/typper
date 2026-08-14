<?php
/**
 * Laralabs Typper
 */

namespace Typper\Skeleton;

/**
 * The Interface skeleton to Category object
 */
interface CategoryInterface
{
    /**
     * Gets a Category object based on an array
     *
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): CategoryInterface;

    /**
     * Searches the categories configured based on a passed slug
     *
     * @param string $slug
     * @param boolean $forceReload If true, force the reload of the categories list
     * @return self A Category object filled with found data or empty
     */
    public static function fromSlug(string $slug, bool $forceReload = false): CategoryInterface;

    /**
     * Get all configured categories
     *
     * @param bool $reload If true, reloads the data if it's loaded
     * @return Category[]
     */
    public static function getCategories(bool $reload = false): array;
}

