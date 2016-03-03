<?php

namespace diversen\cache;

use diversen\db\q;
use diversen\file;
use diversen\conf;

/**
 * Class clears DB cache and assets.
 * NOT To be used outside framework
 */
class clear {

    /**
     * Clears DB *system_cache* table
     * @return boolean $res true on success else false  
     */
    public static function db () {
        $res = q::delete('system_cache')->filter('1 =', 1)->exec();
        return $res;
    }

    /**
     * Clears *conf::pathBase() . "/htdocs/files/default/cached_assets*
     * @param array $options
     * @return int $res '1'
     */
    public static function assets ($options = null) {

        $path = conf::pathBase() . "/htdocs/files/default/cached_assets";
        if (file_exists($path)) {
            file::rrmdir($path);
        }
        return 1;
    }

    /**
     * Clear both DB and files cache
     * @param array $options
     * @return int $res always '1'
     */
    public static function all ($options = null) {
        self::assets();
        self::db();
        return 1;
    }
}
