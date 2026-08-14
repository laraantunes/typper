<?php
/**
 * Laralabs Typper
 */

namespace Typper;

use Arrayy\Arrayy;
use Typper\Skeleton\ContentInterface;
use Typper\Parsers\Parser;
use Typper\Parsers\Content\ParseDownExtraParser;

/**
 * The Content Class
 * 
 *
 * @package \Typper
 */
class Content implements ContentInterface
{
    /**
     * The content's slug
     *
     * @var string
     */
    public $slug;

    /**
     * The content's file path
     *
     * @var string
     */
    public $filePath;
    /**
     * The content's title
     *
     * @var string
     */
    public $title;

    /**
     * The content's subtitle
     *
     * @var string
     */
    public $subtitle = '';

    /**
     * The content's content
     *
     * @var string
     */
    public $content = '';

    /**
     * The content's author (when different from defined on site.yml)
     *
     * @var string
     */
    public $author;

    /**
     * The category of the content
     *
     * @var Category
     */
    public $category;

    /**
     * Defines if the content is published or not. Default true
     *
     * @var boolean
     */
    public $published = true;

    /**
     * The creation date of the content. If informed on file metadata, 
     * gets the data from there. If not, try to get it from the .md file
     *
     * @var string
     */
    public $creationDate;

    /**
     * The publication date of the content
     *
     * @var [type]
     */
    public $publicationDate;

    /**
     * The content type. Default 'post'
     * It can be 'post' or 'page'
     *
     * @var string
     */
    public $type = 'post';

    /**
     * An array of tags for the content
     *
     * @var array
     */
    public $tags = [];

    /**
     * An array of metadata for the content
     *
     * @var Arrayy
     */
    public $meta;

    /**
     * If true, the content was not found
     *
     * @var boolean
     */
    public $notFound = false;

    /**
     * Gets a Content object based on a given path
     *
     * @param string $path
     * @return self
     */
    public static function fromPath(string $path): ContentInterface
    {
        $contentsPath = trim(config('app.contentsPath','/'));
        $filePath = "{$contentsPath}/{$path}.md";

        if (!file_exists($filePath)) {
            $content = new self;
            $content->slug = $path;
            $content->filePath = $filePath;
            $content->notFound = true;
            return $content;
        }

        $content = self::parser()->parse($filePath);
        $contentData = $content->getMetadata();
        $contentData['slug'] = $path;
        $contentData['filePath'] = $filePath;
        $contentData['content'] = $content->getContent();
        return static::fromArray($contentData);
    }

    /**
     * Builds a Content object with a given array
     *
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): ContentInterface
    {
        $content = new self;
        $content->slug = $array['slug'];
        $content->filePath = $array['filePath'];
        $content->title = $array['title'] ?? '';
        $content->subtitle = $array['subtitle'] ?? '';
        $content->content = $array['content'] ?? '';
        $content->author = $array['author'] ?? config('author');
        if (!empty($array['published'])) {
            $content->published = $array['published'];
        }
        if (!empty($array['creationDate'])) {
            $content->creationDate = $array['creationDate'];
        } else {
            $content->creationDate = date('Y-m-d', filemtime($content->filePath));
        }
         
        if ($content->published) {
            $content->publicationDate = $content->creationDate;
            if (!empty($array['publicationDate'])) {
                $content->publicationDate = $array['publicationDate'];
            }
        }
        
        if (!empty($array['type'])) {
            $content->type = $array['type'];
        }
        if (!empty($array['tags'])) {
            $content->tags = $array['tags'];
        }
        if (!empty($array['meta'])) {
            $content->meta = new Arrayy($array['meta']);
        }
        $content->category = Category::fromSlug(getCategoryFromSlug($content->slug));

        return $content;
    }

    /**
     * Gets a Content based on it's file path
     *
     * @param string $path
     * @return ContentInterface
     */
    public static function fromFilePath(string $path): ContentInterface
    {
        // Obter caminho absoluto do diretório de conteúdos
        $contentsPath = realpath(config('app.contentsPath'));
        $absPath = realpath($path) ?: $path;
        
        if ($contentsPath && strpos($absPath, $contentsPath) === 0) {
            $relativePath = substr($absPath, strlen($contentsPath));
            $slug = ltrim(str_replace('.md', '', $relativePath), '/\\');
            $slug = str_replace('\\', '/', $slug);
            return self::fromPath($slug);
        }
        
        // Fallback caso falhe
        $path = str_replace('.md', '', ltrim(str_replace('\\', '/', $path), '/'));
        return self::fromPath($path);
    }

    /**
     * Gets the parser for data
     *
     * @return Parser
     */
    protected static function parser()
    {
        $contentParser = config('parser.contentParser', \Typper\Parsers\Content\ParseDownExtraParser::class);
        return new Parser(
            new $contentParser
        );
    }
}

