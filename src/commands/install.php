<?php

namespace diversen\commands;

use diversen\conf;
use diversen\moduleinstaller;
use diversen\profile;
use diversen\cli\common;
use diversen\db\connect;

class install {


    public function getHelp() {
        return
                array(
                    'usage' => 'install complete system. Note: prompt-install is easier',
                    'options' => array(
                        '-m' => 'use master. Use if you have all module sources, then the master will be installed',
                        '--install' => 'install system',
                        '--check-root' => 'Dummy command (used when installing system from shell). Checks if user is super user (root)',
                        '--config-reload' => 'Reloads the module table, database routes, and menu items'
                    ),
                    'arguments' => array(
                        'profile' => 'Specify the profile to install'
                    ),
        );
    }
    
    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {
        
        $profile = $args->getValueByKey(0);
        
        if ($args->getFlag('m')) {
            $this->useMaster();
        }
        
        if ($args->getFlag('install')) {
            return $this->installSystem($profile);
        }
        
        if ($args->getFlag('check-root')) {
            return $this->checkRoot();
        }
        
        if ($args->getFlag('config-reload')) {
            return $this->configReload();
        }
    }

    /**
     * function for installing coscms from a profile
     */
    public function installSystem($profile) {

        // we need a profile specified
        if (!$profile) {
            common::abort('You need to specifiy a profile');
        }

        // create files - logs/ - files/
        $f = new \diversen\commands\fileSystem();
        $f->createLogs();
        // cos_create_files();

        $d = new \diversen\commands\db;
        $d->dropDb(array('silence' => 1));

        // create database
        $d->create();

        // load default base sql.
        $d->loadBase();

        // Connect to db
        $db_conn = array(
            'url' => conf::getMainIni('url'),
            'username' => conf::getMainIni('username'),
            'password' => conf::getMainIni('password'),
            'db_init' => conf::getMainIni('db_init')
        );
        connect::connect($db_conn);

        // Set up profile to install
        $pro = new profile();
        $pro->setProfileInfo($profile);

        // install all the profile modules
        $g = new \diversen\commands\gitCommand();
        foreach ($pro->profileModules as $key => $val) {

            // check if master is specified. Else use profile version
            if (conf::getMainIni('git_use_master')) {
                $tag = 'master';
            } else {
                $tag = $val['module_version'];
            }

            // check out and install
            $g->installMod($val['public_clone_url'], $tag);
        }

        // install templates
        foreach ($pro->profileTemplates as $key => $val) {

            if (conf::getMainIni('git_use_master')) {
                $tag = 'master';
            } else {
                $tag = $val['module_version'];
            }

            // check out and install
            $g->installMod($val['public_clone_url'], $tag, 'template');
        }

        // load all profile ini files
        $pro->loadProfileFiles($profile);

        // set template
        $pro->setProfileTemplate();
    }

    /**
     * wrapper function for reloading all languages
     */
    public function configReload() {
        $reload = new moduleinstaller();
        $reload->reloadConfig();
    }

    /**
     * cli call function is --master is set then master will be used instead of
     * normal tag
     *
     * @param array $options
     */
    public function useMaster() {
        conf::setMainIni('git_use_master', 1);
    }

    public function checkRoot() {
        common::needRoot();
    }
}
