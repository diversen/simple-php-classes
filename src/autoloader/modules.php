<?php

namespace diversen\autoloader;

use diversen\conf;

/**
 * Modules autoloader for simple-php-classes
 * 
 * @package     main
 * @example     
 */
class modules {

    /**
     * Register the modulesAutoloader autoloader
     */
    public function autoloadRegister () {
        spl_autoload_register(array($this, 'modulesAutoLoader'));
    }
    
    /**
     * The method which autoloads module files
     * @param string $classname
     */
    public function modulesAutoLoader ($classname) {        
        $class = str_replace('\\', '/', $classname) . "";
        $class = conf::pathBase() . "/" . $class.= ".php";
        if (file_exists($class)) {
            require $class;
        } 
    }
}