<?php

namespace Typper\Parsers\Content;

use ParsedownExtra;
use Typper\Skeleton\ContentParserInterface;

/**
 * Class ParseDownExtraParser, a custom Parser
 * using ParsedownExtra package
 * 
 * @package \Typper\Parsers\Content
 */
class TypperParsedown extends ParsedownExtra
{
    protected function inlineImage($Excerpt)
    {
        $image = parent::inlineImage($Excerpt);

        if (!isset($image)) {
            return $image;
        }

        if (isset($image['element']['attributes']['src'])) {
            $src = $image['element']['attributes']['src'];
            $parsedUrl = parse_url($src);
            
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $query);
                
                if (isset($query['resize'])) {
                    $width = (int) $query['resize'];
                    $quality = isset($query['quality']) ? (int) $query['quality'] : 85;
                    $path = $parsedUrl['path'] ?? '';
                    
                    if (($pos = strpos($path, '/files/')) !== false && extension_loaded('gd')) {
                        $localRelPath = substr($path, $pos);
                        $siteRoot = dirname($_SERVER['SCRIPT_FILENAME']);
                        $absolutePath = realpath($siteRoot . $localRelPath);
                        
                        if ($absolutePath && file_exists($absolutePath)) {
                            $cacheDir = $siteRoot . '/cache/images';
                            if (!is_dir($cacheDir)) {
                                @mkdir($cacheDir, 0755, true);
                            }
                            // Realpath now that it exists
                            $cacheDir = realpath($cacheDir);
                            
                            $filename = basename($absolutePath);
                            $slug = basename(dirname($absolutePath));
                            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            $name = pathinfo($filename, PATHINFO_FILENAME);
                            
                            $cacheFilename = sprintf('%s-%s-%dw-%dq.%s', $slug, $name, $width, $quality, $ext);
                            $cachePath = $cacheDir . '/' . $cacheFilename;
                            
                            if (!file_exists($cachePath) || filemtime($absolutePath) > filemtime($cachePath)) {
                                $this->resizeImage($absolutePath, $cachePath, $width, $quality, $ext);
                            }
                            
                            $newUrlBase = substr($path, 0, $pos);
                            $image['element']['attributes']['src'] = $newUrlBase . '/cache/images/' . $cacheFilename;
                            $image['element']['attributes']['width'] = $width;
                        }
                    }
                }
            }
        }
        
        return $image;
    }
    
    private function resizeImage($source, $destination, $targetWidth, $quality, $ext)
    {
        $size = getimagesize($source);
        if (!$size) return;
        
        list($origWidth, $origHeight) = $size;
        if (!$origWidth || !$origHeight) return;
        
        $targetHeight = (int) round(($origHeight / $origWidth) * $targetWidth);
        $imageResized = imagecreatetruecolor($targetWidth, $targetHeight);
        
        $imageOrig = null;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $imageOrig = imagecreatefromjpeg($source);
                break;
            case 'png':
                $imageOrig = imagecreatefrompng($source);
                imagealphablending($imageResized, false);
                imagesavealpha($imageResized, true);
                $transparent = imagecolorallocatealpha($imageResized, 255, 255, 255, 127);
                imagefilledrectangle($imageResized, 0, 0, $targetWidth, $targetHeight, $transparent);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $imageOrig = imagecreatefromwebp($source);
                    imagealphablending($imageResized, false);
                    imagesavealpha($imageResized, true);
                    $transparent = imagecolorallocatealpha($imageResized, 255, 255, 255, 127);
                    imagefilledrectangle($imageResized, 0, 0, $targetWidth, $targetHeight, $transparent);
                }
                break;
            case 'gif':
                $imageOrig = imagecreatefromgif($source);
                $transparentIndex = imagecolortransparent($imageOrig);
                if ($transparentIndex >= 0) {
                    $transparentColor = imagecolorsforindex($imageOrig, $transparentIndex);
                    $transparentIndex = imagecolorallocate($imageResized, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                    imagefill($imageResized, 0, 0, $transparentIndex);
                    imagecolortransparent($imageResized, $transparentIndex);
                }
                break;
        }
        
        if (!$imageOrig) {
            imagedestroy($imageResized);
            return;
        }
        
        imagecopyresampled($imageResized, $imageOrig, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);
        
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($imageResized, $destination, $quality);
                break;
            case 'png':
                $pngQuality = round((100 - $quality) / 100 * 9);
                imagepng($imageResized, $destination, $pngQuality);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    imagewebp($imageResized, $destination, $quality);
                }
                break;
            case 'gif':
                imagegif($imageResized, $destination);
                break;
        }
        
        imagedestroy($imageOrig);
        imagedestroy($imageResized);
    }
}

class ParseDownExtraParser implements ContentParserInterface
{
    /**
     * Parse raw content into new format.
     *
     * @param string $content
     *
     * @return string
     */
    public function parse(string $content): string
    {
        return (new TypperParsedown)->text($content);
    }
}