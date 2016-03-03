<?php

namespace diversen\autoloader;

use diversen\conf;

/**
 * Modules autoloader for simple-php-classes
 * @package
 * @example
<code>
// Register an autoloader for loading modules from mopdules dir
$m = new modules();
$m->autoloadRegister();

// Now it is possible to load e.g. modules/blog/module.php 
</code>    
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
