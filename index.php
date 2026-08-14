<?php
/**
 * Laralabs Typper
 */
include_once "vendor/autoload.php";

\Typper\Router::fromUrl()->load();
//print_r(Typper\Category::getCategories());
//print_r(Typper\Content::fromPath('teste'));