<?php

namespace diversen\commands;

use diversen\conf;
use diversen\db\admin;
use diversen\file;
use diversen\time;
use diversen\cli\common;

class db {

    public function getHelp() {
        return
                array(
                    'usage' => 'MySQL database commands',
                    'options' => array(
                        '--show' => 'Show DB connection info',
                        '--drop' => 'Drop the database',
                        '--create' => 'Create database from settings in config/config.ini. Will drop database if it exists',
                        '--load-base' => 'Load the database with default sql',
                        '--load-dump' => 'Load specified file or latest dump found in backup/sql into db',
                        '--connect' => 'Connect to database using the MySQL shell',
                        '--clone' => 'Clone the database. Specify clone name',
                        '--save-dump' => 'Dump database to file. Will place dump in backup/sql if no file argument is specified'
                    ),
                    'arguments' => array(
                        'File|Database' => 'Specify a file or database name'
                    )
        );
    }
    
        /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {

        $arg = $args->getValueByKey(0);
        if ($args->getFlag('show')) {
            return $this->show();
        }        
        if ($args->getFlag('drop')) {
            return $this->dropDb();
        }
        if ($args->getFlag('create')) {
            return $this->create();
        }
        if ($args->getFlag('load-dump')) {
            return $this->loadDump($arg);
        }
        if ($args->getFlag('load-base')) {
            return $this->loadBase();
        }
        if ($args->getFlag('connect')) {
            return $this->connect();
        }
        if ($args->getFlag('clone')) {
            return $this->cloneDb($arg);
        }
        if ($args->getFlag('save-dump')) {
            return $this->saveDb($arg);
        }
        
        return 0;
    }

    /**
     * Helper function. Echo that config.ini needs an url if no url exists
     * @return int
     */
    public function noDbUrl() {
        common::echoMessage('No url in config/config.ini');
        return 1;
    }

    /**
     * shell callback
     * print_r db info
     * @param type $options
     */
    public function show() {
        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }
        print_r($db);
    }


    /**
     * Create a database
     * @param array $options
     * @return int $res 0 on success else a positive int
     */
    public function create() {

        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }

        $command = "mysqladmin -u" . conf::$vars['coscms_main']['username'] .
                " -p" . conf::$vars['coscms_main']['password'] . " -h$db[host] ";

        $command .= "--default-character-set=utf8 ";
        $command .= "CREATE $db[dbname]";
        return $ret = common::execCommand($command, $options);
    }

    /**
     * function for dropping database specified in config.ini
     * @return int $res the executed commands shell status 0 on success. 
     */
    public function dropDb($options = array()) {

        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }
        $command = "mysqladmin -f -u" . conf::$vars['coscms_main']['username'] .
                " -p" . conf::$vars['coscms_main']['password'] . " -h$db[host] ";
        $command .= "DROP $db[dbname]";
        return $ret = common::execCommand($command, $options);
    }

    /**
     * function for loading db with install sql found in scripts/default.sql
     * this function will also drop database if it exists
     * @return int $res the executed commands shell status 0 on success. 
     */
    public function loadBase() {

        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }

        $command = "mysql -u" . conf::$vars['coscms_main']['username'] . ' ' .
                "-p" . conf::$vars['coscms_main']['password'] . ' ' .
                "-h$db[host] " . ' ' .
                "$db[dbname] < scripts/default.sql";

        return common::execCommand($command);
    }

    /**
     * function for opening a connection to the database specified in config.ini
     * opens up the MySQL command line tool
     * @return  int     the executed commands shell status 0 on success.
     */
    public function connect() {
        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }

        $command = "mysql --default-character-set=utf8 -u" . conf::getMainIni('username') .
                " -p" . conf::getMainIni('password') .
                " -h" . $db['host'] .
                " $db[dbname]";

        $ret = array();
        proc_close(proc_open($command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));
    }

    /**
     * function for dumping a database specfied in config.ini to a file
     *
     * @param   array   Optional. If you leave empty, then the function will try
     *                  and find most recent sql dump and load it into database.
     *                  Set <code>$options = array('File' => '/backup/sql/latest.sql')</code>
     *                  for setting a name for the dump.
     * @return  int     the executed commands shell status 0 on success.
     */
    public function saveDb($file) {
        if (!$file) {
            common::echoMessage('You did not specify file to dump. We create one from current timestamp!');
            $dump_name = "backup/sql/" . time() . ".sql";
        } else {
            $dump_name = $file;
        }

        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }

        $command = "mysqldump --opt -u" . conf::$vars['coscms_main']['username'] .
                " -p" . conf::$vars['coscms_main']['password'] .
                " -h" . $db['host'];
        $command .= " $db[dbname] > $dump_name";
        common::execCommand($command);
    }

    /**
     * function for loading a database file into db specified in config.ini
     *
     * @param   array   options. You can specifiy a file to load in options.
     *                  e.g. <code>$options = array('File' => 'backup/sql/latest.sql')</code>
     * @return  int     the executed commands shell status 0 on success.
     */
    public function loadDump($file) {
        if (!$file) {
            common::echoMessage('You did not specify file to load. We use latest!');
            $latest = $this->getLatestDump();
            if ($latest == 0) {
                common::abort('Yet no database dumps');
            }

            $latest = "backup/sql/" . $latest . ".sql";
            $file = $latest;
        } else {
            if (!file_exists($file)) {
                common::abort("No such file: $file");
            }
        }
        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }

        $command = "mysql --default-character-set=utf8  -u" . conf::$vars['coscms_main']['username'] .
                " -p" . conf::$vars['coscms_main']['password'] . " -h$db[host]  $db[dbname] < $file";
        return $ret = common::execCommand($command);
    }

    /**
     * function for getting latest timestamp for dumps
     * 
     * default to backup/sql but you can specify a dir. 
     *
     * @param   string  $dir
     * @return  int     $timestamp
     */
    public static function getLatestDump($dir = null, $num_files = null) {
        if (!$dir) {
            $dir = conf::pathBase() . "/backup/sql";
        }
        $list = file::getFileList($dir);
        $time_stamp = 0;
        foreach ($list as $val) {
            $file = explode('.', $val);
            if (is_numeric($file[0])) {
                if ($file[0] > $time_stamp) {
                    $time_stamp = $file[0];
                }
            }
        }
        return $time_stamp;
    }

    public function cloneDb($new_db) {
        if (!$new_db) {
            common::abort('Specify new database name');
        }
        $db = admin::getDbInfo(conf::getMainIni('url'));
        $old = $db['dbname'];
        admin::cloneDB($old, $new_db);
    }
}
