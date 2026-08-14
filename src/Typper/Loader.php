<?php
/**
 * Laralabs Typper
 */

namespace Typper;

use Stash\Pool;

/**
 * Typper contents Loader
 *
 * @package \Typper
 */
class Loader
{
    /**
     * Cache pool for storing articles and pages
     * 
     * @var Pool
     */
    protected $cachePool;

    /**
     * Loader constructor
     */
    public function __construct()
    {
        $driverClass = config('cache.driver');
        $driver = new $driverClass(configAsPureArray('cache.options'));
        $this->cachePool = new Pool($driver);
    }

    /**
     * Loads a path and returns a content
     * 
     * @param string $path
     * @return Content
     */
    public function load(string $path): Content
    {
        // If empty path is given, assumes it's the home
        if (in_array($path, ['', '/'])) {
            $path = 'home';
        }
        
        // Gets from cache the data
        $item = $this->cachePool->getItem($path);
        // Tries to get cached data
        $data = $item->get();
        // If the item is not stored on cache, loads the data
        if($item->isMiss()){
            $item->lock();
            $data = Content::fromPath($path);
            // Stores at cache the returned data
            $this->cachePool->save($item->set($data));
        }
        return $data;
    }

    public function purge()
    {
        $this->cachePool->purge();
    }

    public function clear()
    {
        $this->cachePool->clear();
    }

    public function deleteCache(string $file)
    {
        $this->cachePool->deleteItem($file);
    }
}

