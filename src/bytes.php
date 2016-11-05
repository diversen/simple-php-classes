<?php

namespace diversen;

/**
 * Class that transforms bytes to human readables. 
 * And transform human readales to bytes 
 */

class bytes {
    
    /**
     * Return bytes from Greek e.g. 2M or 100K
     * Used mainly to get values from php.ini file as this
     * uses the G, M, K modifieres
     * @param string $val
     * @return int $val bytes
     */
    public static function bytesFromGreekSingle ($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
    
   /**
    * Found on:
    * 
    * http://codeaid.net/php/convert-size-in-bytes-to-a-human-readable-format-%28php%29
    * 
    * Convert bytes to human readable format, e.g. bytes to 10GB
    *
    * @param int $bytes Size in bytes to convert
    * @param int $precision 
    * @return string $bytes as string
    */
    public static function bytesToGreek($bytes, $precision = 2){	
	$kilobyte = 1024;
	$megabyte = $kilobyte * 1024;
	$gigabyte = $megabyte * 1024;
	$terabyte = $gigabyte * 1024;
	
	if (($bytes >= 0) && ($bytes < $kilobyte)) {
		return $bytes . ' B';

	} elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
		return round($bytes / $kilobyte, $precision) . ' KB';

	} elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
		return round($bytes / $megabyte, $precision) . ' MB';

	} elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
		return round($bytes / $gigabyte, $precision) . ' GB';

	} elseif ($bytes >= $terabyte) {
		return round($bytes / $terabyte, $precision) . ' TB';
	} else {
		return $bytes . ' B';
	}
    }
    
    /**
     * Transforms greek (e.g.102MB or 20GB) to bytes
     * Found on: http://stackoverflow.com/questions/11807115/php-convert-kb-mb-gb-tb-etc-to-bytes
     * @param string $from
     * @return int $bytes
     */
    public static function greekToBytes($from) {

        $number = substr($from, 0, -2);
        switch (strtoupper(substr($from, -2))) {
            case "KB":
                return $number * 1024;
            case "MB":
                return $number * pow(1024, 2);
            case "GB":
                return $number * pow(1024, 3);
            case "TB":
                return $number * pow(1024, 4);
            case "PB":
                return $number * pow(1024, 5);
            default:
                return $from;
        }
    }
    
    
    /**
     * return max size for file upload in bytes
     * @return int $bytes
     */
    public static function getNativeMaxUpload () {
        $upload_max_filesize = self::bytesFromGreekSingle(ini_get('upload_max_filesize'));
        $post_max_size = self::bytesFromGreekSingle(ini_get('post_max_size'));
        if ($upload_max_filesize >= $post_max_size) {
            return $post_max_size;
        } else {
            return $upload_max_filesize;
        }
    }
}
