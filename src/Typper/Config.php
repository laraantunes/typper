<?php
/**
 * 2009 Typper
 */
namespace Typper;

use Arrayy\Arrayy;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines the Configuration for the application
 *
 * @package \Typper
 */
class Config
{
    /**
     * Stores all the application configurations
     * 
     * @var Arrayy
     */
    protected $config;
    
    /**
     * Stores all the site configuration
     * 
     * @var Arrayy
     */
    protected $site;

    /**
     * Config singleton instance
     * 
     * @var self
     */
    static $instance;

    /**
     * Config Class Constructor
     */
    public function __construct()
    {
        $this->config = new Arrayy(include('config/application_config.php'));
        $this->site = new Arrayy(Yaml::parseFile('config/site.yml'));
    }

    /**
     * Gets the Config singleton
     * 
     * @return self
     */
    public static function singleton(): self
    {
        if (empty(static::$instance)) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /**
     * Gets a configuration
     * 
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        return self::singleton()->config->get($key) ?? self::singleton()->site->get($key) ?? null;
    } 
}

