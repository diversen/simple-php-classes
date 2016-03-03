<?php

namespace diversen\db;

use diversen\conf;
use PDO;
use PDOException;

/**
 * Create a connection to some sort of database
 * 
 * @package main
 */

class connect {
    
    /**
     * database handle 
     */
    public static $dbh = null;
    
    /*
     * Flag indicating if there is a connection 
     */
    public static $con = null;
    
    /** 
     * var that holds all sql statements fro debug purpose
     */
    public static $debug = array();
    
    /**
     * Connect to a database using an array with some of these arguments
     * <code>array('url', 'username', 'password', 'dont_die', 'db_init')</code>
     * @param array $options 
     * @return void
     */
    public function __construct($options = null) {
        if (!self::$dbh) {
            self::$dbh->connect($options);
        }
    }
    
    /**
     * Connect to a database using an options array 
     * <code>array('url', 'username', 'password', 'dont_die', 'db_init')</code>
     * If the array is empty then try to read from a configuration file.
     * @param array $options 
     * @return void|string void or 'NO_DB_CON' if fail on connect
     */
    public static function connect($options = null){

        self::$debug[] = "Trying to connect with " . conf::getMainIni('url');
        
        if (isset($options['url'])) {
            $url = $options['url'];
            $username = $options['username'];
            $password = $options['password'];
        } else {
            $url = conf::getMainIni('url');
            $username = conf::getMainIni('username');
            $password = conf::getMainIni('password');
        }

        if (conf::getMainIni('db_dont_persist') == 1) {
            $con_options = array();
        } else {
            $con_options = array('PDO::ATTR_PERSISTENT' => true);
        }
        
        try {    
            self::$dbh = new PDO(
                $url,
                $username,
                $password, 
                $options
            );
            
            // Exception mode
            self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // set SSL
            self::setSsl();
	    
            // init
            if (conf::getModuleIni('db_init')) {
                self::$dbh->exec(conf::getModuleIni('db_init'));
            }

        // Catch Exception
        } catch (PDOException $e) {
            if (!$options){
                self::fatalError ('Connection failed: ' . $e->getMessage());
            } else {
                if (isset($options['dont_die'])){
                    self::$debug[] = $e->getMessage();
                    self::$debug[] = 'No connection';
                    return "NO_DB_CONN";
                }
            }
        }
        self::$con = true;
        self::$debug[]  = 'Connected!';
    }
    
    /**
     * Set SSL for mysql if SSL is set in the configuration,
     * experimental
     * @return void
     */
    public static function setSsl() {
        $attr = conf::getMainIni('mysql_attr');
        if (isset($attr['mysql_attr'])) {
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_KEY, $attr['ssl_key']);
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_CERT, $attr['ssl_cert']);
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_CA, $attr['ssl_ca']);
        }
    }
    
    /**
     * Method for showing fatal database errors
     * @param string $msg the message to show with the backtrace
     * @return void
     */
    protected static function fatalError($msg) {
        self::$debug[] = "Fatal error encountered";
        echo "<pre>Error!: $msg\n";
        $bt = debug_backtrace();
        foreach($bt as $line) {
            $args = var_export($line['args'], true);
            echo "{$line['function']}($args) at {$line['file']}:{$line['line']}\n";
        }
        echo "</pre>";
        die();
    }
}
