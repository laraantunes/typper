<?php
/**
 * Laralabs Typper
 * Helper functions and constants
 */

use Cocur\Slugify\Slugify;
use Typper\Config;

/**
 * Gets a configuration
 *
 * @param string $key
 * @return mixed
 */
function config(string $key)
{
    return Config::get($key);
}

/**
 * Gets a configuration as a pure array, not as an 
 * Arrayy/Arrayy object
 *
 * @param string $key
 * @return array
 */
function configAsPureArray(string $key): array
{
    return json_decode(Config::get($key)->toJson(), true);
}

/**
 * Slugify a given text
 *
 * @param string $string
 * @return string
 */
function slugify(string $string): string
{
    $slugify = new Slugify();
    return $slugify->slugify($string);
}

/**
 * Gets the category part of a given slug
 *
 * @param string $slug
 * @return string
 */
function getCategoryFromSlug(string $slug): string
{
    $slugArray = explode('/', $slug);
    unset($slugArray[count($slugArray) - 1]);
    return implode('/', $slugArray);
}

/**
 * Outputs Google Analytics tracking code if configured
 */
function ga_analytics()
{
    $ga_code = config('ga_code');
    if (!empty($ga_code)) {
        echo "<!-- Google tag (gtag.js) -->\n";
        echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id=" . htmlspecialchars($ga_code) . "\"></script>\n";
        echo "<script>\n";
        echo "window.dataLayer = window.dataLayer || [];\n";
        echo "function gtag(){dataLayer.push(arguments);}\n";
        echo "gtag('js', new Date());\n";
        echo "gtag('config', '" . htmlspecialchars($ga_code) . "');\n";
        echo "</script>\n";
    }
}

/**
 * Outputs SEO meta tags based on site configuration and current content
 *
 * @param \Typper\Skeleton\ContentInterface|null $content
 * @param \Typper\Skeleton\CategoryInterface|null $category
 */
function auto_seo(?\Typper\Skeleton\ContentInterface $content = null, ?\Typper\Skeleton\CategoryInterface $category = null)
{
    $siteTitle = config('siteTitle') ?: 'Typper';
    $appendTitle = config('seo_append_title');
    $separator = config('seo_title_separator') ?: '|';
    
    // Page Title
    $pageTitle = $siteTitle;
    if ($content && !empty($content->title)) {
        $pageTitle = $content->title;
        if ($appendTitle) {
            $pageTitle .= " $separator " . $siteTitle;
        }
    } elseif ($category && !empty($category->title)) {
        $pageTitle = $category->title;
        if ($appendTitle) {
            $pageTitle .= " $separator " . $siteTitle;
        }
    }
    echo "<title>" . htmlspecialchars($pageTitle) . "</title>\n";
    
    // Description
    $autoDescription = config('seo_auto_description');
    $maxDescWords = config('seo_max_description') ?: 30;
    $description = config('description') ?: '';
    
    if ($content && $autoDescription) {
        $contentDesc = '';
        if (isset($content->meta) && $content->meta->has('description')) {
            $contentDesc = $content->meta->get('description');
        } else if (!empty($content->content)) {
            $contentDesc = strip_tags($content->content);
            $words = preg_split('/\s+/', $contentDesc);
            if (count($words) > $maxDescWords) {
                $words = array_slice($words, 0, $maxDescWords);
                $contentDesc = implode(' ', $words) . '...';
            }
        }
        if (!empty($contentDesc)) {
            $description = trim($contentDesc);
        }
    } elseif ($category && $autoDescription && !empty($category->description)) {
        $description = trim($category->description);
    }
    
    if (!empty($description)) {
        echo "<meta name=\"description\" content=\"" . htmlspecialchars($description) . "\">\n";
    }
    
    // Keywords
    $autoKeywords = config('seo_auto_keywords');
    $maxKwWords = config('seo_max_keywords') ?: 20;
    $keywords = '';
    
    if ($content && $autoKeywords && !empty($content->tags)) {
        $tags = $content->tags;
        if (is_array($tags)) {
            $tags = array_slice($tags, 0, $maxKwWords);
            $keywords = implode(', ', $tags);
        } else if (is_string($tags)) {
            $keywords = $tags;
        }
    }
    
    if (!empty($keywords)) {
        echo "<meta name=\"keywords\" content=\"" . htmlspecialchars($keywords) . "\">\n";
    }
    
    // Open Graph
    $autoOg = config('seo_auto_og');
    if ($autoOg) {
        echo "<meta property=\"og:title\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";
        echo "<meta property=\"og:type\" content=\"" . ($content ? "article" : "website") . "\">\n";
        if (!empty($description)) {
            echo "<meta property=\"og:description\" content=\"" . htmlspecialchars($description) . "\">\n";
        }
        echo "<meta property=\"og:site_name\" content=\"" . htmlspecialchars($siteTitle) . "\">\n";
        
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        echo "<meta property=\"og:url\" content=\"" . htmlspecialchars($url) . "\">\n";
        
        if ($content && isset($content->meta) && $content->meta->has('thumbnail')) {
            $thumbnail = $content->meta->get('thumbnail');
            if (!filter_var($thumbnail, FILTER_VALIDATE_URL)) {
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                $base = $protocol . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['SCRIPT_NAME']);
                $thumbnail = rtrim($base, '/') . '/' . ltrim($thumbnail, '/');
            }
            echo "<meta property=\"og:image\" content=\"" . htmlspecialchars($thumbnail) . "\">\n";
        }
    }
    
    // Twitter
    $autoTwitter = config('seo_auto_twitter');
    if ($autoTwitter) {
        echo "<meta name=\"twitter:card\" content=\"summary\">\n";
        echo "<meta name=\"twitter:title\" content=\"" . htmlspecialchars($pageTitle) . "\">\n";
        if (!empty($description)) {
            echo "<meta name=\"twitter:description\" content=\"" . htmlspecialchars($description) . "\">\n";
        }
        if ($content && isset($content->meta) && $content->meta->has('thumbnail')) {
            $thumbnail = $content->meta->get('thumbnail');
            if (!filter_var($thumbnail, FILTER_VALIDATE_URL)) {
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                $base = $protocol . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['SCRIPT_NAME']);
                $thumbnail = rtrim($base, '/') . '/' . ltrim($thumbnail, '/');
            }
            echo "<meta name=\"twitter:image\" content=\"" . htmlspecialchars($thumbnail) . "\">\n";
        }
    }
}

/**
 * Gets all uncategorized contents (root level posts)
 *
 * @return \Typper\Content[]
 */
function get_uncategorized_contents(): array
{
    $contents = [];
    $path = config('app.contentsPath');
    if (!is_dir($path)) return $contents;
    
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $isLoggedIn = !empty($_SESSION['typper_logged_in']);
    
    $files = glob(rtrim($path, '/\\') . '/*.md');
    if ($files) {
        foreach ($files as $file) {
            $content = \Typper\Content::fromFilePath($file);
            // Ignore the 'home.md' if it exists, since it's the home page itself
            if ($content->slug === 'home') continue;
            if ($content->published || $isLoggedIn) {
                $contents[] = $content;
            }
        }
    }
    
    usort($contents, function($a, $b) {
        $dateA = strtotime($a->publicationDate ?? $a->creationDate);
        $dateB = strtotime($b->publicationDate ?? $b->creationDate);
        return $dateB <=> $dateA;
    });
    
    return $contents;
}

/**
 * Gets all contents by a specific tag
 *
 * @param string $tag
 * @return \Typper\Content[]
 */
function get_contents_by_tag(string $tag): array
{
    $contents = [];
    $path = config('app.contentsPath');
    if (!is_dir($path)) return $contents;
    
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $isLoggedIn = !empty($_SESSION['typper_logged_in']);
    
    $tagLower = mb_strtolower(trim($tag));
    
    $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($dir as $fileinfo) {
        if ($fileinfo->isFile() && $fileinfo->getExtension() === 'md') {
            $content = \Typper\Content::fromFilePath($fileinfo->getPathname());
            
            if ($content->published || $isLoggedIn) {
                $contentTags = [];
                if (is_array($content->tags)) {
                    $contentTags = $content->tags;
                } else if (is_string($content->tags)) {
                    $contentTags = explode(',', $content->tags);
                }
                
                foreach ($contentTags as $t) {
                    if (mb_strtolower(trim($t)) === $tagLower) {
                        $contents[] = $content;
                        break;
                    }
                }
            }
        }
    }
    
    usort($contents, function($a, $b) {
        $dateA = strtotime($a->publicationDate ?? $a->creationDate);
        $dateB = strtotime($b->publicationDate ?? $b->creationDate);
        return $dateB <=> $dateA;
    });
    
    return $contents;
}