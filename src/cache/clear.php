<?php

namespace diversen\cache;

use diversen\db\q;
use diversen\file;
use diversen\conf;

/**
 * Class contains a simple class for clearing caches 
 * To be used with framework 
 * @package main
 */
class clear {

    /**
     * Clears database system_cache table
     * @return boolean $res true on success else false  
     */
    public static function db () {
        $res = q::delete('system_cache')->filter('1 =', 1)->exec();
        return $res;
    }

    public static function assets ($options = null) {

        $path = conf::pathBase() . "/htdocs/files/default/cached_assets";
        if (file_exists($path)) {
            file::rrmdir($path);
        }
        return 1;
    }

    public static function all ($options = null) {
        self::assets();
        self::db();
        return 1;
    }
}