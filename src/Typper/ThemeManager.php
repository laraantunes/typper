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
        $cleanPath = trim($path, '/');
        
        // Add support for tags
        if (strpos($cleanPath, 'tag/') === 0) {
            $tagParts = explode('/', $cleanPath);
            $tag = $tagParts[1] ?? '';
            if ($tag !== '') {
                $this->showTagTemplate(urldecode($tag));
            }
            return;
        }
        
        $lookupPath = in_array($cleanPath, ['', 'home']) ? 'home' : $cleanPath;
        
        $content = $this->getContentFromPath($lookupPath);
        
        // Bloqueia a visualização de conteúdos não publicados se não estiver logado
        if (!$content->notFound && !$content->published) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (empty($_SESSION['typper_logged_in'])) {
                $content->notFound = true;
            }
        }

        if (!$content->notFound) {
            $this->showContentTemplate($content);
            return;
        }

        if (in_array($cleanPath, ['', 'home'])) {
            $this->showHomeIfPossible($cleanPath);
        }

        // If the content was not found, try to get it as a category or returns a not found
        $category = Category::fromSlug($cleanPath);
        if (!$category->notFound) {
            $this->showCategoryTemplate($category);
        } else {
            $this->showNotFoundTemplate();
        }
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
        $this->includeTemplatePart('header', ['category' => $category]);
        $this->includeTemplatePart('category', ['category' => $category]);
        $this->includeTemplatePart('footer', ['category' => $category]);
        exit;
    }

    /**
     * Shows the tag template
     * @param string $tag
     */
    protected function showTagTemplate(string $tag)
    {
        // Se não houver arquivo tag.php no tema, tenta usar o category.php como fallback (ou dá not found)
        if (!file_exists("{$this->themesPath}/{$this->activeTheme}/tag.php")) {
            $this->showNotFoundTemplate();
        }
        $this->includeTemplatePart('header', ['tag' => $tag]);
        $this->includeTemplatePart('tag', ['tag' => $tag]);
        $this->includeTemplatePart('footer', ['tag' => $tag]);
        exit;
    }

    /**
     * Shows the content template
     */
    protected function showContentTemplate(Content $content)
    {
        $this->includeTemplatePart('header', ['content' => $content]);
        $this->includeTemplatePart('content', ['content' => $content]);
        $this->includeTemplatePart('footer', ['content' => $content]);
        exit;
    }

    /**
     * Shows the not found template
     */
    protected function showNotFoundTemplate()
    {
        $this->includeTemplatePart('header');
        $this->includeTemplatePart('not_found');
        $this->includeTemplatePart('footer');
        exit;
    }
}