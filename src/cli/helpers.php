<?php

namespace diversen\cli;

use diversen\autoloader\modules;
use diversen\conf;
use diversen\db;
use diversen\db\admin;
use diversen\db\connect;
use diversen\db\q;
use diversen\file;
use diversen\cli\common;
use diversen\intl;
use diversen\lang;
use diversen\log;
use diversen\moduleloader;
use modules\configdb\module;

/**
 * Helper class to minimal-cli-framework
 */

class helpers {

    public function bootCLi() {
        // The following enables the auto-loading of loading of \modules
        // It is commented away as it should be 
        $m = new modules();
        $m->autoloadRegister();

        // Define all essential paths. 
        // base_path has been enabled, and based on this we 
        // set htdocs_path, modules_path, files_dir
        conf::defineCommon();

        // Set include paths - based on config.ini
        // enable modules_path base_path as include_dirs
        conf::setIncludePath();

        // Load config file 
        conf::load();

        // set public file folder in file
        file::$basePath = conf::getFullFilesPath();

        // Set log level - based on config.ini

        $log_file = conf::pathBase() . '/logs/system.log';
        log::setErrorLogFile($log_file);
        if (conf::getMainIni('debug')) {
            log::enableDebug();
        }

        // Set locales
        intl::setLocale();

        // Set default timezone
        intl::setTimezone();

        // Enable translation
        $l = new lang();

        // Load all language files
        $base = conf::pathBase();
        $l->setDirsInsideDir("$base/modules/");
        $l->setDirsInsideDir("$base/htdocs/templates/");
        $l->setSingleDir("$base/vendor/diversen/simple-php-classes");
        $l->loadLanguage(conf::getMainIni('lang'));
    }

    public function dbConExists() {
        $db_conn = array(
            'url' => conf::getMainIni('url'),
            'username' => conf::getMainIni('username'),
            'password' => conf::getMainIni('password'),
            'db_init' => conf::getMainIni('db_init'),
            'dont_die' => 1
        );

        $ret = connect::connect($db_conn);
        if ($ret == 'NO_DB_CONN') {
            return false;
        }
        return true;
    }

    public function tablesExists() {
        $db = new db();
        //$ret = $db->connect(array('dont_die' => 1));
        //if ($ret == 'NO_DB_CONN') {
        //    return false;
        //}

        $info = admin::getDbInfo(conf::getMainIni('url'));
        if (!$info) {
            common::echoMessage('No databse url in config.ini');
            return false;
        }
        if ($info['scheme'] == 'mysql' || $info['scheme'] == 'mysqli') {
            $rows = $db->selectQuery("SHOW TABLES");
            if (empty($rows)) {
                return false;
            }
            return true;
        }

        if ($info['scheme'] == 'sqlite') {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='modules'";
            $rows = $db->selectQuery($sql);

            if (empty($rows)) {
                return false;
            }
            return true;
        }
    }

    public function getModuleCommands() {

        if (!$this->tablesExists()) {
            common::echoMessage('No tables exists. We can not load modules in modules/ dir');
            return;
        }

        $ml = new moduleloader();

        // select all db settings and merge them with ini file settings
        $db_settings = [];
        
        if (admin::tableExists('settings')) {
            $db_settings = q::select('settings')->filter('id =', 1)->fetchSingle();
        }
        // merge db settings with config/config.ini settings
        // db settings override ini file settings
        conf::$vars['coscms_main'] = array_merge(conf::$vars['coscms_main'], $db_settings);

        $commands = [];
        $modules = moduleloader::getAllModules();

        foreach ($modules as $val) {
            // moduleloader::setModuleIniSettings($val['module_name']);
            $path = conf::pathModules() . "/$val[module_name]/command.php";
            if (file_exists($path)) {

                $class = '\modules\\' . "$val[module_name]\command";
                $obj = new $class();
                // die;
                $help = $obj->getCommand();
                $name = $help['name'];


                $commands[$name] = $obj;
            }
        }

        // Override any setting with configdb setting if module exists
        if (moduleloader::moduleExists('configdb')) {
            $c = new module();
            $c->overrideAll();
        }
        return $commands;
    }
}
