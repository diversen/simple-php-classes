<?php

namespace diversen\commands;

use diversen\cli\common;
use diversen\conf;

/**
 * Function for changing group settings on some files, which the server needs to 
 * have read and write access to. 
 * 
 * Change these files, so anon users does not have any access to them. 
 *
 * We read the user under which the web server is running. This is done by fetching
 * the `htdocs/whoami.php` script from the server. 
 *
 * @return void
 */
class fileSystem {

    public function getHelp() {
        return 
            array (
                'usage' => 'Basic files commands',
                'options' => array (
                    '--chmod-system' => 'Chmod and chown of htdocs/files to be used by server-user',
                    '--chmod-user' => 'Chmod and chown of htdocs/files to be used by current user',
                    '--clean-up' => 'Clean up files in htdocs/files and in htdocs/logo',
                    '--create-logs' => 'Create system.log file'),
            );
    }
    
    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {
        
        if ($args->getFlag('chmod-system')) {
            $this->chmodSystem();
        }
        if ($args->getFlag('chmod-user')) {
            $this->chmodUser();
        }
        if ($args->getFlag('clean-up')) {
            $this->removeFiles();
        }
        if ($args->getFlag('create-logs')) {
            $this->createLogs();
        }
    }

    
    public function chmodSystem() {

        // Get group
        echo $group = conf::getServerUser();
        if (!$group) {
            common::abort('Could not fetch server user. Check if server_name is set in config/config.ini, and check if this server is running');
        }

        common::needRoot();

        $files_path = $this->getFilesToChmod();
        $command = "chgrp -R $group $files_path";
        common::execCommand($command);
        $command = "chmod -R 770 $files_path";
        common::execCommand($command);
    }

    /**
     * Function for changing all files to be owned by user.
     * Change file to owned by owner (the user logged in)
     * Public `files` will then be set to 777
     *
     * @return int  value from exec command
     */
    public function chmodUser() {

        $owner = '';

        // Note: On PHP 7.0.8-0ubuntu0.16.04.2 this does not 
        // yield any result. Maybe it is a bug - or maybe it is the linux system
        if (function_exists('posix_getlogin')) {
            $owner = posix_getlogin();
        }

        // Note: This gets the owner of the current script running
        // exec('whoami') yields root if we sudo, so this is the better option
        // even though it is not optimal
        if (!$owner) {
            $owner = get_current_user();
        }

        common::needRoot();

        $command = "chown -R $owner:$owner $files_path";
        common::execCommand($command);
        $command = "chmod -R 770 $files_path";
        common::execCommand($command);
    }

    /**
     * Get files which may need some special settings according to the web-server
     * @return string $str
     */
    public function getFilesToChmod() {

        $files_path = conf::pathFiles() . " ";
        $files_path .= conf::pathBase() . '/logs ';
        $files_path .= conf::pathBase() . '/private ';
        $files_path .= conf::pathBase() . '/config/multi ';
        $files_path .= conf::pathBase() . '/config/config.ini ';
        return $files_path;
    }

    /**
     * function for removing all files in htdocs/files/*, htdocs/logo/*
     * when doing an install
     *
     * @return int  value from exec command
     */
    public function removeFiles() {
        common::needRoot();
        $files_path = conf::pathFiles() . "/*";
        $command = "rm -Rf $files_path";
        common::execCommand($command);
    }

    /**
     * Create default files for system.
     * @return int  value from exec command
     */
    public function createLogs() {
        $files_path = conf::pathBase() . '/logs/system.log';
        if (!file_exists($files_path)) {
            $command = "touch $files_path";
            common::execCommand($command);
        }

        $files_path = conf::pathBase() . '/logs/cron.log';
        if (!file_exists($files_path)) {
            $command = "touch $files_path";
            common::execCommand($command);
        }

        $files_path = conf::pathFiles();
        if (!file_exists($files_path)) {
            $command = "mkdir -p $files_path";
            common::execCommand($command);
        }
    }
}
