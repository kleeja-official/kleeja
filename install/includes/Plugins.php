<?php

/**
 * @ignore an alias class for plugins class, during installation or updating.
 */
class Plugins
{
    private static $instance;

    /**
     * @return Plugins
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return array
     */
    public function run($name)
    {
        return [];
    }
}
