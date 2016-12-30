<?php

namespace diversen\commands;

use diversen\cache\clear;
use diversen\conf;
use diversen\cli\common;

/**
 * File containing documentation functions for shell mode
 *
 * @package     shell
 */
class cache {
    
    public function getCommand() {
        return 
            array (
                'usage' => 'Commands for clearing caches',
                'options' => array (
                    '--clear-db' => 'Clear db cache - only works on default domain',
                    '--clear-assets' => 'Clear cached assets',
                    '--clear-all' => 'Clear all cached assets, and db cache')
            );
    }
    
    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand ($args){
        if ($args->getFlag('clear-db')) {
            return $this->clearDb();
        }
        
        if ($args->getFlag('clear-assets')) {
            return $this->clearAssets();
        }
        
        if ($args->getFlag('clear-all')) {
            return $this->clearAll();
        }
        
    }
    
    public function clearDb() {
        if (clear::db()) {
            return 0;
        }
        return 1;
    }

    public function clearAssets() {
        if (conf::isCli()) {
            common::needRoot();
        }
        clear::assets();
        return 0;
    }

    public function clearAll() {
        if (conf::isCli()) {
            common::needRoot();
        }

        clear::all();
        return 0;
    }
}
