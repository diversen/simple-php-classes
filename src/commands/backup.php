<?php

namespace diversen\commands;

use diversen\conf;
use diversen\file;
use diversen\time;
use diversen\cli\common;

class backup {

    public function getHelp() {
        return
                array(
                    'usage' => 'Create and restore file and MySQL backups.',
                    'options' => array(
                        '--full' => "Backup all files and preserve ownership. If you don't specify a file then the script will create backup from current timestamp",
                        '--public' => 'Backup public html files in htdocs/files and preserve ownership',
                        '--full-restore' => 'Restore all files from a backup and regenerate original ownership. If no file is given when restoring backup, then the backup with latest timestamp will be used',
                        '--public-restore' => "Restore public htdocs/files from a backup and preserve ownership"),
                    'arguments' => array(
                        'file' => 'Specify an optional filename for the backup'
                    )
        );
    }

    /**
     * Run command
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {

        $options = [];
        $filename = $args->getValueByKey(0);
        if ($filename) {
            $options['File'] = $filename;
        }
        
        if ($args->getFlag('full')) {
            return $this->full($options);
        }
        
        if ($args->getFlag('full-restore')) {
            return $this->fullRestore($options);
        }
        
        if ($args->getFlag('public')) {
            return $this->files($options);
        }
        
        if ($args->getFlag('public-restore')) {
            return $this->filesRestore($options);
        }
        
        return 0;
    }

    /**
     * 
     * CLI command: Make a backup of all sources. 
     *
     * Archive will be placed in backup/full dir.
     * You can specifiy an exact filename on the commandline. If you don't use a filename
     * in the options array, the archive will be named after current timestamp, e.g.
     * backup/full/1264168904.tar
     *
     * @param   array   $options Input from commandline, e.g.
     *                  array('File' => 'backup/full/latest.tar') This will create
     *                  the backup file backup/full/latest.tar
     *                  Leave options empty if you want to use current timestamp for 
     *                  your achive.
     * @return  int     $int the executed commands shell status. 0 on success. 
     */
    public function full($options) {
        common::needRoot();

        // default backup dir
        if (isset($options['File'])) {
            // we use full path when specifing a file
            $backup_file = $options['File'];
        } else {
            $backup_file = "backup/full/" . time() . ".tar.gz";
        }
        $command = "tar -Pczf $backup_file --exclude=backup* -v . ";
        $ret = common::execCommand($command);
    }

    /**
     * CLI command: Make a backup of all public files 
     *
     * @param   array   options to parser, e.g.
     *                  `array('File' => 'backup/files/latest.tar')` This will create
     *                  the backup file `backup/files/latest.tar`
     *                  Leave options empty if you want to use current timestamp for 
     *                  your achive.
     * @return  int     the executed commands shell status 0 on success. 
     */
    public function files($options) {
        common::needRoot();
        // default backup dir
        if (isset($options['File'])) {
            // When file is set we use it as the backup file path
            $backup_file = $options['File'];
        } else {
            $backup_file = "backup/files/" . time() . ".tar.gz";
        }
        $command = "tar -Pczf $backup_file -v ./htdocs/files ";
        $ret = common::execCommand($command);
    }

    /**
     * CLI command: Function for restoring full tar archive
     * All file settings are restored (if user is the owner of all files)
     *
     * @param   array   options to parser, e.g.
     *                  array('File' => '/backup/full/latest.tar') This will restore
     *                  the tar achive /backup/full/latest.tar
     *
     *                  Leave options empty if you
     *                  want to restore latest archive with highest timestamp, .e.g
     *                  backup/full/1264168904.tar
     * @return  int     the executed commands shell status 0 on success. 
     */
    public function fullRestore($options) {

        common::needRoot();
        if (!isset($options['File'])) {
            $latest = $this->getLatest();
            if ($latest == 0) {
                die("Yet no backups\n");
            }
            $backup_file = $latest = "backup/full/" . $latest . ".tar.gz";
        } else {
            $backup_file = $options['File'];
        }

        $command = "tar -Pxzf $backup_file --exclude=backup* -v . ";
        $ret = common::execCommand($command);
    }

    /**
     * CLI command. Function for restoring tar archive
     * All file settings are restored (if user is the owner of all files)
     *
     * @param   array   options to parser, e.g.
     *                  <code>array('File' => '/backup/full/latest.tar')</code> This will restore
     *                  the tar achive /backup/full/latest.tar
     *
     *                  Leave options empty if you
     *                  want to restore latest archive with highest timestamp, .e.g
     *                  backup/full/1264168904.tar
     * @return  int     the executed commands shell status 0 on success. 
     */
    public function filesRestore($options) {

        if (!isset($options['File'])) {
            $latest = $this->getLatest('files');
            if ($latest == 0) {
                die("Yet no backups\n");
            }
            $backup_file = $latest = "backup/files/" . $latest . ".tar.gz";
        } else {
            $backup_file = $options['File'];
        }

        common::needRoot("In order to restore: $backup_file. Run command as root");
        $command = "tar -Pxzf $backup_file -v ./htdocs/files ";
        $ret = common::execCommand($command);
    }

    /**
     * CLI command: function for getting latest timestamp from /backup/full dir
     *
     * @return int   backup with most recent timestamp
     */
    function getLatest($type = null) {
        if ($type == 'files') {
            $dir = conf::pathBase() . "/backup/files";
        } else {
            $dir = conf::pathBase() . "/backup/full";
        }
        $list = file::getFileList($dir);
        $time_stamp = 0;
        foreach ($list as $key => $val) {
            $file = explode('.', $val);
            if (is_numeric($file[0])) {
                if ($file[0] > $time_stamp) {
                    $time_stamp = $file[0];
                }
            }
        }
        return $time_stamp;
    }
}
