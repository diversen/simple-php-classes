<?php

namespace diversen\commands;

use diversen\conf;
use diversen\file;
use diversen\cli\common;
use diversen\git;

class promptInstall {

    /**
     * Define shell command and options
     * @return array $ary
     */
    public function getCommand() {
        return 
            array (
                'usage' => 'Prompt install. Asks questions and install',
                'options' => array (
                    '--install' => 'Prompt user for info and install')
            );
    }

    /**
     * Run the command
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {
        if ($args->getFlag('install')) {
            $this->promptInstallRun();
        }
    }

    /**
     * function for doing a prompt install from shell mode
     * is a wrapper around other shell functions.
     */
    public function promptInstallRun() {
        global $argv;
        
        common::echoMessage('Pick a version to install:');

        $tags = git::getTagLatest() . PHP_EOL;
        $tags .= "master";

        common::echoMessage($tags);
        $tag = common::readSingleline("Enter tag (version) to use:");

        common::execCommand("git checkout $tag");

        // Which profile to install
        $profiles = file::getFileList('profiles', array('dir_only' => true));
        if (count($profiles) == 1) {
            $profile = array_pop($profiles);
        } else {
            common::echoMessage("List of profiles: ");
            foreach ($profiles as $val) {
                common::echoMessage("\t" . $val);
            }

            // select profile and load it
            $profile = common::readSingleline('Enter profile, and hit return: ');
        }

        common::echoMessage("Loading the profile '$profile'");

        // Load config.ini
        $p = new \diversen\profile();
        $p->loadConfigIni($profile);

        common::echoMessage("Main configuration (placed in config/config.ini) for '$profile' is loaded");
        
        // Keep base path. Ortherwise we will lose it when loading profile    
        $base_path = conf::pathBase();

        // Load the default config.ini settings as a skeleton
        conf::$vars['coscms_main'] = conf::getIniFileArray($base_path . '/config/config.ini', true);

        // Reset base path
        conf::setMainIni('base_path', $base_path);
        conf::defineCommon();

        common::echoMessage("Enter MySQL credentials");

        // Get configuration info
        $host = common::readSingleline('Enter your MySQL host: ');
        $database = common::readSingleline('Enter database name: ');
        $username = common::readSingleline('Enter database user: ');
        $password = common::readSingleline('Enter database users password: ');
        $server_name = common::readSingleline('Enter server host name: ');

        common::echoMessage("Writing database connection info to main configuration");

        // Assemble configuration info
        conf::$vars['coscms_main']['url'] = "mysql:dbname=$database;host=$host;charset=utf8";
        conf::$vars['coscms_main']['username'] = $username;
        conf::$vars['coscms_main']['password'] = $password;
        conf::$vars['coscms_main']['server_name'] = $server_name;

        // Write it to ini file
        $content = conf::arrayToIniFile(conf::$vars['coscms_main'], false);
        $path = conf::pathBase() . "/config/config.ini";
        file_put_contents($path, $content);

        common::echoMessage("Your can also always change the config/config.ini file manually");

        $options = array();
        $options['profile'] = $profile;
        if ($tag == 'master') {
            conf::setMainIni('git_use_master', 1);
        }

        common::echoMessage("Will now clone and install all modules");
        
        $i = new \diversen\commands\install();
        $i->installSystem($profile);

        common::echoMessage("Create a super user");
        
        $u = new \diversen\commands\useradd();
        $u->useraddSuper();

        $login = "http://$server_name/account/login/index";

        common::echoMessage("If there was no errors you will be able to login at $login");
        common::echoMessage("Remember to change file permissions. This will require super user");
        common::echoMessage("E.g. like this:");
        
        $program = $argv[0];
        common::echoMessage("sudo $program file --chmod-system");
    }

    /**
     * @deprecated 
     * @return type
     */
    public function get_password() {
        $site_password = common::readSingleline('Enter system user password, and hit return: ');
        $site_password2 = common::readSingleline('Retype system user password, and hit return: ');
        if ($site_password == $site_password2) {
            return $site_password;
        } else {
            get_password();
        }
    }

}
