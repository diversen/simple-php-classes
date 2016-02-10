<?php

namespace diversen\filter;

/**
 * Filter text and transform URLs to HTML links
 * @example
<code>
 use diversen\filters\autolinkext;
 $txt = autolink::filter($txt);
</code>
*/
class autolinkext {

    /**
     * The filter method
     * @param string $text to filter
     * @return string $text
     */
    public static function filter($text){        
       $text = self::autoLink($text);
       return $text;
    }
    
   /**
    * Replace URLs in text with HTML links
    * 
    * @see http://daringfireball.net/2009/11/liberal_regex_for_matching_urls
    * @see http://stackoverflow.com/questions/1925455/how-to-mimic-stackoverflow-auto-link-behavior
    * @param  string $text
    * @return string $string
    */
   public static function autoLink($text)
   {
      $pattern  = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
      $callback = function($matches) { 
          $url       = array_shift($matches);
          $url_parts = parse_url($url);
          $deny = self::getDenyHosts();

          // check for links that we will be transformed from link
          // to inline content, e.g. youtube
          if (isset($url_parts['host'])) {
          
            if (in_array($url_parts['host'], $deny)) {
;                  return $url;
            }
          }
          
          $text = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
          $text = preg_replace("/^www./", "", $text);

          $last = -(strlen(strrchr($text, "/"))) + 1;
          if ($last < 0) {
              $text = substr($text, 0, $last) . "&hellip;";
          }

          return sprintf('<a target="_blank" rel="nofollow" href="%s">%s</a>', $url, $text);
      };

      return preg_replace_callback($pattern, $callback, $text);
    }
    
    /**
     * List of hosts to ignore
     * @return array $ary
     */
    public static function getDenyhosts () {
        return array (
            'www.vimeo.com', 
            'soundcloud.com', 
            'youtu.be', 
            'www.youtu.be', 
            'www.youtube.com', 
            'youtube.com');
    }
}
