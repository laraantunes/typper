<?php
/**
 * Laralabs Typper
 */

namespace Typper;

use Typper\Skeleton\ContentInterface;
use Typper\Skeleton\ThemeManagerInterface;

/**
 * Typper Theme Manager
 * 
 * @package Typper
 */
class ThemeManager implements ThemeManagerInterface
{
    /**
     * The current active theme
     *
     * @var string
     */
    public $activeTheme;

    /**
     * The path for themes folder
     *
     * @var string
     */
    public $themesPath;

    /**
     * The articles loader
     *
     * @var Loader
     */
    protected $loader;

    /**
     * ThemeManager constructor
     */
    public function __construct()
    {
        $this->activeTheme = config('theme') ?? 'default';
        $this->themesPath = trim(config('app.themesPath'), '/');
        $this->loader = new Loader();
    }

    /**
     * Loads a template by a given path
     *
     * @param string $path
     */
    public function fromPath(string $path)
    {
        $this->showHomeIfPossible($path);
        $content = $this->getContentFromPath($path);
        // If the content was not found, try to get it as a category or returns a not found
        if ($content->notFound) {
            $category = Category::fromSlug($path);
            if (!$category->notFound) {
                $this->showCategoryTemplate($category);
            } else {
                $this->showNotFoundTemplate();
            }
        }
        $this->showContentTemplate($content);
    }

    /**
     * Show home template if it exists. If it doesn't, handle "home" as a normal content
     *
     * @param string $path
     */
    protected function showHomeIfPossible(string $path)
    {
        $homeFilePath = "{$this->themesPath}/{$this->activeTheme}/home.php";
        if (in_array($path, ['', '/', 'home']) && file_exists($homeFilePath)) {
            include_once($homeFilePath);
            exit;
        }
    }

    /**
     * Gets a content from a given path
     *
     * @param string $path
     * @return ContentInterface
     */
    protected function getContentFromPath(string $path): ContentInterface
    {
        return $this->loader->load($path);
    }

    /**
     * Includes a part of the template
     *
     * @param string $part
     * @param array $data
     */
    protected function includeTemplatePart(string $part, array $data = [])
    {
        extract($data);
        include_once("{$this->themesPath}/{$this->activeTheme}/{$part}.php");
    }

    /**
     * Shows the category template
     * @param Category $category
     */
    protected function showCategoryTemplate(Category $category)
    {
        $this->includeTemplatePart('header');
        $this->includeTemplatePart('category', ['category' => $category]);
        $this->includeTemplatePart('footer');
        exit;
    }

    /**
     * Shows the content template
     */
    protected function showContentTemplate(Content $content)
    {
        $this->includeTemplatePart('header');
        $this->includeTemplatePart('content', ['content' => $content]);
        $this->includeTemplatePart('footer');
        exit;
    }

    /**
     * Shows the not found template
     */
    protected function showNotFoundTemplate()
    {
        $this->includeTemplatePart('not_found');
        exit;
    }
}