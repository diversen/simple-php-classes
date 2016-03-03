<?php

namespace diversen\file;

/**
 * package contains file class for doing common file tasks associated 
 * with strings. 
 */

class string {
    
    /**
     * Return the number of lines in a file
     * @param string $file
     * @return int $num
     */
    public static function getNumLines ($file) {
        $linecount = 0;
        $handle = fopen($file, "r");
        while(!feof($handle)){
            $line = fgets($handle);
            $linecount++;
        }
        
        fclose($handle);
        return $linecount;
    }
    
    /**
     * Removes an exact line from a file and saves it
     * @param string $file
     * @param string $str
     * @return boolean $res true on success and false on failure.
     */
    public static function rmLine ($file, $str) {
        $handle = fopen($file, "r");
        $final = '';
        while(!feof($handle)){
            $line = fgets($handle);
            if (strstr($line, $str)) { 
                continue;
            }
            $final.= $line;
        }
        fclose($handle);
        return file_put_contents($file, $final, LOCK_EX);
    }
    
    /**
     * Get a single line from a file
     * @param string $file
     * @return string|false $line the line or false
     */
    public static function getLine($file) {
        $handle = @\fopen($file, "r");
        if ($handle) {
            $line = \fgets($handle);
            @\fclose($handle);
            return $line;
        } 
        return false;
    }
    
    /**
     * From: http://stackoverflow.com/questions/11068203/php-retrieving-lines-from-the-end-of-a-large-text-file
     * Returns the tail of a file as array of lines
     * @param string $filename
     * @param int $num_lines
     * @return array $lines an array of lines from the file
     */
    public static function getTail($filename, $num_lines = 10) {
        $file = fopen($filename, "r");
        fseek($file, -1, SEEK_END);

        for ($line = 0, $lines = array(); $line < $num_lines && false !== ($char = fgetc($file));) {
            if ($char === "\n") {
                if (isset($lines[$line])) {
                    $lines[$line][] = $char;
                    $lines[$line] = implode('', array_reverse($lines[$line]));
                    $line++;
                }
            } else {
                $lines[$line][] = $char;
            }
            fseek($file, -2, SEEK_CUR);
        }

        if ($line < $num_lines){
            $lines[$line] = implode('', array_reverse($lines[$line]));
        }

        return array_reverse($lines);
    }
}
