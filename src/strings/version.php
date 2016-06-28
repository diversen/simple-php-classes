<?php

namespace diversen\strings;

/**
 * Class that contains a method to get versions from strings
 */
class version {
    
    /**
     * Parses a semantic version string into a array consisting of 
     * `[major, minor, minimal]`
     * @param string $str e.g. 2.4.6
     * @return array $ary array ('major' => 2, 'minor' => 4, 'minimal' => 6)
     */
    public static function getSemanticAry ($str) {
        $ary = explode(".", $str);
        $ret['major'] = $ary[0];
        $ret['minor'] = $ary[1];
        $ret['minimal'] = $ary[2];
        return $ret;
    }
}
