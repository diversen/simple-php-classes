<?php

use diversen\cli\common;
use diversen\conf;

/**
 * File containing shell commands for file operations
 */

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
function cos_chmod_files(){

    // Get group
    $group = conf::getServerUser();
    if (!$group) {
        common::abort('Could not fetch server user. Check if server_name is set in config/config.ini, and check if this server is running');
    }
    
    common::needRoot();
        
    $files_path = cos_files_group();
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
function cos_chmod_files_owner(){
    
    $owner = '';
    
    // Note: On PHP 7.0.8-0ubuntu0.16.04.2 this does not 
    // yield any result. Maybe it is a bug - or maybe it is the linux system
    if (function_exists('posix_getlogin')){
        $owner = posix_getlogin();
    } 
    
    // Note: This gets the owner of the current script running
    // exec('whoami') yields root if we sudo, so this is the better option
    // even though it is not optimal
    if (!$owner) {
        $owner = get_current_user();
    }

    common::needRoot();
    
    $files_path = cos_files_group();
    
    $command = "chown -R $owner:$owner $files_path";
    common::execCommand($command);
    $command = "chmod -R 770 $files_path";
    common::execCommand($command);
}

/**
 * Get files which may need some spcial settings according to the web-server
 * @return string $str
 */
function cos_files_group() {
        
    $files_path = conf::pathFiles() . " ";
    $files_path.= conf::pathBase() . '/logs ';
    $files_path.= conf::pathBase() . '/private ';
    $files_path.= conf::pathBase() . '/config/multi ';
    $files_path.= conf::pathBase() . '/config/config.ini ';
    return $files_path;
}

/**
 * function for removing all files in htdocs/files/*, htdocs/logo/*
 * when doing an install
 *
 * @return int  value from exec command
 */
function cos_rm_files(){
    common::needRoot();
    $files_path = conf::pathFiles() . "/*";
    $command = "rm -Rf $files_path";
    common::execCommand($command);
}

/**
 * function for removing all files in htdocs/files/*, htdocs/logo/*
 * when doing an install
 *
 * @return int  value from exec command
 */
function cos_create_files(){
    $files_path = conf::pathBase() . '/logs/coscms.log';
    if (!file_exists($files_path)){
        $command = "touch $files_path";
        common::execCommand($command);
    }
    
    $files_path = conf::pathBase() . '/logs/cron.log';
    if (!file_exists($files_path)){
        $command = "touch $files_path";
        common::execCommand($command);
    }

    $files_path = conf::pathFiles();
    if (!file_exists($files_path)){
        $command = "mkdir -p $files_path";
        common::execCommand($command);
    }
}

self::setCommand('file', array(
    'description' => 'Basic files commands.',
));

self::setOption('cos_chmod_files', array(
    'long_name'   => '--chmod-files',
    'description' => 'Will try to chmod and chown of htdocs/files',
    'action'      => 'StoreTrue'
));

self::setOption('cos_chmod_files_owner', array(
    'long_name'   => '--chmod-files-owner',
    'description' => 'Will try to chmod and chown of htdocs/files to current user',
    'action'      => 'StoreTrue'
));

self::setOption('cos_rm_files', array(
    'long_name'   => '--rm-files',
    'description' => 'Will remove files in htdocs/files and in htdocs/logo',
    'action'      => 'StoreTrue'
));

self::setOption('cos_create_files', array(
    'long_name'   => '--create-files',
    'description' => 'Will create log file: log/coscms.log',
    'action'      => 'StoreTrue'
));
