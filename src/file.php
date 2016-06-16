<?php

namespace diversen;
use diversen\file\path;

/**
 * File class for doing common file tasks
 */
class file {

    /**
     * Method for getting a file list of a directory (. and .. will not be
     * collected)
     * @param   string  $dir the path to the directory where we want to create a filelist
     * @param   array   $options if $options['dir_only'] isset then only return directories.
     *                  if $options['search'] isset then only return files containing
     *                  search string. 
     * @return  array   $entries all files as an array
     */
    public static function getFileList($dir, $options = null) {
        
        if (!file_exists($dir)) {
            return false;
        }

        $d = dir($dir);
        $entries = array();
        while (false !== ($entry = $d->read())) {
            if ($entry == '..')
                continue;
            if ($entry == '.')
                continue;
            if (isset($options['dir_only'])) {
                if (is_dir($dir . "/$entry")) {
                    if (isset($options['search'])) {
                        if (strstr($entry, $options['search'])) {
                            $entries[] = $entry;
                        }
                    } else {
                        $entries[] = $entry;
                    }
                }
            } else {
                if (isset($options['search'])) {
                    if (strstr($entry, $options['search'])) {
                        $entries[] = $entry;
                    }
                } else {
                    $entries[] = $entry;
                }
            }
        }
        $d->close();
        return $entries;
    }

    /**
     * Method for getting a file list recursive
     * @param string $start_dir the directory where we start
     * @param string $pattern a given fnmatch() pattern
     * return array $ary an array with the files found. 
     */
    public static function getFileListRecursive($start_dir, $pattern = null) {

        $files = array();
        if (is_dir($start_dir)) {
            $fh = opendir($start_dir);
            while (($file = readdir($fh)) !== false) {
                // skip hidden files and dirs and recursing if necessary
                if (strpos($file, '.') === 0) {
                    continue;
                }

                $filepath = $start_dir . '/' . $file;
                if (is_dir($filepath)) {
                    $files = array_merge($files, self::getFileListRecursive($filepath, $pattern));
                } else {
                    if (isset($pattern)) {
                        if (fnmatch($pattern, $filepath)) {
                            array_push($files, $filepath);
                        }
                    } else {
                        array_push($files, $filepath);
                    }
                }
            }
            closedir($fh);
        } else {
            // false if the function was called with an invalid non-directory argument
            $files = false;
        }

        return $files;
    }

    /**
     * Remove single file or array of files
     * @param string|array $files
     */
    public static function remove($files) {
        if (is_string($files)) {
            unlink($files);
        }
        if (is_array($files)) {
            foreach ($files as $val) {
                $res = unlink($val);
            }
        }
    }

    /**
     * Method for getting extension of a file
     * @param string $filename
     * @return string $extension
     */
    public static function getExtension($filename) {
        return $ext = substr($filename, strrpos($filename, '.') + 1);
    }

    /**
     * Get a filename from a path string
     * @param string $file full path of file
     * @param array $options you can set 'utf8' => true and the filename will be utf8
     * @return string $filename the filename     
     */
    public static function getFilename($file, $options = array()) {
        if (isset($options['utf8'])) {
            $info = path::utf8($file);
        } else {
            $info = pathinfo($file);
        }
        return $info['filename'];
    }

    /**
     * Method for getting mime type of a file
     * @param string $path
     * @return string $mime_type 
     */
    public static function getMime($path) {
        $result = false;
        if (is_file($path) === true) {
            if (function_exists('finfo_open') === true) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if (is_resource($finfo) === true) {
                    $result = finfo_file($finfo, $path);
                }
                finfo_close($finfo);
            } else if (function_exists('mime_content_type') === true) {
                $result = preg_replace('~^(.+);.*$~', '$1', mime_content_type($path));
            } else if (function_exists('exif_imagetype') === true) {
                $result = image_type_to_mime_type(exif_imagetype($path));
            }
        }
        return $result;
    }

    /**
     * Return the prim mime type of a file
     * @param string $file
     * @return string $mime
     */
    public static function getPrimMime($file) {
        $str = self::getMime($file);
        $ary = explode('/', $str);
        return $ary[0];
    }
    
    /**
     * Base path for mkdir
     * @var string $basePath 
     */
    public static $basePath = '/';

    /**
     * Method for creating a directory. It takes into
     * consideration the self::$basePath
     * @param boolean $res
     */
    public static function mkdir($dir, $perms = 0777) {
        $full_path = self::$basePath;
        $dir = $full_path . "$dir";

        if (file_exists($dir)) {
            return false;
        }
        $res = @mkdir($dir, $perms, true);
        return $res;
    }
    
    /**
     * Make a public dir
     * @param string $dir
     */
    public static function mkdirDirect($dir, $perms = 0777) {
        if (file_exists($dir)) {
            return false;
        }
        $res = @mkdir($dir, $perms, true);
        return $res;
    }
    
    /**
     * Get a memory cached file using e.g. APC
     * @param string $file
     * @return string $content content of the file 
     */
    public static function getCachedFile($file) {
        ob_start();
        readfile($file);
        $str = ob_get_contents();
        ob_end_clean();
        return $str;
    }

    /**
     * get dirs in path using DirectoryIterator method
     * @param string $path
     * @param array $options you can set a basename which has to be correct
     *              'basename' => '/path/to/exists'
     * @return array $dirs 
     */
    public static function getDirsGlob($path, $options = array()) {
        if (file_exists($path)) {
            $it = new \DirectoryIterator($path);
        } else {      
            error_log("$path does not exists in " . __FILE__ . ": " . __LINE__);
            return array();
        }
        
        $dirs = array ();
        foreach ($it as $file) {
            if ($file->isDir() && !$file->isDot()) {
                $dirs[] = $it->getPathname();
            }
        }
        if (isset($options['basename'])) {
            foreach ($dirs as $key => $dir) {
                $dirs[$key] = basename($dir);
            }
        }

        return $dirs;
    }

    /**
     * Remove a directory recursively
     * @param string $dir
     */
    public static function rrmdir($dir) {
        $fp = opendir($dir);
        if ($fp) {
            while ($f = readdir($fp)) {
                $file = $dir . "/" . $f;
                if ($f == "." || $f == "..") {
                    continue;
                } else if (is_dir($file) && !is_link($file)) {
                    file::rrmdir($file);
                } else {
                    unlink($file);
                }
            }
            closedir($fp);
            rmdir($dir);
        }
    }

    /**
     * Scans a directory recursively and returns files
     * @param string $dir
     * @return array $ary
     */
    public static function scandirRecursive($dir) {
        $files = scandir($dir);
        static $ary = array();
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $file = $dir . '/' . $file;
            if (is_dir($file)) {
                self::scandirRecursive($file);
            } else {
                $ary[] = $file;
            }
        }
        return $ary;
    }
}
