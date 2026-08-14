<?php
namespace Typper\Parsers;

use Symfony\Component\Yaml\Yaml;
use Typper\Skeleton\ContentParserInterface;

class Parser
{
    protected $contentParser;

    public function __construct(ContentParserInterface $contentParser)
    {
        $this->contentParser = $contentParser;
    }

    public function parse(string $filePath): Document
    {
        if (!file_exists($filePath)) {
            return new Document();
        }

        $rawContent = file_get_contents($filePath);
        
        // Separa metadados do conteúdo baseado no formato ==== ou ----
        $parts = preg_split('/^(?:===|---)\s*$/m', $rawContent, 2);
        
        if (count($parts) > 1) {
            $metadataStr = trim($parts[0]);
            $markdownStr = trim($parts[1]);
            
            // Suporta yaml nativamente
            try {
                $metadata = Yaml::parse($metadataStr) ?? [];
            } catch (\Exception $e) {
                $metadata = []; // Fallback seguro
            }
        } else {
            $metadata = [];
            $markdownStr = trim($rawContent);
        }

        $htmlContent = $this->contentParser->parse($markdownStr);

        return new Document($metadata, $htmlContent);
    }
}
