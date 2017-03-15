<?php

namespace diversen;

class git {
    
    /**
     * Get a module name from a git repo url
     * Works with e.g. 
     * git@github.com:diversen/debug.git
     * git@github.com:diversen/debug
     * https://github.com/diversen/debug.git
     * https://github.com/diversen/debug
     * git://github.com/diversen/debug.git
     * git://github.com/diversen/debug
     * @param string $repo url
     * @return string $module_name
     */
    public static function getRepoNameFromRepoUrl($repo) {
        $url = parse_url($repo);
        
        $url['path'] = rtrim($url['path'], '/');
        $parts = explode('/', $url['path']);
        
        $name = array_pop($parts);
        
        if (strstr($name, '.git')) {
            $name = substr($name, 0, -4);
        }
        return $name;
    }
    
    
    /**
     * Get a repo name from repo url
     * e.g. https://github.com/diversen/php-git-to-book.git should
     * return php-git-to-book
     * @param string $url
     * @return string $name
     */
    /*
    public static function getRepoName($url) {
        return self::getRepoNameFromRepoUrl($url);
        $ary = parse_url($url);
        $parts = explode('/', $ary['path']);
        $last = array_pop($parts);
        return str_replace('.git', '', $last);
    }*/

    /**
     * Get all tags as a string from currenct path
     * @return string $tags tags as a string
     */
    public static function getTagsAsString() {
        $command = "git tag -l";
        $ret = exec($command, $output);
        return implode(PHP_EOL, $output);
    }
    
    /**
     * Get all tags for current path as an array
     * @return array $tags
     */
    public static function getTagAsArray () {
        $tags = self::getTagsAsString();
        $ary = explode("\n", $tags);
        return array_filter($ary);
    }
    
    /**
     * Get latest tag from current path 
     * @return string $tag
     */
    public static function getTagLatest () {
        $ary = self::getTagAsArray();
        return array_pop($ary);
    }



    /**
     * Get remote tags
     * See: https://github.com/troelskn/pearhub
     *
     * @param   string  $url a git url
     * @param   mixed   $clear set this and tags will not be cached in static var
     * @return  array   $ary array of remote tags
     */
    public static function getTagsRemote($url = null) {

        $tags = array();
        $output = array();
        $ret = 0;

        $command = "git ls-remote --tags $url";
        exec($command . ' 2>&1', $output, $ret);

        foreach ($output as $line) {
            trim($line);
            if (preg_match('~^[0-9a-f]{40}\s+refs/tags/(([a-zA-Z_-]+)?([0-9]+)(\.([0-9]+))?(\.([0-9]+))?([A-Za-z]+[0-9A-Za-z-]*)?)$~', $line, $reg)) {
                $tags[] = $reg[1];
            }
        }

        return $tags;
    }

    /**
     * Get latest remote tag
     * 
     * See: https://github.com/troelskn/pearhub
     * 
     * @param   string  $repo a git url url
     * @param   boolean $clear and tags will not be cached in static var
     * @return  array   $tags array of remote tags
     */
    public static function getTagsRemoteLatest($repo) {
        $tags = self::getTagsRemote($repo);
        if (count($tags) > 0) {
            sort($tags);
            return $tags[count($tags) - 1];
        }
        return null;
    }
    
    /**
     * Check if main repo is master
     * @return boolean true if master else false
     */
    public static function isMaster () {
        $branch = shell_exec('git branch');
        if ('* master' == trim($branch)){
            return true;
        }
        return false;
    }
    
    /**
     * get a SSH clone URL from a HTTPS clone URL
     * @param string $url
     * @return string $url
     */
    public static function getSshFromHttps($url) {
        
        $ary = parse_url(trim($url));
        $num = count($ary);
        if ($num == 1) {
            return $ary['path'];
        }
        
        // E.g. git://github.com/diversen/vote.git
        if (isset($ary['scheme']) && $ary['scheme'] == 'git') {
            return "git@$ary[host]:" . ltrim($ary['path'], '/');
        }
        
        return "git@$ary[host]:" . ltrim($ary['path'], '/');
    }

    
       /**
     * get a SSH clone URL from a HTTPS clone URL
     * @param string $url
     * @return string $url
     */
    public static function getHttpsFromSsh($url) {

        $ary = parse_url(trim($url));
        
        // Is it already https
        if (isset($ary['scheme']) && $ary['scheme'] == 'https') {
                        
            return $url;
        }
        
        // E.g. git://github.com/diversen/vote.git
        if (isset($ary['scheme']) && $ary['scheme'] == 'git') {

            return 'https://' . $ary['host'] . $ary['path'];
        }
        

        $num = count($ary);
        if ($num == 1) {    

            return self::parsePrivateUrl($url);
        }
        return "$ary[scheme]@$ary[host]:$ary[path]";
    }
    
    /**
     * return a SSH path from a HTTPS URL
     * @param string $url
     * @return string $path
     */
    public static function parsePrivateUrl ($url) {
        $ary = explode('@', $url);
        $ary = explode(':', $ary[1]);
        $url = 'https://' . $ary[0] . "/$ary[1]";
        return $url;
    }
}
