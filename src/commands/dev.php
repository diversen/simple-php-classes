<?php

namespace diversen\commands;

use diversen\conf;
use diversen\file;
use diversen\log;
use diversen\mycurl;
use diversen\cli\common;

/**
 * File containing documentation functions for shell mode
 *
 * @package     shell_dev
 */
class dev {

    public function getHelp() {
        return
                array(
                    'usage' => 'Dev commands for testing and checking',
                    'options' => array(
                        '--http' => 'Will check all web access points and give return code, e.g. 200 or 403 or 404',
                        '--env' => 'Displays which env you are in (development, stage or production)',
                        '--log' => 'Test log file. Will write hello world into logs/debug.log'),
        );
    }
    
    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {
        
        if ($args->getFlag('http')) {
            $this->httpTest();
            return 0;
        }
        
        if ($args->getFlag('env')) {
            $this->devTest();
            return 0;
        }
        
        if ($args->getFlag('log')) {
            $this->logTest();
            return 0;
        }
        
    }

    /**
     * function for checking if your are denying people 
     * from e.g. admin areas of your module. 
     */
    public function httpTest() {

        $files = file::getFileListRecursive(conf::pathModules(), "*module.php");
        foreach ($files as $val) {

            $class_path = "modules" . str_replace(conf::pathModules(), '', $val);
            $class_path = str_replace('.php', '', $class_path);

            $class = str_replace('/', "\\", $class_path);
            $ary = get_class_methods($class);

            if (!is_array($ary)) {
                continue;
            }
            $call_paths = $this->dev_get_actions($ary, $class_path);

            foreach ($call_paths as $path) {

                $url = conf::getSchemeWithServerName() . "$path";
                $curl = new mycurl($url);
                $curl->createCurl();

                echo $curl->getHttpStatus();
                common::echoMessage(" Status code recieved on: $url");
            }
        }
    }

    public function dev_get_actions($methods, $class_path) {
        $ary = explode("/", $class_path);

        array_pop($ary);
        array_shift($ary);

        $path = "/" . implode('/', $ary);

        foreach ($methods as $key => $method) {
            if (!strstr($method, 'Action')) {
                unset($methods[$key]);
                continue;
            }
            $methods[$key] = $path . "/" . str_replace('Action', '', $method);
        }

        return $methods;
    }

    public function devTest($options = null) {
        echo conf::getEnv() . "\n";
    }

    public function logTest($options = null) {
        log::error('Hello world');
    }
}
