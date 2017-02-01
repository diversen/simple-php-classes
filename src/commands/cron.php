<?php

namespace diversen\commands;

use diversen\cli\common;
use diversen\conf;
use diversen\moduleloader;
use diversen\date;


class cron {
    
    public function getCommand() {
        return 
            array (
                'usage' => "Cron command for running all module's cron jobs",
                'options' => array (
                    '--run' => 'Run all cron jobs implemented in all modules',
                    '--install' => 'See how to install cron line in crontab for this system')
            );
    }
    
    
    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {
        if ($args->getFlag('run')) {
            return $this->runCron();
        }
        
        if ($args->getFlag('install')) {
            return $this->usage();
        }
    }

    public function runCron() {
        $m = new moduleloader();
        $modules = $m->getAllModules();

        foreach ($modules as $module) {
            $name = $module['module_name'];
            $class = "\\modules\\$name\\cron";
            if (method_exists($class, 'run')) {
                moduleloader::includeModule($name);

                $c = new $class();
                $c->run();
            }
        }
    }

    /**
     * Add additional info to the cron log. Which module is running
     * at what time. Note: NOT USED
     * @param string $name name of the module running
     */
    public function cron_message($name) {
        // Log to cron.log
        $date = date::getDateNow(array('hms' => 1));
        $mes = $date . PHP_EOL;
        $mes .= "RUNNING: $name" . PHP_EOL;

        echo $mes;
    }

    public function usage() {

        $mes = "Add the following line to your crontab";
        common::echoMessage($mes);

        $command = conf::pathBase() . "/coscli.sh cron --run";
        $log = conf::pathBase() . "/logs/cron.log";
        $command = "* * * * * $command >> $log 2>&1";
        common::echoMessage($command);
        return 0;
    }
}
