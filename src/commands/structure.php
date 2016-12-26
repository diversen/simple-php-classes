<?php

namespace diversen\commands;

use diversen\conf;
use diversen\db\admin;
use diversen\time;
use diversen\cli\common;

class structure {


    public function getHelp() {
        return
                array(
                    'usage' => 'MySQL database commands',
                    'options' => array(
                        '--db' => 'Outputs complete table structure for the database',
                        '--table' => 'Output table structure for a single table',
                        '--backup-table' => 'Backup a single table',
                        '--load-table' => 'Load a dump of a table. If no file is specfied then use lateset backup of the table'
                        ),
                    'arguments' => array(
                        'table' => 'The table to output structure of'
                    )
        );
    }

        /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {

        $arg = $args->getValueByKey(0);
        
        if ($args->getFlag('db')) {
            return $this->completeDb();
        }
        
        if ($args->getFlag('table')) {
            if (!$arg) {
                return common::abort('Specify table to use');
            }
            return $this->dumpDefinition($arg);
        }
        if ($args->getFlag('backup-table')) {
            if (!$arg) {
                return common::abort('Specify table to use');
            }
            return $this->dumpTable($arg);
        }
        if ($args->getFlag('load-table')) {
            if (!$arg) {
                return common::abort('Specify table to use');
            }
            return $this->loadTable ($arg);
        }
        return 0;
    }
    
    /**
     * dumps entire structure
     */
    function completeDb() {
        $ary = admin::getDbInfo(conf::getMainIni('url'));
        if (!$ary) {
            return $this->noDbUrl();
        }

        $user = conf::getMainIni('username');
        $password = conf::getMainIni('password');
        $command = "mysqldump -d -h $ary[host] -u $user -p$password $ary[dbname]";
        common::execCommand($command);
    }

    /**
     * dump single table structure
     * @param string $table
     */
    function dumpDefinition($table) {
        $ary = admin::getDbInfo(conf::getMainIni('url'));
        if (!$ary) {
            return $this->noDbUrl();
        }

        $user = conf::getMainIni('username');
        $password = conf::getMainIni('password');

        $dump_dir = "backup/sql/$table";
        if (!file_exists($dump_dir)) {
            mkdir($dump_dir);
        }

        $dump_name = "backup/sql/$table/" . time() . ".sql";

        $command = "mysqldump -d -h $ary[host] -u $user -p$password $ary[dbname] $table > $dump_name";
        common::execCommand($command);
    }

    /**
     * Dump a single table
     * @param string $table
     * @return int
     */
    function dumpTable($table) {

        $dump_dir = "backup/sql/$table";
        if (!file_exists($dump_dir)) {
            mkdir($dump_dir);
        }

        $dump_name = "backup/sql/$table/" . time() . ".sql";
        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }

        $command = "mysqldump --opt -u" . conf::$vars['coscms_main']['username'] .
                " -p" . conf::$vars['coscms_main']['password'];
        $command .= " $db[dbname] $table > $dump_name";
        common::execCommand($command);
    }

    /**
     * function for loading a database file into db specified in config.ini
     *
     * @param   array   options. You can specifiy a file to load in options.
     *                  e.g. <code>$options = array('File' => 'backup/sql/latest.sql')</code>
     * @return  int     the executed commands shell status 0 on success.
     */
    public function loadTable($table) {

        $dump_dir = "backup/sql/$table";
        if (!file_exists($dump_dir)) {
            common::abort('Yet no backups');
        }

        $search = conf::pathBase() . "/backup/sql/$table";
        $latest = \diversen\commands\db::getLatestDump($search);
        if ($latest == 0) {
            common::abort('Yet no database dumps');
        }

        $latest = "backup/sql/$table/" . $latest . ".sql";
        $db = admin::getDbInfo(conf::getMainIni('url'));
        if (!$db) {
            return $this->noDbUrl();
        }

        $command = "mysql --default-character-set=utf8  -u" . conf::$vars['coscms_main']['username'] .
                " -p" . conf::$vars['coscms_main']['password'] . " $db[dbname] < $latest";
        return $ret = common::execCommand($command);
    }
    
    /**
     * Helper function. Echo that config.ini needs an url if no url exists
     * @return int
     */
    public function noDbUrl() {
        common::echoMessage('No url in config/config.ini');
        return 1;
    }
}
