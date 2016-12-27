<?php

namespace diversen\commands;

use diversen\db;
use diversen\db\admin;
use diversen\cli\common;
use diversen\conf;

class useradd {

    /**
     * Define shell command and options
     * @return array $ary
     */
    public function getHelp() {
        return
                array(
                    'usage' => 'Create users from commandline',
                    'options' => array(
                        '--super' => 'Add a super user from prompt',
                        '--admin' => 'Add an admin user from prompt',
                        '--user' => 'Add a user from prompt',
                        )
        );
    }
    
    /**
     * Run the command
     * @param \diversen\parseArgv $args
     */
    public function runCommand ($args) {
        
        if ($args->getFlag('super')) {
            return $this->useraddSuper();
        }
        if ($args->getFlag('admin')) {
            return $this->useraddAdmin();
        }
        if ($args->getFlag('user')) {
            return $this->useraddUser();
        }
        
    }
    /**
     * Create a super user from commandline
     * @return int $res
     */
    public function useraddSuper() {

        $values['email'] = common::readSingleline("Enter Email of super user (you will use this as login): ");
        $values['password'] = common::readSingleline("Enter password: ");
        $values['password'] = md5($values['password']);
        $values['username'] = $values['email'];
        $values['verified'] = 1;
        $values['admin'] = 1;
        $values['super'] = 1;

        $values['type'] = 'email';
        $res = $this->useraddInsert($values);
        if ($res) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Create a user from commandline
     * @return int $res
     */
    public function useraddUser() {

        $values['email'] = common::readSingleline("Enter Email of super user (you will use this as login): ");
        $values['password'] = common::readSingleline("Enter password: ");
        $values['password'] = md5($values['password']);
        $values['username'] = $values['email'];
        $values['verified'] = 1;
        $values['admin'] = 0;
        $values['super'] = 0;

        $values['type'] = 'email';
        $res = $this->useraddInsert($values);
        if ($res) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Create a user from commandline
     * @param array $options
     * @return int $res
     */
    public function useraddAdmin() {

        $values['email'] = common::readSingleline("Enter Email of super user (you will use this as login): ");
        $values['password'] = common::readSingleline("Enter password: ");
        $values['password'] = md5($values['password']);
        $values['username'] = $values['email'];
        $values['verified'] = 1;
        $values['admin'] = 1;
        $values['super'] = 0;

        $values['type'] = 'email';
        $res = $this->useraddInsert($values);
        if ($res) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * function for inserting user
     * @param   array   $values
     * @return  boolean $res
     */
    public function useraddInsert($values) {
        $database = admin::getDbInfo(conf::getMainIni('url'));
        if (!$database) {
            common::echoMessage('No url in config/config.ini');
            return 1;
        }

        $db = new db();
        $res = $db->insert('account', $values);
        return $res;
    }
}
