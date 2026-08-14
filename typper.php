#!/usr/bin/php
<?php

include_once "vendor/autoload.php";

use splitbrain\phpcli\CLI;
use splitbrain\phpcli\Options;
use Typper\Loader;

class Typper extends CLI
{
    protected function setup(Options $options)
    {
        $options->setHelp('A minimalistic cli and markdown-based cms');
        $options->registerOption('clear', 'Clear all caches', 'c');
        $options->registerCommand('delete', 'Delete a file cache');
        $options->registerArgument('file', 'The path of the content to delete it\'s cache', true, 'delete');
        
        $options->registerCommand('make:post', 'Create a new post');
        $options->registerArgument('slug', 'Post slug', true, 'make:post');
        $options->registerOption('category', 'Category slug', null, 'category', 'make:post');

        $options->registerCommand('list:posts', 'List posts');
        $options->registerOption('category', 'Category slug', 'c', 'category', 'list:posts');

        $options->registerCommand('make:page', 'Create a new page');
        $options->registerArgument('slug', 'Page slug', true, 'make:page');
        $options->registerOption('category', 'Category slug', null, 'category', 'make:page');

        $options->registerCommand('list:pages', 'List pages');
        $options->registerOption('category', 'Category slug', 'c', 'category', 'list:pages');
        
        $options->registerCommand('site', 'Configure site properties (updates site.yml)');
        $options->registerOption('title', 'Site title', 't', 'title', 'site');
        $options->registerOption('author', 'Site author', 'a', 'author', 'site');
        $options->registerOption('theme', 'Active theme', null, 'theme', 'site');
        
        $options->registerCommand('make:category', 'Create a new category');
        $options->registerArgument('slug', 'Category slug', true, 'make:category');
        $options->registerOption('title', 'Category title', 't', 'title', 'make:category');
        $options->registerOption('desc', 'Category description', 'd', 'description', 'make:category');

        $options->registerCommand('edit:category', 'Edit an existing category');
        $options->registerArgument('slug', 'Category slug', true, 'edit:category');
        $options->registerOption('title', 'Category title', 't', 'title', 'edit:category');
        $options->registerOption('desc', 'Category description', 'd', 'description', 'edit:category');

        $options->registerCommand('list:categories', 'List all categories');
        
        $options->registerCommand('update', 'Update Typper to the latest version via GitHub');
    }

    protected function main(Options $options)
    {
        if ($options->getCmd() === 'delete') {
            
            $args = $options->getArgs();
            $loader = new Loader;
            $deleted = $loader->deleteCache($args[0]);
            if ($deleted) {
                echo "Cache of the file {$args[0]} deleted!\n";
            } else {
                echo "Something wrong happened deleting the cache file\n";
            }
            
            exit;
        }

        if ($options->getCmd() === 'make:post' || $options->getCmd() === 'make:page') {
            $type = $options->getCmd() === 'make:post' ? 'post' : 'page';
            $args = $options->getArgs();
            $slug = preg_replace('/[^a-z0-9\-\/]/', '', strtolower(trim($args[0])));
            $category = $options->getOpt('category');
            if ($category) {
                $category = preg_replace('/[^a-z0-9\-\/]/', '', strtolower(trim($category)));
            }
            
            if (!$slug) {
                echo "Error: Invalid slug provided.\n";
                exit;
            }

            $contents_dir = __DIR__ . '/contents';
            if ($category) {
                $contents_dir .= '/' . $category;
            }
            
            if (!is_dir($contents_dir)) {
                @mkdir($contents_dir, 0755, true);
            }
            
            $filePath = $contents_dir . '/' . $slug . '.md';
            
            if (file_exists($filePath)) {
                echo "Error: Content '{$slug}' already exists" . ($category ? " in category '{$category}'" : "") . "!\n";
                exit;
            }
            
            $title = ucfirst(basename($slug));
            // Criando com padrao nao publicado
            $content = "title: {$title}\ntype: {$type}\npublished: false\ntags: []\n===\n\n# {$title}\n\nStart writing here...";
            
            file_put_contents($filePath, $content);
            echo ucfirst($type) . " '{$slug}' created successfully" . ($category ? " in category '{$category}'" : "") . "!\n";
            exit;
        }

        if ($options->getCmd() === 'list:posts' || $options->getCmd() === 'list:pages') {
            $type = $options->getCmd() === 'list:posts' ? 'post' : 'page';
            $category = $options->getOpt('category');
            
            $contents_dir = __DIR__ . '/contents';
            if ($category) {
                $category = preg_replace('/[^a-z0-9\-\/]/', '', strtolower(trim($category)));
                $contents_dir .= '/' . $category;
            }
            
            if (!is_dir($contents_dir)) {
                echo "No contents found.\n";
                exit;
            }
            
            $files = glob($contents_dir . '/*.md');
            $count = 0;
            
            foreach ($files as $file) {
                $content = \Typper\Content::fromFilePath($file);
                if ($content->type === $type) {
                    $slug = basename($file, '.md');
                    $title = $content->title ?? 'No title';
                    $status = $content->published ? 'Published' : 'Draft';
                    echo "- {$slug} ({$title}) [{$status}]\n";
                    $count++;
                }
            }
            
            if ($count === 0) {
                echo "No {$type}s found" . ($category ? " in category '{$category}'" : " without category") . ".\n";
            }
            exit;
        }
        
        if ($options->getCmd() === 'site') {
            $title = $options->getOpt('title');
            $author = $options->getOpt('author');
            $theme = $options->getOpt('theme');
            
            if (!$title && !$author && !$theme) {
                echo "Please provide --title, --author, and/or --theme to update the site configuration.\n";
                exit;
            }
            
            $siteFile = __DIR__ . '/config/site.yml';
            $data = [];
            if (file_exists($siteFile)) {
                $data = \Symfony\Component\Yaml\Yaml::parseFile($siteFile) ?? [];
            }
            
            if ($title) {
                $data['title'] = $title;
            }
            if ($author) {
                $data['author'] = $author;
            }
            if ($theme) {
                $data['theme'] = $theme;
            }
            
            file_put_contents($siteFile, \Symfony\Component\Yaml\Yaml::dump($data));
            echo "Site configuration updated successfully!\n";
            exit;
        }

        if ($options->getCmd() === 'make:category') {
            $args = $options->getArgs();
            $slug = preg_replace('/[^a-z0-9\-\/]/', '', strtolower(trim($args[0])));
            $title = $options->getOpt('title') ?: ucfirst($slug);
            $description = $options->getOpt('desc');

            $catFile = __DIR__ . '/config/categories.yml';
            $data = file_exists($catFile) ? \Symfony\Component\Yaml\Yaml::parseFile($catFile) ?? [] : [];

            if (isset($data[$slug])) {
                echo "Error: Category '{$slug}' already exists!\n";
                exit;
            }

            $data[$slug] = ['title' => $title];
            if ($description) {
                $data[$slug]['description'] = $description;
            }

            file_put_contents($catFile, \Symfony\Component\Yaml\Yaml::dump($data));
            
            // Create folder
            $contents_dir = __DIR__ . '/contents/' . $slug;
            if (!is_dir($contents_dir)) {
                @mkdir($contents_dir, 0755, true);
            }
            
            echo "Category '{$slug}' created successfully!\n";
            exit;
        }

        if ($options->getCmd() === 'edit:category') {
            $args = $options->getArgs();
            $slug = trim($args[0]);
            $title = $options->getOpt('title');
            $description = $options->getOpt('desc');

            $catFile = __DIR__ . '/config/categories.yml';
            $data = file_exists($catFile) ? \Symfony\Component\Yaml\Yaml::parseFile($catFile) ?? [] : [];

            if (!isset($data[$slug])) {
                echo "Error: Category '{$slug}' not found!\n";
                exit;
            }

            if ($title) $data[$slug]['title'] = $title;
            if ($description) $data[$slug]['description'] = $description;

            file_put_contents($catFile, \Symfony\Component\Yaml\Yaml::dump($data));
            echo "Category '{$slug}' updated successfully!\n";
            exit;
        }

        if ($options->getCmd() === 'list:categories') {
            $catFile = __DIR__ . '/config/categories.yml';
            $data = file_exists($catFile) ? \Symfony\Component\Yaml\Yaml::parseFile($catFile) ?? [] : [];
            
            if (empty($data)) {
                echo "No categories found.\n";
                exit;
            }
            
            foreach ($data as $slug => $cat) {
                $title = $cat['title'] ?? 'No Title';
                echo "- {$slug} ({$title})\n";
            }
            exit;
        }
        
        if ($options->getCmd() === 'update') {
            echo "Checking for updates...\n";
            $result = \Typper\Updater::update();
            if ($result['success']) {
                echo $result['message'] . "\n";
            } else {
                echo "Error: " . $result['message'] . "\n";
            }
            exit;
        }

        if ($options->getOpt('clear')) {
            $loader = new Loader;
            $loader->clear();
            echo "Cache cleared!\n";
        } else {
            echo $options->help();
        }
    }
}

$cli = new Typper();
$cli->run();