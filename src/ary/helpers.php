<?php

namespace diversen\ary;

/**
 * Class contains a few array helpers
 * @package main
 */
class helpers {
    
    /**
     * Prepares an array for e.g. DB insert, where we specify keys to return from global
     * *$_POST*. If $null_values is true, then the array will be returned with 
     * *'key' => null* if the key is not in *$_POST* array
     * @param array $keys keys to use from $_POST request
     * @param boolean $null_values use values not set in $_POST
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
     * Prepares an array for e.g. DB insert, where we specify keys to return from global
     * $_GET. If $null_values is true, then the array will be returned with 
     * *'key' => null* if the key is not in $_GET array
     * @param array $keys keys to use from $_GET request
     * @param boolean $null_values use values not set in $_GET
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
     * @see http://php.net/manual/en/function.array-search.php
     * @param type $needle the needle to find
     * @param type $haystack the array to search in
     * @return boolean $res true if found else false
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
