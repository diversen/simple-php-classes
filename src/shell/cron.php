<?php

use diversen\cli\common;
use diversen\conf;
use diversen\moduleloader;
use diversen\date;

function cron_run () {
    $m = new moduleloader();
    $modules = $m->getAllModules();
    
    foreach($modules as $module) {
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
function cron_message($name) {
    // Log to cron.log
    $date = date::getDateNow(array('hms' => 1));
    $mes = $date . PHP_EOL;
    $mes.= "RUNNING: $name" . PHP_EOL;

    echo $mes;
}

function cron_install() {
    
    $mes = "Add the following line to your crontab";
    common::echoMessage($mes);
    
    $command = conf::pathBase() . "/coscli.sh cron --run";
    $log = conf::pathBase() . "/logs/cron.log";
    $command = "* * * * * $command >> $log 2>&1"; 
    common::echoMessage($command);
    return 0;
}


self::setCommand('cron', array(
    'description' => 'Cron command.',
));

self::setOption('cron_run', array(
    'long_name'   => '--run',
    'description' => 'Runs the cron jobs',
    'action'      => 'StoreTrue'
));

self::setOption('cron_install', array(
    'long_name'   => '--install',
    'description' => 'See how to install cron line in crontab for this system.',
    'action'      => 'StoreTrue'
));
