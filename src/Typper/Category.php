<?php
/**
 * Laralabs Typper
 */

namespace Typper;

use Nette\Utils\Finder;
use Symfony\Component\Yaml\Yaml;
use Typper\Skeleton\CategoryInterface;

 /**
  * The category class
  *
  * @package \Typper
  */
class Category implements CategoryInterface
{
    /**
     * An array with all loaded categories
     */
    static $categories;

    /**
     * The Category title
     *
     * @var string
     */
    public $title;

    /**
     * The category description
     *
     * @var string
     */
    public $description = '';

    /**
     * The category slug
     *
     * @var string
     */
    public $slug;

    /**
     * If true, the category was not found
     *
     * @var boolean
     */
    public $notFound = false;

    /**
     * Category constructor
     *
     * @param string $title
     * @param string $slug
     * @param string $description
     */
    public function __construct(string $title = '', string $slug = '', string $description = '')
    {
        $this->title = $title;
        $this->slug = $slug;
        $this->description = $description;
    }

    /**
     * Gets all the contents from the current Category
     *
     * @return ContentInterface[]
     */
    public function getContents(): array
    {
        $contents = [];
        $path = config('app.contentsPath').DIRECTORY_SEPARATOR.$this->slug;
        if (!is_dir($path)) return $contents;
        
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $isLoggedIn = !empty($_SESSION['typper_logged_in']);
        
        foreach (Finder::findFiles('*.md')->in($path) as $key => $file) {
            $content = Content::fromFilePath($file);
            if ($content->published || $isLoggedIn) {
                $contents[] = $content;
            }
        }
        return $contents;
    }

    /**
     * Gets a Category object based on an array
     *
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): CategoryInterface
    {
        return new self($array['title'], $array['slug'], $array['description'] ?? '');
    }

    /**
     * Searches the categories configured based on a passed slug
     *
     * @param string $slug
     * @param boolean $forceReload If true, force the reload of the categories list
     * @return self A Category object filled with found data or empty
     */
    public static function fromSlug(string $slug, bool $forceReload = false): CategoryInterface
    {
        $filtered = array_filter(self::getCategories($forceReload), function ($item) use ($slug) {
            if (trim($item->slug, '/') == trim($slug, '/')) {
                return $item;
            }
        });
        return array_values($filtered)[0] ?? self::notFoundCategory();
    }

    /**
     * Get all configured categories
     *
     * @param bool $reload If true, reloads the data if it's loaded
     * @return Category[]
     */
    public static function getCategories(bool $reload = false): array
    {
        if (!empty(static::$categories) && !$reload) {
            return static::$categories;
        }

        $categoriesArray = Yaml::parseFile('config/categories.yml');
        static::$categories = [];
        foreach ($categoriesArray as $slug => $category) {
            $category['slug'] = $slug;
            static::$categories[] = self::fromArray($category);
        }
        return static::$categories;
    }

    /**
     * Returns an empty object with not found marked
     *
     * @return self
     */
    protected static function notFoundCategory(): self
    {
        $notFound = new self();
        $notFound->notFound = true;
        return $notFound;
    }
}

