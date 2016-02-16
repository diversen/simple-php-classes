<?php

namespace diversen;

/**
 * File contains a wrapper for curl.
 * class found on php.net
 * http://php.net/manual/en/book.curl.php
 * slightly modified from above class.  
 */
class mycurl {

    /**
     * Default user agent
     * @var string $_useragent 
     */
    protected $_useragent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1';

    /**
     * URL to curl
     * @var string $_url
     */
    protected $_url;

    /**
     * Flag indicating if we follow urls or not
     * @var boolean $_followlocation
     */
    protected $_followlocation;

    /**
     * Set timeout in secs
     * @var int $_timeout
     */
    protected $_timeout;

    /**
     * Set max redirects
     * @var int $_maxRedirects
     */
    protected $_maxRedirects;

    /**
     * Set cookie jar file
     * @var string $_cookieFileLocation
     */
    protected $_cookieFileLocation = '/tmp/cookie.txt';

    /**
     * Flag indication if we are doing any post
     * @var boolean $_post
     */
    protected $_post;

    /**
     * Var containing vars to post
     * @var array $_postFields
     */
    protected $_postFields;

    /**
     * Var setting referer
     * @var string $_referer
     */
    protected $_referer = "http://www.google.com";

    /**
     * var holding the curled webpage
     * @var string $_webpage
     */
    private $_webpage;

    /**
     * Flag indicating if we want to include headers
     * @var boolean $_includeHeader
     */
    protected $_includeHeader;

    /**
     * Flag indicating if we ignore body of webpage
     * @var boolean $_noBody
     */
    protected $_noBody;

    /**
     * Var indicating the status of the webpage, e.g. 200
     * @var int $_status
     */
    protected $_status;

    /**
     * Flag indicating if this is a binary transfer
     * @var boolean $_binaryTransfer 
     */
    protected $_binaryTransfer;

    /**
     * Var indicating if we use basic auth
     * @var boolean $authentication
     */
    protected $_authentication = 0;

    /**
     * var holding basic auth username
     * @var string $auth_name
     */
    protected $_auth_name = '';

    /**
     * var holding basic auth password
     * @var string $auth_pass
     */
    protected $_auth_pass = '';

    /**
     * Specify a request method - other than GET or POST, e.g .DELETE or 
     * PATCH
     * @var string $_request 
     */
    public $_request = null;

    /**
     * method setting use auth
     * @param boolean $use 1 or 0 
     */
    public function useAuth($use) {
        $this->_authentication = 0;
        if ($use == true) {
            $this->_authentication = 1;
        }
    }

    /**
     * Set auth name
     * @param string $name
     */
    public function setName($name) {
        $this->_auth_name = $name;
    }

    /**
     * set auth pass
     * @param string $pass
     */
    public function setPass($pass) {
        $this->_auth_pass = $pass;
    }

    /**
     * Sets referer
     * @param string $referer 
     */
    public function setReferer($referer) {
        $this->_referer = $referer;
    }

    /**
     * Sets cookie jar file
     * @param string $path 
     */
    public function setCookiFileLocation($path) {
        $this->_cookieFileLocation = $path;
    }

    /**
     * Set post fields
     * @param array $postFields
     */
    public function setPost($postFields) {
        $this->_post = true;
        $this->_postFields = $postFields;
    }

    /**
     * Set useragent
     * @param string $userAgent
     */
    public function setUserAgent($userAgent) {
        $this->_useragent = $userAgent;
    }

    /**
     * Sets request type, e.g. PATCH, DELETE
     * @param string $request
     */
    public function setRequest($request) {
        $this->_request = $request;
    }

    /**
     * Return headers when returning web page
     * @param boolean $bool
     */
    public function includeHeader($bool) {
        $this->_includeHeader = $bool;
    }

    /**
     * Constructor. Sets up curl object
     * @param string  $url
     * @param boolean $followlocation
     * @param int $timeOut
     * @param int $maxRedirecs
     * @param boolean $binaryTransfer
     * @param boolean $includeHeader
     * @param boolean $noBody
     */
    public function __construct(
    $url, $followlocation = true, $timeOut = 30, $maxRedirecs = 4, $binaryTransfer = false, $includeHeader = false, $noBody = false) {
        $this->_url = $url;
        $this->_followlocation = $followlocation;
        $this->_timeout = $timeOut;
        $this->_maxRedirects = $maxRedirecs;
        $this->_noBody = $noBody;
        $this->_includeHeader = $includeHeader;
        $this->_binaryTransfer = $binaryTransfer;
        $this->_cookieFileLocation = sys_get_temp_dir() . '/curl_cookie.txt';
    }

    /**
     * Create curl
     * @param string $url = 'nul'
     */
    public function createCurl($url = 'nul') {
        if ($url != 'nul') {
            $this->_url = $url;
        }

        $s = curl_init();

        curl_setopt($s, CURLOPT_URL, $this->_url);
        curl_setopt($s, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($s, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($s, CURLOPT_MAXREDIRS, $this->_maxRedirects);
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_FOLLOWLOCATION, $this->_followlocation);
        curl_setopt($s, CURLOPT_COOKIEJAR, $this->_cookieFileLocation);
        curl_setopt($s, CURLOPT_COOKIEFILE, $this->_cookieFileLocation);

        if ($this->_request) {
            curl_setopt($s, CURLOPT_CUSTOMREQUEST, $this->_request);
        }

        if ($this->_authentication == 1) {
            curl_setopt($s, CURLOPT_USERPWD, $this->_auth_name . ':' . $this->_auth_pass);
        }

        if ($this->_post) {
            // only set CURLOPT_POST is _request is empty or 'POST'
            if ($this->_request == 'POST' || !isset($this->_request)) {
                curl_setopt($s, CURLOPT_POST, true);
            }
            curl_setopt($s, CURLOPT_POSTFIELDS, $this->_postFields);
        }

        if ($this->_includeHeader) {
            curl_setopt($s, CURLOPT_HEADER, true);
        }

        if ($this->_noBody) {
            curl_setopt($s, CURLOPT_NOBODY, true);
        }

        if ($this->_binaryTransfer) {
            curl_setopt($s, CURLOPT_BINARYTRANSFER, true);
        }

        curl_setopt($s, CURLOPT_USERAGENT, $this->_useragent);
        curl_setopt($s, CURLOPT_REFERER, $this->_referer);

        $this->_webpage = curl_exec($s);
        $this->_status = curl_getinfo($s, CURLINFO_HTTP_CODE);
        curl_close($s);
    }

    /**
     * get http status of curl operation
     */
    public function getHttpStatus() {
        return $this->_status;
    }

    /**
     * Magic method. Returns webpage as string
     */
    public function __tostring() {
        return $this->_webpage;
    }

    /**
     * Return webpage from curl operation
     */
    public function getWebPage() {
        return $this->_webpage;
    }

}

