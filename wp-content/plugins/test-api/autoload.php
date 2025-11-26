<?php

/**
 * Plugin classes autoloader
 */

class TaTi_Autoloader
{
    /**
     * The Constructor.
     */
    public function __construct()
    {
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }

        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Take a class name and turn it into a file name.
     *
     * @param string $class_name
     * @return string
     */
    public function prepare(string $class_name)
    {
        $class_name_exploded = explode('\\', strtolower($class_name));
        $new_path = str_replace(['\\', '/', '_'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '-'], implode(DIRECTORY_SEPARATOR, $class_name_exploded) . '.php');
        return WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $new_path;
    }

    /**
     * Include a class file.
     *
     * @param mixed $class_name
     * @return void
     */
    public function autoload(string $class_name)
    {

        $path = $this->prepare($class_name);
        if (is_readable($path)) {
            include_once $path;
        }
    }
}

new TaTi_Autoloader();
