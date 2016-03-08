<?php

namespace diversen\db;

use diversen\conf;
use diversen\db\connect;
use Exception;
use R;

include_once "vendor/diversen/redbean-composer/rb.php";


/**
 * RB contains some helpers methods for RB. 
 * Methods for connecting, converting array to beans
 * 
 * In order to use this class you need to do: *composer require diversen/redbean-composer*
 * @see http://www.redbeanphp.com/index.php
 * @example: 
<code>
use diversen\rb;
rb::connect();
$bean = rb::getBean('test');
$bean = rb::arrayToBean($bean, $_POST);
r::store($bean);
</code>
 */
class rb {
        
    /**
     * Create a db connection with params found in *config.ini*
     * @return void
     */
    public static function connect () {
        static $connected = null;
        
        if (!$connected){           
            
            $url = conf::getMainIni('url');
            $username = conf::getMainIni('username');
            $password = conf::getMainIni('password');
            
            R::setup($url, $username,$password); 
            $freeze = conf::getMainIni('rb_freeze');
            if ($freeze == 1) {
                R::freeze(true);
            }
            $connected = true;
        } 
    }
    
    /**
     * Connect to existing database handle with RedBeans
     */
    public static function connectExisting () {
        static $connected = null;
        if (!$connected) {
            R::setup(connect::$dbh);
            $connected = true;
        }
        $freeze = conf::getMainIni('rb_freeze');
        if ($freeze == 1) {
            R::freeze(true);
        }
    }
    
    /**
     * Method for transforming an array into a bean
     * @param object $bean
     * @param array $ary
     * @param boolean $skip_null if true we skip values that is not set (e.g. null)
     *                           if false we don't skip null - but add them to bean
     * @return object $bean 
     */
    public static function arrayToBean ($bean, $ary, $skip_null = true) {
        foreach ($ary as $key => $val) {
            if (!isset($val) && $skip_null)  { 
                continue;
            }
            $bean->{$key} = trim($val);
        }
        return $bean;
    }
    
    /**
     * Update a bean found by *table* and *id* with *values* 
     * @param string $table
     * @param int $id
     * @param array $values
     * @return int $res
     */
    public static function updateBean ($table, $id, $values) {
        $bean = self::getBean($table, 'id', $id);
        foreach($values as $key => $value) {
            $bean->{$key} = $value;
        }
        return R::store($bean);
    }
    /**
     * Method for getting a bean. It searches for an existing bean based 
     * on a *field* and a *value* 
     * If not found it create a new bean
     * @param string $table
     * @param string $field
     * @param mixed $search
     * @return object $bean 
     */
    public static function getBean ($table, $field = null, $search = null) {
        if (isset($field) && isset($search)) {
            $needle = R::findOne($table," 1 AND $field  = ?", array( $search ));
        } else {
            $needle = null;
        }
        
        if (empty($needle)) {
            $bean = R::dispense( $table );
        } else {
            $bean = R::load($table, $needle->id);
        }
        return $bean;
    }

    /**
     * Shorthand method that will delete a collection of beans with *commit* and *rollback*  transactions
     * @param object $beans
     */
    public static function deleteBeans ($beans) {
        R::begin();
        try{
            R::trashAll($beans);   
            R::commit();
        } catch(Exception $e) {
            R::rollback();
        }
    }

    /**
     * commit a bean with transactions
     * @param object $bean
     * @return $res false or last insert id 
     */
    public static function commitBean ($bean) {
        R::begin();
        try{
            $res = R::store($bean);
            R::commit();
        } catch(Exception $e) {
            R::rollback();
            $res = false;
        }
        return $res;
    }
}
