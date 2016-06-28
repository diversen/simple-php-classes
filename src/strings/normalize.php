<?php

namespace diversen\strings;

/**
 * Normalize newlines
 */
class normalize {
    /**
     * Method that normalize newlines across platforms
     * Found on: http://darklaunch.com/2009/05/06/php-normalize-newlines-line-endings-crlf-cr-lf-unix-windows-mac
     * @param string $str
     * @return string $str
     */
    public static function newlinesToUnix($s) {
        $s = str_replace("\r\n", "\n", $s);
        $s = str_replace("\r", "\n", $s);
        return $s;
    }
}
