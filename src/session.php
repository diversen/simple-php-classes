<?php

namespace diversen;

use diversen\random;
use diversen\conf;
use diversen\db;
use diversen\db\q;
use diversen\date;
use diversen\moduleloader;


/**
 * File contains contains class for doing checks on seesions
 * @package    session
 */

/**
 * Class contains contains methods for setting sessions
 * @package    session
 */
class session {
    
    /**
     * method for initing a session
     * checks if we use memcached which is a good idea
     */
    public static function initSession(){
        
        self::setSessionIni(); 
        self::setSessionHandler();
        session_start();
    }
    
    /**
     * sets ini for 
     * session.cooke_lifetime, session.cookie_path,session.cookie_domain
     */
    public static function setSessionIni () {
        
        // session host. Use .example.com if you want to allow session
        // accross sub domains. Interesting: You can not use testserver
        // server without country part)
        
        $session_host = conf::getMainIni('session_host');
        if ($session_host){
            ini_set("session.cookie_domain", $session_host);
        }
        
        $session_save_path = conf::getMainIni('session_save_path');
        if ($session_save_path) { 
            ini_set("session.save_path", $session_save_path);
        }
        
        // session time
        $session_time = conf::getMainIni('session_time');
        if (!$session_time) { 
            $session_time = '0';
        }
        ini_set("session.cookie_lifetime", $session_time);

        // session path
        $session_path = conf::getMainIni('session_path');
        if ($session_path) {
            ini_set("session.cookie_path", $session_path);
        }

        // secure session
        $session_secure = conf::getMainIni('session_secure');
        if ($session_secure) { 
            ini_set("session.cookie_secure", true);
        } else {
            ini_set("session.cookie_secure", false);
        }

        // set a session name. You need this if the session 
        // should cross sub domains
        $session_name = conf::getMainIni('session_name');
        if ($session_name) { 
            session_name($session_name);
        }
    }
    
    /**
     * sets session handler. 
     * only memcahce if supported
     */
    public static function setSessionHandler () {
        
        // use memcache if available
        $handler = conf::getMainIni('session_handler');
        if ($handler == 'memcache'){
            $host = conf::getMainIni('memcache_host');
            if (!$host) {
                $host = 'localhost';
            }
            $port = conf::getMainIni('memcache_port');
            if (!$port) {
                $port = '11211';
            }
            $query = conf::getMainIni('memcache_query');
            if (!$query) {
                $query = 'persistent=0&weight=2&timeout=2&retry_interval=10';
            }
            $session_save_path = "tcp://$host:$port?$query,  ,tcp://$host:$port  ";
            ini_set('session.save_handler', 'memcache');
            ini_set('session.save_path', $session_save_path);
        }
    }

    /**
     * checks if there is a cookie we can use for log in. If cookie exists 
     * we will log in the user
     * 
     * You can run trigger events which needs to be set in session_events
     * in config/config.ini 
     * 
     * @return void
     */
    public static function checkSystemCookie(){
        
        if (isset($_COOKIE['system_cookie'])){
            
            // Check against cookie from DB
            // User may have logged out of all devices
            $row = self::getSystemCookieDb();
            
            if (empty($row)) {
                return;
            }

            
            // we got a cookie that equals one found in database
            $days = self::getCookiePersistentDays();

            // delete system_cookies that are out of date. 
            $now = date::getDateNow();
            $last = date::substractDaysFromTimestamp($now, $days);
            q::delete('system_cookie')->
                    filter('account_id =', $row['account_id'])->condition('AND')->
                    filter('last_login <', $last)->
                    exec();

            // on every cookie login we update the cookie id              
            $last_login = date::getDateNow(array('hms' => true));
            $new_cookie_id = random::md5();
            $values = array(
                'account_id' => $row['account_id'],
                'cookie_id' => $new_cookie_id,
                'last_login' => $last_login);

            q::delete('system_cookie')->
                    filter('cookie_id=', $_COOKIE['system_cookie'])->
                    exec();

            q::insert('system_cookie')->
                    values($values)->
                    exec();

            // set the new cookie
            self::setCookie('system_cookie', $new_cookie_id);

            // get account which is connected to account id
            $account = self::getAccount($row['account_id']);

            // user with account
            if (!empty($account)) {
                 
                $_SESSION['id'] = $account['id'];
                $_SESSION['admin'] = $account['admin'];
                $_SESSION['super'] = $account['super'];
                $_SESSION['type'] = $account['type'];

            } 
        }
    }
    
    /**
     * get account from id
     * @param int $id
     * @return array $row
     */
    public static function getAccount ($id) {
        $db = new db();
        $row = $db->selectOne('account', 'id', $id);
        return $row;
    }
    
    /**
     * sets a cookie based on main configuration
     * @param string $name
     * @param string $value
     * @param string $path
     */
    public static function setCookie ($name, $value, $path = '/') {

        $cookie_time = self::getCookiePersistentSecs();              
        $timestamp = time() + $cookie_time;        
        $session_host = conf::getMainIni('session_host');
        
        // secure session
        $session_secure = conf::getMainIni('session_secure');
        if ($session_secure) { 
            $secure = true;
        } else {
            $secure = false;
        }        
        $res = setcookie($name, $value, $timestamp, $path, $session_host, $secure);

        // Make $_COOKIE available in this request
        // Why is this not the default behavior as in SESSION
        // XXX
        $_COOKIE[$name] = $value;        
        return $res;
    }

    /**
     * sets a system cookie. 
     * @param int $user_id
     * @return boolean $res true on success and false on failure. 
     */
    public static function setSystemCookie($user_id){

        $uniqid = random::md5();
        self::setCookie('system_cookie', $uniqid);
        
        $db = new db();

        // place cookie in system cookie table
        // last login is auto updated
        $values = array (
            'account_id' => $user_id, 
            'cookie_id' => $uniqid,
            'last_login' => date::getDateNow(array ('hms' => true))
                );
        
        return $db->insert('system_cookie', $values);
    }
    
    /**
     * return persistent cookie time in secs
     * @return int $time in secs
     */
    public static function getCookiePersistentSecs () {
        
        $days = conf::getMainIni('cookie_time');        
        if ($days == -1) {
            // ten years
            $cookie_time = 3600 * 24 * 365 * 10;
        }
        
        else if ($days >= 1) {
            $cookie_time = 3600 * 24 * $days;
        }
        
        else {
            // Session Cookie
            $cookie_time = 0;
        }
        
        return $cookie_time;
    }
    
    /**
     * return persistent cookie time in secs
     * @return int $time in secs
     */
    public static function getCookiePersistentDays () {
        
        $days = conf::getMainIni('cookie_time');        
        if ($days == -1) {
            $cookie_time = 365 * 10;
        }
        
        else if ($days >= 1) {
            $cookie_time = $days;
        }
        
        else {
            $cookie_time = 0;
        }
        
        return $cookie_time;
    }
    
    /**
     * Try to get system cookie
     * @return false|string $_COOKIE return system_cookie md5 or false    
     */
    public static function getSystemCookie (){
        if (isset($_COOKIE['system_cookie'])){
            return $_COOKIE['system_cookie'];
        } else {
            return false;
        }
    }
    
    /**
     * Fetch system cookie row from db
     * 
     * @return array $row array empty if no match between system_cookie and $_COOKIE['systen_cookie']    
     */
    public static function getSystemCookieDb (){
        $cookie = self::getSystemCookie();
        if (!$cookie) {
            return [];
        }
        
        return q::select('system_cookie')->
                filter('cookie_id =', $cookie)->
                fetchSingle(); 
    }

    /**
     * method for killing a session
     * unsets the system cookie and unsets session credentials
     */
    public static function killSession (){

        $db = new db();
        $db->delete('system_cookie', 'cookie_id', @$_COOKIE['system_cookie']);
        
        setcookie ("system_cookie", "", time() - 3600, "/");
        unset($_SESSION['id'], $_SESSION['admin'], $_SESSION['super'], $_SESSION['account_type']);
        session_destroy();
    }
    
    /**
     * method for killing all sessions based on user_id
     * deletes all system cookies and unsets session credentials
     * @param int $account_id
     */
    public static function killSessionAll ($account_id){
        // only keep one system cookie (e.g. if user clears his cookies)
        $db = new db();
        $db->delete('system_cookie', 'account_id', $account_id);
        
        setcookie ("system_cookie", "", time() - 3600, "/");
        unset($_SESSION['id'], $_SESSION['admin'], $_SESSION['super'], $_SESSION['account_type']);
        session_destroy();
    }
    
    /**
     * method for killing all cookie sessions
     * unsets the system cookie and unsets session credentials
     * @param int $user_id
     */
    public static function killAllSessions ($user_id){
        // only keep one system cookie (e.g. if user clears his cookies)
        $db = new db();
        $db->delete('system_cookie', 'account_id', $user_id);
        
        setcookie ("system_cookie", "", time() - 3600, "/");
        unset($_SESSION['id'], $_SESSION['admin'], $_SESSION['super'], $_SESSION['account_type']);
        session_destroy();
    }
    
    /**
     * you can specify one event in your main ini (config/config.ini) file.
     * session_events:  
     * 
     * e.g. $args = array (
     *                  'action' => 'account_login',
     *                  'user_id' => $account['id']
     *              );
     * 
     * This is called on a login  
     */
    public static function __events () {
        
    }


    
    /**
     * sets a persistent session var
     * @param string $name index of var
     * @param mixed $var array or object or string or int
     */
    public static function setPersistentVar ($name, $var) {
        if (!isset($_SESSION['system_persistent_var'])) $_SESSION['system_persistent_var'] = array ();
        $_SESSION['system_persistent_var'][$name] = serialize($var);
        
    }
    
    /**
     * returns a persistent var from index name
     * @param string $name index of var
     * @param boolean $clean true will clean var from session, false will not 
     * @return mixed $ret array or object or string or int
     */
    public static function getPersistentVar($name, $clean = true) {
        if (!isset($_SESSION['system_persistent_var'][$name])) {
            return null;
        }
        
        $ret = unserialize($_SESSION['system_persistent_var'][$name]);
        if ($clean) { 
            unset($_SESSION['system_persistent_var'][$name]);
        }
        return $ret;
    }

    /**
     * Method for setting an action message. Used when we want to tell a
     * user what happened if he is e.g. redirected. You can force to
     * close the session, which means you can write to screen after you
     * session vars has been set. This should be avoided.  
     *
     * @param string $message the action message.
     * @param string $type type of message. Default is 'system_message' and 
     *                     another type is 'system_error'. You can also just create
     *                     your own types
     */
    public static function setActionMessage($message, $type = 'system_message'){
        if (!isset($_SESSION[$type])) {
            $_SESSION[$type] = array ();
        } 
            
        $_SESSION[$type][] = $message;
    }

    /**
     * method for reading an action message
     * You can template this message by adding a template_get_action_message
     * in your template. 
     * @return string $str actionMessage
     */
    public static function getActionMessage($type = 'system_message', $empty = true){
        if (isset($_SESSION[$type])){

            $messages = $_SESSION[$type];
            $ret = '';
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $ret.= $message;
                }
            }
            if ($empty) {
                unset($_SESSION[$type]);
            }
            return $ret;
        }
        return null;
    }

    /**
     * method for testing if user is in super or not
     * @return  boolean $res true or false
     */
    public static function isSuper(){
        if ( isset($_SESSION['super']) && ($_SESSION['super'] == 1)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * method for testing if user is admin or not
     * @return  boolean $res true or false
     */
    static public function isAdmin(){
        if ( isset($_SESSION['admin']) && ($_SESSION['admin'] == 1)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * method for testing if user is loged in or not
     *
     * @return  boolean true or false
     */
    static public function isUser(){
        if ( isset($_SESSION['id']) && $_SESSION['id'] != 0 ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * checks $_SESSION['id'] and if set it will return 
     * method for getting a users id - remember that 0 is anon user.
     *
     * @return  mixed $res false if no user id or the users id. 
     */
    static public function getUserId(){
        
        if (!isset($_SESSION['id'])) {
            return false;
        }
        
        if ($_SESSION['id'] == 0 ){
            return false;
        } 
        
        return $_SESSION['id'];
        
    }

    /**
     * Checks access control against a module ini setting 
     * e.g. in blog.ini default is: blog_allow = 'admin'
     * then you should call checkAccessControl('blog_allow') in order to prevent
     * others than 'admin' in using the page
     * 
     * If a user does not have perms then the default 403 page will be set, 
     * and a 403 header will be sent. 
     * 
     * @param   string  $allow user or admin or super
     * @param   boolean $setErrorModule set error module or not
     * @return  boolean true if user has required accessLevel.
     *                  false if not. 
     * 
     */
    public static function checkAccessControl($allow, $setErrorModule = true){
        
        // we check to see if we have a ini setting for 
        // the type to be allowed to an action
        // allow_edit_article = super
        $allow = conf::getModuleIni($allow);

        // is allow is empty means the access control
        // is not set and we grant access
        if (empty($allow)) {
            return true;
        }
        
        // anon is anonymous user. Anyone if allowed
        if ($allow == 'anon') {
            return true;
        }

        // check if we have a user
        if ($allow == 'user'){
            if(self::isUser()){
                return true;
            } else {
                if ($setErrorModule){
                    moduleloader::$status[403] = 1;
                }
                return false;
            }
        }


        // check other than users. 'admin' and 'super' is set
        // in special session vars when logging in. User is
        // someone who just have a valid $_SESSION['id'] set
        if (!isset($_SESSION[$allow]) || $_SESSION[$allow] != 1){
            if ($setErrorModule){
                moduleloader::$status[403] = 1;
            }
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * better name for checkAccessControl
     * @param string $allow the module ini settings we read from e.g. blog_allow
     * @param boolean $setErrorModule notify moduleloader
     * @return boolean $res true if access allowed else false
     */
    public static function checkAccessFromModuleIni ($allow, $setErrorModule = true){
        return self::checkAccessControl($allow, $setErrorModule); 
    }
    
    /**
     * 
     * @param string $allow, a module ini setting which yield a access level. 
     *               e.g. blog_allow = 'user'
     * @param boolean $setErrorModule if auth fails should we display error 403. Access denied.
     *               defaults to 'true'
     * @return boolean $res 
     */
    public static function authIni ($allow, $setErrorModule = true) {
        return self::checkAccessControl($allow, $setErrorModule);
    }
    
    
    
    /**
     * 
     * Method checks an account based on session user_id. It checks: 
     * a) if an account is locked 
     * b) if the current user_id does not correspond to an account.
     * 
     * In both cases all sessions are killed. 
     * Method is run at boot. In diversen\boot
     *   
     * @return void
     */
    public static function checkAccount () {
        $user_id = session::getUserId();

        if ($user_id) {

            if (!self::getSystemCookieDb($user_id)) {
                self::killSessionAll($user_id);
                return false;
            }
            
            $a = q::select('account')->filter('id =', $user_id)->fetchSingle();            

            // user may have been deleted
            if (empty($a)) {
                self::killSessionAll($user_id);
                return false;
            } 
            
            // User is locked
            if ($a['locked'] == 1) {
                self::killSessionAll($user_id);
                return false;
            }
        }

        return true;
    } 
    
    /**
     * checks access for 'user', 'admin' or 'super'. It 
     * Loads error module if correct user level is not present
     * @return boolean $res true or false. 
     */
    public static function checkAccess ($type = null) {
        $res = self::checkAccessClean($type);
        
        if (!$res) {
            moduleloader::$status[403] = 1;
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Auth. Check if a user has the correct level. 
     * @deprecated
     * @param string $level 'user', 'admin', 'super'
     * @return boolean $res true if user has the needed level. 
     */
    public static function auth ($level) {
        return self::checkAccess($level);
    }
    
    /**
     * check access clean. This means from the three main groups of users
     * 'user', 'admin', 'super'
     * @param string $type
     * @return boolean $res true if success or false on failure
     */
    public static function checkAccessClean ($type = null) {
        $res = false;
        if ($type == 'user') {
            $res = self::isUser();
        }
        
        if ($type == 'admin') {
            $res = self::isAdmin();
        }
        
        if ($type == 'super') {
            $res = self::isSuper();
        }
        return $res;
    }
    
    /**
     * method for relocate user to login, and after correct login 
     * redirect to the page where he was. You can set message to
     * be shown on login screen.
     *  
     * @param string $message 
     */
    public static function loginThenRedirect ($message){
        unset($_SESSION['return_to']);
        if (!session::isUser()){
            $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
            session::setActionMessage($message);
            http::locationHeader('/account/login/index');
        }
    }

        /**
     * Method for getting users level (user, admin, super - or null)
     * return   null|string   $res null or 'user', 'admin' or 'super'.
     */
    public static function getUserLevel(){
        if (self::isSuper()){
            return "super";
        }
        if (self::isAdmin()){
            return "admin";
        }
        if (self::isUser()){
            return "user";
        }
        return null;
    }

}
