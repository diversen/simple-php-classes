<?php

use diversen\db;
use diversen\db\admin;
use diversen\cli\common;
use diversen\conf;


/**
 * @package shell
 *
 */

/**
 * Create a super user from commandline
 * @param array $options
 * @return int $res
 */
function useradd_super ($options = null){

    $values['email'] = common::readSingleline("Enter Email of super user (you will use this as login): ");
    $values['password'] = common::readSingleline ("Enter password: ");
    $values['password'] = md5($values['password']);
    $values['username'] = $values['email'];
    $values['verified'] = 1;
    $values['admin'] = 1;
    $values['super'] = 1;
    
    $values['type'] = 'email';
    $res = useraddInsert($values);
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
function useradd_user ($options = null){

    $values['email'] = common::readSingleline("Enter Email of super user (you will use this as login): ");
    $values['password'] = common::readSingleline ("Enter password: ");
    $values['password'] = md5($values['password']);
    $values['username'] = $values['email'];
    $values['verified'] = 1;
    $values['admin'] = 0;
    $values['super'] = 0;
    
    $values['type'] = 'email';
    $res = useraddInsert($values);
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
function useradd_admin ($options = null){

    $values['email'] = common::readSingleline("Enter Email of super user (you will use this as login): ");
    $values['password'] = common::readSingleline ("Enter password: ");
    $values['password'] = md5($values['password']);
    $values['username'] = $values['email'];
    $values['verified'] = 1;
    $values['admin'] = 1;
    $values['super'] = 0;
    
    $values['type'] = 'email';
    $res = useraddInsert($values);
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
function useraddInsert ($values){
    $database = admin::getDbInfo(conf::getMainIni('url'));
    if (!$database) {
        return db_no_url();
    }

    $db = new db();
    $res = $db->insert('account', $values);
    return $res;
}

self::setCommand('useradd', array(
    'description' => 'Create users',
));

self::setOption('useradd_super', array(
    'long_name'   => '--super',
    'description' => 'Add a super user from prompt.',
    'action'      => 'StoreTrue'
));

self::setOption('useradd_admin', array(
    'long_name'   => '--admin',
    'description' => 'Add an admin user from prompt.',
    'action'      => 'StoreTrue'
));

self::setOption('useradd_user', array(
    'long_name'   => '--user',
    'description' => 'Add a user from prompt.',
    'action'      => 'StoreTrue'
));
