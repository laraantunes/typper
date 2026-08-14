<?php
namespace Typper\Parsers;

class Document
{
    protected $metadata;
    protected $content;

    public function __construct(array $metadata = [], string $content = '')
    {
        $this->metadata = $metadata;
        $this->content = $content;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
