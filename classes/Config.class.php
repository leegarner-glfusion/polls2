<?php
/**
 * Class to read and manipulate Polls2 configuration values.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     polls
 * @version     v3.0.0
 * @since       v3.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Polls2;


/**
 * Class to get plugin configuration data.
 * @package polls
 */
final class Config
{
    /** Array of config items (name=>val).
     * @var array */
    private $properties = NULL;


    /**
     * Get the Polls2 configuration object.
     * Creates an instance if it doesn't already exist.
     *
     * @return  object      Configuration object
     */
    public static function getInstance()
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self;
        }
        return $instance;
    }


    /**
     * Create an instance of the Polls2 configuration object.
     */
    private function __construct()
    {
        if ($this->properties === NULL) {
            $this->properties = \config::get_instance()
                ->get_config('polls2');
        }
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key    Name of item to retrieve
     * @return  mixed       Value of config item
     */
    public function _get($key=NULL)
    {
        if ($key === NULL) {
            return $this->properties;
        } else {
            return array_key_exists($key, $this->properties) ? $this->properties[$key] : NULL;
        }
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set
     * @return  object  $this
     */
    public function _set($key, $val)
    {
        if ($val === NULL) {
            unset($this->properties[$key]);
        } else {
            $this->properties[$key] = $val;
        }
        return $this;
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set, NULL to unset
     */
    public static function set($key, $val=NULL)
    {
        return self::getInstance()->_set($key, $val);
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key    Name of item to retrieve
     * @return  mixed       Value of config item
     */
    public static function get($key=NULL)
    {
        return self::getInstance()->_get($key);
    }

}

?>
