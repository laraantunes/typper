<?php
namespace Typper\Skeleton;

interface ContentParserInterface
{
    /**
     * Parse raw content into new format.
     *
     * @param string $content
     *
     * @return string
     */
    public function parse(string $content): string;
}
