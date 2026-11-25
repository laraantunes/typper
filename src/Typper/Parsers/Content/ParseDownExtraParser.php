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
    public function __construct()
    {
        parent::__construct();

        // Blocos "$$tipo ... $$" do editor: embed (Incorporar Conteúdo) e vídeos
        $this->BlockTypes['$'] = array_merge(['Embed'], $this->BlockTypes['$'] ?? []);
    }

    /**
     * Players dos blocos de vídeo; o conteúdo do bloco (ID) vai no fim da URL.
     * Mantenha em sincronia com videoPlugin() em panel/editor.php.
     */
    private const VIDEO_PLAYERS = [
        'youtube'  => 'https://www.youtube.com/embed/',
        'vimeo'    => 'https://player.vimeo.com/video/',
        'youku'    => 'https://player.youku.com/embed/',
        'bilibili' => 'https://player.bilibili.com/player.html?aid=',
        'qq'       => 'https://v.qq.com/txp/iframe/player.html?vid=',
    ];

    /**
     * Abre um bloco "$$embed" (HTML exibido como está) ou de vídeo
     * ("$$youtube", "$$vimeo", "$$mp4"...). O bloco termina na linha "$$".
     */
    protected function blockEmbed($Line)
    {
        if (!preg_match('/^\$\$(\w+)\s*$/', $Line['text'], $matches)) {
            return;
        }

        $type = strtolower($matches[1]);
        if ($type !== 'embed' && $type !== 'mp4' && !isset(self::VIDEO_PLAYERS[$type])) {
            return;
        }

        return [
            'embedType' => $type,
            'embedLines' => [],
            'element' => [
                'name' => 'div',
                'attributes' => ['class' => $type === 'embed' ? 'typper-embed' : 'typper-embed typper-video'],
                'rawHtml' => '',
                'allowRawHtmlInSafeMode' => true,
            ],
        ];
    }

    protected function blockEmbedContinue($Line, $Block)
    {
        if (isset($Block['complete'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            for ($i = 0; $i < $Block['interrupted']; $i++) {
                $Block['embedLines'][] = '';
            }
            unset($Block['interrupted']);
        }

        if (rtrim($Line['text']) === '$$') {
            $Block['complete'] = true;
            return $Block;
        }

        $Block['embedLines'][] = $Line['body'];

        return $Block;
    }

    protected function blockEmbedComplete($Block)
    {
        $content = trim(implode("\n", $Block['embedLines']));
        $type = $Block['embedType'];

        if ($type !== 'embed') {
            $source = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            $content = $type === 'mp4'
                ? '<video width="640" controls src="' . $source . '" style="max-width: 100%;"></video>'
                : '<iframe width="640" height="450" src="' . self::VIDEO_PLAYERS[$type] . $source . '" frameborder="0" allowfullscreen style="max-width: 100%;"></iframe>';
        }

        $Block['element']['rawHtml'] = "\n" . $content . "\n";

        return $Block;
    }

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
                            $cacheDir = $siteRoot . '/media-cache';
                            if (!is_dir($cacheDir)) {
                                @mkdir($cacheDir, 0755, true);
                            }
                            // Realpath now that it exists, or fallback
                            $cacheDir = realpath($cacheDir) ?: $cacheDir;
                            
                            $filename = basename($absolutePath);
                            $slug = basename(dirname($absolutePath));
                            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            $name = pathinfo($filename, PATHINFO_FILENAME);
                            
                            $cacheFilename = sprintf('%s-%s-%dw-%dq.%s', $slug, $name, $width, $quality, $ext);
                            $cachePath = $cacheDir . '/' . $cacheFilename;
                            
                            if (!file_exists($cachePath) || filemtime($absolutePath) > filemtime($cachePath)) {
                                $this->resizeImage($absolutePath, $cachePath, $width, $quality, $ext);
                            }
                            
                            if (file_exists($cachePath)) {
                                $newUrlBase = substr($path, 0, $pos);
                                $image['element']['attributes']['src'] = $newUrlBase . '/media-cache/' . $cacheFilename;
                                $image['element']['attributes']['width'] = $width;
                            }
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
        
        $imageOrig = @imagecreatefromstring(file_get_contents($source));
        
        if (!$imageOrig) {
            imagedestroy($imageResized);
            return;
        }
        
        if ($ext === 'png' || $ext === 'webp' || $ext === 'gif') {
            imagealphablending($imageResized, false);
            imagesavealpha($imageResized, true);
            $transparent = imagecolorallocatealpha($imageResized, 255, 255, 255, 127);
            imagefilledrectangle($imageResized, 0, 0, $targetWidth, $targetHeight, $transparent);
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