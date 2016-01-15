<?php

namespace diversen\ary;

/**
 * Class contains a couple of array helpers
 * @package main
 */
class helpers {
    /**
     * Prepares an array for db post where we specify keys to return from global
     * *$_POST* 
     * @param array $keys keys to use from POST request
     * @param boolean $null_values set a null value if key is not set in POST 
     *                values. If false, then the values will not exist. 
     * @return array $ary array with array we will use 
     */
    public static function preparePOST ($keys, $null_values = true) {
        $ary = array ();
        foreach ($keys as $val) {
            if (isset($_POST[$val])) {
                $ary[$val] = $_POST[$val];
            } else {
                if ($null_values) {
                    $ary[$val] = NULL;
                }
            }
        }
        return $ary;
    }
    
    /**
     * Prepares an array for db using GET where we specify keys to return from 
     * global *$_GET*
     * @param array $keys keys to use from GET request
     * @param boolean $null_values set a null value if key is not set in POST 
     *                values. If false, then the values will not exist. 
     * @return array $ary array with array we will use 
     */
    public static function prepareGET ($keys, $null_values = true) {
        $ary = array ();
        foreach ($keys as $val) {
            if (isset($_GET[$val])) {
                $ary[$val] = $_GET[$val];
            } else {
                if ($null_values) {
                    $ary[$val] = NULL;
                }
            }
        }
        return $ary;
    }
    
    /** 
     * Search an array recursively for a needle
     * 
     * @see http://php.net/manual/en/function.array-search.php
     * @param type $needle
     * @param type $haystack
     * @return boolean
     */
    public static function searchRecursive($needle, $haystack) {
        foreach ($haystack as $key => $value) {
            $current_key = $key;
            if ($needle === $value OR ( is_array($value) && self::searchRecursive($needle, $value) !== false)) {
                return $current_key;
            }
        }
        return false;
    }
}
