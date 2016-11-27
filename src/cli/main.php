<?php

namespace diversen\cli;

use diversen\autoloader\modules;
use diversen\cli;
use diversen\cli\common;
use diversen\conf;
use diversen\db;
use diversen\db\admin;
use diversen\db\q;
use diversen\db\connect;
use diversen\file;
use diversen\intl;
use diversen\lang;
use diversen\log;
use diversen\moduleloader;

/**
 * Main cli command for the framework
 * All framework commands are placed in src/shell, one file per command,
 * and the system can easily be extended by modules.
 * @example This is the coscli.sh command
<code>
#!/usr/bin/env php
<?php

include_once "vendor/autoload.php";
use diversen\conf;
use diversen\cli\main as mainCli;

$path = dirname(__FILE__);
conf::setMainIni('base_path', $path); 

mainCli::init();
$ret = mainCli::run();
exit($ret);
</code>
 */

class main extends cli {
    
    /**
     * - Init the CLI command
     * - Set up autoloading
     * - Define common
     * - Set include_path
     * - Load language
     * - Set timeezone 
     * - Load translation
     * @return void
     */
    public static function init() {

        // Autoload modules
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
        
        $log_file = conf::pathBase() . '/logs/coscms.log';
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
        
        // Init parent with base commands
        parent::init();
        
        // Make a cool description
        self::$parser->description = <<<EOF
                    _ _       _     
  ___ ___  ___  ___| (_)  ___| |__  
 / __/ _ \/ __|/ __| | | / __| '_ \ 
| (_| (_) \__ \ (__| | |_\__ \ | | |
 \___\___/|___/\___|_|_(_)___/_| |_|

    Modulized Command line program

EOF;
        self::$parser->version = '0.0.1';

        // Adding a main option for setting domain
        self::$parser->addOption(
            'domain', array(
            'short_name' => '-d',
            'long_name' => '--domain',
            'description' => 'Domain to use if using multi hosts. If not set we will use default domain',
            'action' => 'StoreString',
            'default' => 'default',
                )
        );
        
        self::beforeParse();
    }
    
    /**
     * Run the command line afterloading all modules                           
     * @return int $ret 0 on success any other int is failure
     */
    public static function run() {
        
        $result = self::parse();
        self::afterParse($result);
        
        // Execute the result
        $ret = self::execute($result);
        
        // Exit with result from execution
        exit($ret);
    }

    /**
     * After the commandline options has been parsed. 
     * Examine the --domain flag and the --verbose flag
     * As these options needs to be examined to see if 
     * we e.g. needs to load and merge another config file
     * E.g. a virtual a sub-domain
     * @return void
     */
    public static function afterParse($result) {
        
        // Set domain and first level conf vars
        // Else they may be merged when we load a multi 
        // domain config file.
        $verbose = $result->options['verbose'];
        conf::$vars['verbose'] = $verbose;
        
        // Check if other domain than default is being used
        $domain = $result->options['domain'];
        conf::$vars['domain'] = $domain;

        if ($domain != 'default' || empty($domain)) {
            $domain_ini = conf::pathBase() . "/config/multi/$domain/config.ini";
            if (!file_exists($domain_ini)) {
                common::abort("No such domain - no configuration found: '$domain_ini'");
            } else {
                // If domain is used - Load domain specific configuration
                conf::loadMainCli();
            }
        }
    }

    /**
     * Before parsing of the commandline options
     * This loads all commandline modules from file system
     * and any modules found in the database
     * @return void
     */
    public static function beforeParse () {
        self::loadBaseModules();
        $url = conf::getMainIni('url');
        if ($url) {
            self::loadDbModules();
        }
    }

    /**
     * Loads all modules found in database if *'modules'* table exists
     * Else the CLI command may be used for e.g. an install. 
     * @return void
     */
    public static function loadDbModules (){        
          
        if (!self::tablesExists()) {
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

        $modules = moduleloader::getAllModules();
        foreach ($modules as $val) {
            moduleloader::setModuleIniSettings($val['module_name']);
            $path = conf::pathModules() . "/$val[module_name]/$val[module_name].inc";
            if (file_exists($path)) {
                include_once $path;
            }
        }

        // Override any setting with configdb setting if module exists
        if (moduleloader::moduleExists('configdb')) {
            $c = new \modules\configdb\module();
            $c->overrideAll();
        }
    }
    
        
    /**
     * Loads all base modules
     * Base modules are placed in *vendor/diversen/simple-php-classes/src/shell*
     * @return void
     */
    public static function loadBaseModules () {
        $command_path = __DIR__ . "/../shell";
        $base_list = file::getFileList($command_path, array ('search' => '.php'));

        foreach ($base_list as $val){
            include_once $command_path . "/$val";
        }
    }
    
    /**
     * Checks if any table exist in database
     * @return boolean $res true if table exists else false
     */
    public static function tablesExists () {

        $db_conn = array (
            'url' => conf::getMainIni('url'),
            'username' => conf::getMainIni('username'),
            'password' => conf::getMainIni('password'),
            'db_init' => conf::getMainIni('dont_die'),
            'dont_die' => 1     
        );
        
        $ret = connect::connect($db_conn);
        if ($ret == 'NO_DB_CONN'){
            return false;
        }
        
        $db = new db();
        
        $info = admin::getDbInfo(conf::getMainIni('url'));
        if (!$info) {
            common::echoMessage('No databse url in config.ini');
        }
        if ($info['scheme'] == 'mysql' || $info['scheme'] == 'mysqli') {
            $rows = $db->selectQuery("SHOW TABLES");
            if (empty($rows)){
                return false; 
            }
            return true;
        }

        if ($info['scheme'] == 'sqlite')  {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='modules'";
            $rows = $db->selectQuery($sql);
            
            if (empty($rows)){
                return false; 
            }
            return true;    
        }
    } 
}
