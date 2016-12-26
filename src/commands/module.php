<?php

namespace diversen\commands;

use diversen\conf;
use diversen\moduleinstaller;
use diversen\moduleloader;
use diversen\cli\common;

/**
 * File containing module functions for shell mode
 * (install, update, delete modules)
 *
 * @package     shell
 */
class module {


    public function getHelp() {
        return
                array(
                    'usage' => 'Locale module management',
                    'options' => array(
                        '--mod-in' => 'Install specified module',
                        '--mod-down' => 'Uninstall specified module',
                        '--mod-up' => 'Upgrade specified module to latest version',
                        '--purge' => 'Purge (uninstall and remove files) specified module',
                        '--all-up' => 'Check all modules and templates for later versions and upgrade',
                        '--list' => 'List all modules present installed'
                        ),
                    'arguments' => array(
                        'module' => 'The module to install or upgrade',
                        'version' => 'Specifiy a version'
                    ),
        );
    }
    
    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand ($args) {
        
        $module = $args->getValueByKey(0);
        $version = $args->getValueByKey(1);
    
        if ($args->getFlag('list')) {
            return $this->listAll();
        }
        
        if ($args->getFlag('mod-in')) {
            return $this->installModule($module, $version);
        }
        
        if ($args->getFlag('mod-down')) {
            return $this->uninstallModule($module);
        }
        
        if ($args->getFlag('mod-up')) {
            return $this->upgradeModule($module, $version);
        }
        
        if ($args->getFlag('purge')) {
            return $this->purge_module($module);
        }
        
        if ($args->getFlag('list')) {
            return $this->listAll();
        }
        
        if ($args->getFlag('all-up')) {
            return $this->upgradeAll($module);
        }
        exit(0);
    }

    /**
     * Install a module
     * @param string $module
     * @return boolean $res
     */
    public function installModule($module) {

        $in = new moduleinstaller();
        
        $options = [];
        $options['module'] = $module;
        
        $proceed = $in->setInstallInfo($options);
        if ($proceed === false) {
            return false;
        } else {
            $str = '';
            $ret = $in->install();
            if (!$ret) {
                $str .= $in->error;
            } else {
                $str .= $in->confirm;
            }
            common::echoMessage($str);
            return $ret;
        }
    }

    /**
     * function for upgrading all modules installed
     */
    public function upgradeAll() {
        
        $upgrade = new moduleinstaller();
        $modules = $upgrade->getModules();

        foreach ($modules as $val) {
            // testing if this is working
            $options = array('module' => $val['module_name']);
            $upgrade = new moduleinstaller($options);
            $upgrade->upgrade();

            //update_ini_file($options);
            common::echoMessage($upgrade->confirm);
        }
    }

    /**
     * function for uninstalling a module
     * @param string $module
     */
    public function uninstallModule($module) {
        $un = new moduleinstaller();
        
        $options = [];
        $options['module'] = $module;
        $proceed = $un->setInstallInfo($options);

        if ($proceed === false) {
            common::echoMessage($un->error);
        } else {
            $ret = $un->uninstall();
            if ($ret) {
                common::echoMessage($un->confirm);
            } else {
                common::echoMessage($un->error);
            }
        }
    }

    /**
     * function for purging a module
     * @param   string  $module
     */
    public function purge_module($module) {
        
        // check if module exists
        $module_path = conf::pathModules() . '/' . $module;
        if (!file_exists($module_path)) {
            common::echoMessage("module already purged: No such module path: $module_path");
            common::abort();
        }

        // it exists. Uninstall
        $this->uninstallModule($module);

        // remove
        $command = "rm -rf $module_path";
        common::execCommand($command);
    }

    /**
     * function for upgrading a module
     * @param string $module
     * @param string $version
     */
    public function upgradeModule($module, $version) {

        $options = [];
        $options['module'] = $module;
        $options['version'] = $version;
        
        // module exists.
        $upgrade = new moduleinstaller($options);
        $proceed = $upgrade->setInstallInfo($options);
        if ($proceed === false) {
            common::echoMessage("No such module '$options[module]' exists in modules dir.");
            return;
        }

        $ret = $upgrade->upgrade($options['version']);
        if (!$ret) {
            echo $upgrade->error . PHP_EOL;
        } else {
            echo $upgrade->confirm . PHP_EOL;
        }
        return $ret;
    }

    /**
     * List all modules in db table modules
     */
    public function listAll() {
        $ml = new moduleloader();
        $modules = $ml->getAllModules();
        print_r($modules);
    }
}
