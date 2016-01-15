<?php

namespace diversen\filter;

/**
 * Filter a text and transform URLs to HTML links
 * @package main
 * @example
~~~
 use diversen\filters\autolink;
 $txt = autolink::filter($txt);
~~~
*/
class autolink {

    /**
     * Filters a text and transforms URLS to HTML links
     * @param string $text to filter
     * @return string $text filtered text
     */
    public static function filter($text) {
        $text = self::setLinks($text);
        return $text;
    }

    /**
     * Do the replacement
     * @param string $text
     * @return string $text
     */
    protected static function setLinks($text) {

        $text = " " . $text;
        $text = preg_replace("#([\n ])([a-z]+?)://([a-z0-9\-\.,\?!%\*_\#:;~\\&$@\/=\+]+)#i", "\\1<a href=\"\\2://\\3\" target=\"_blank\">\\2://\\3</a>", $text);
        $text = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[a-z0-9\-\.,\?!%\*_\#:;~\\&$@\/=\+]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\" target=\"_blank\">www.\\2.\\3\\4</a>", $text);
        $text = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)?[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $text);
        $text = substr($text, 1);
        return($text);
    }
}
