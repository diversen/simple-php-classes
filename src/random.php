<?php

namespace diversen;

/**
 *class random contains methods for getting random strings
 * @package random 
 */

class random {
    
    /**
     * gets a random string from length [a-zA-Z0-9]
     * @param int $length
     * @return string $random
     */
    public static function string( $length ) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";	
        $str = '';
	$size = strlen( $chars );
	for( $i = 0; $i < $length; $i++ ) {
		$str .= $chars[ rand( 0, $size - 1 ) ];
	}

	return $str;
    }
    
    /**
     * Get a random number from specified length [0-9]
     * @param int $length
     * @return string $random
     */
    public static function number( $length ) {
	$chars = "0123456789";	
        $str = '';
	$size = strlen( $chars );
	for( $i = 0; $i < $length; $i++ ) {
		$str .= $chars[ rand( 0, $size - 1 ) ];
	}

	return $str;
    }
    
    /**
     * Get a random md5
     * @return string $md5 random md5
     */
    public static function md5() {
        return md5(uniqid(mt_rand(), true));
    }
    
    /**
     * Get random SHA1
     * @return string $sha1
     */
    public static function sha1 () {
        return sha1(microtime(true).mt_rand(10000,90000));
    }
}
