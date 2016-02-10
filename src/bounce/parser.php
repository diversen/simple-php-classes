<?php

namespace diversen\bounce;

use diversen\conf;
use diversen\date;
use diversen\db\rb;
use diversen\imap;
use diversen\log;
use R;
use Exception;

/**
 * A simple PHP bounce barser using IMAP
 * @package
 */
class parser {
    
    /**
     * Constructor withs initialize the IMAP object
<code>
array(
        'host' => 'imap_host',
        'port' => 'imap_port',
        'user' => 'imap_user',
        'password' => 'imap_password',
        'ssl' => 'imap_ssl
    );
</code>
     @param array $options

     */
    public function __construct($options = array()) {
        rb::connect();
        if (empty($options)) {
            $options = array(
                'host' => conf::getMainIni('imap_host'),
                'port' => conf::getMainIni('imap_port'),
                'user' => conf::getMainIni('imap_user'),
                'password' => conf::getMainIni('imap_password'),
                'ssl' => conf::getMainIni('imap_ssl')
            );
        }
        $this->options = $options;
    }
    
    /**
     * var holding options 
     * @var array 
     */
    public $options = array();
    
    /**
     * Connect to IMAP server using IMAP object `diversen\imap` with
     * options set in constructor
     * @return diversen\imap $imap diversen\imap
     */
    public function getImap () {
        $imap = new imap();
        $imap->connect($this->options);
        return $imap;
    }
    /**
     * Connect and parse mails found in a the bounce mailbox 
     * This should be easy to add to a cron job
     * @param boolean $remove true if we want messages to be removed.  
     */
    public function parse($remove = true) {

        $imap = $this->getImap();
        $c = $imap->countMessages();

        log::debug("Parse bounces. Num messages: $c");
        $imap->mail->noop();

        // reverse - we start with latest message = $c
        for ($x = $c; $x >= 1; $x--) {
            log::debug("Pasing num: $x");
            $res = $this->parseMessage($imap, $x); 
            if (!$res) {
                log::debug('Could not parse mesage $x');
            }
            
            $imap->mail->noop(); // keep alive
            if ($remove) {
                $imap->mail->removeMessage($x);
            }
            $imap->mail->noop(); // keep alive
            sleep(1);
        }
    }
    
    /**
     * Parse a single email message, and save status code of message
     * in database. 
     * @param imap $imap diversen\imap
     * @param int $x the number of the message
     */
    public function parseMessage($imap, $x) {

        $imap->mail->noop();
        
        try {
            $message = $imap->mail->getMessage($x);
        } catch (Exception $e) {
            log::error($e->getMessage());
            return false;
           
        }
        $imap->mail->noop();

        $parts = $imap->getAllParts($message); 

        // check for valid message/delivery-status'
        if (!isset($parts['message/delivery-status'][0])) {
            log::debug("No delivery status");
            return false;
        } else {
            $delivery_status = $parts['message/delivery-status'][0];
        }
        
        $email = self::getBounceEmail($delivery_status);
        if ($email) {

            log::error("Found email in message: $email");
            $bean = rb::getBean('mailerbounce');
            $bean->deleted = 0;

            // get bounce code e.g 4.4.2 hotmail
            $bounce_code = trim(self::getBounceCode($delivery_status));

            if ($bounce_code) {
                $bounce_ary = explode('.', $bounce_code);
                $bean->major = $bounce_ary[0];
                $bean->minor = $bounce_ary[1];
                $bean->part = $bounce_ary[2];
                $bean->bouncecode = $bounce_code;
            } else {
                $bean->bouncecode = null;
            }

            $bean->email = $email;

            $bean->bouncedate = date::getDateNow(array('hms' => true));
            $bean->message = $delivery_status;
            $bean->returnpath = $message->getHeader('return-path', 'string');
            R::store($bean);
            log::debug("Stored user with email: $email. $bounce_code" . PHP_EOL);
        } else {
            log::error("Did not get a mail from message: " . $delivery_status);
        }
        return true;
    }

    /*
     * Delete all messages
     */
    public function deleteAll() {

        $connect = array(
            'host' => conf::getMainIni('imap_host'),
            'port' => conf::getMainIni('imap_port'),
            'user' => conf::getMainIni('imap_user'),
            'password' => conf::getMainIni('imap_password'),
            'ssl' => conf::getMainIni('imap_ssl')
        );

        $i = new imap();
        $i->connect($connect);
        $c = $i->countMessages();

        log::error("Parse bounces. Num messages: $c\n");
        $i->mail->noop();

        // reverse - we start with latest message = $c
        for ($x = $c; $x >= 1; $x--) {

            log::error("Pasing num: $x");
            $i->mail->noop(); // keep alive
            $i->mail->removeMessage($x);
            $i->mail->noop(); // keep alive
            sleep(1);
        }
    }
    
    /**
     * Returns the bounce code from [message/delivery-status] part of message
     * e.g. 4.2.2
     * @param string $mail the email message
     * @return string $code e.g. 4.2.2
     */
    public static function getBounceCode($mail) {

        // make txt an array
        $ary = explode("\n", $mail);
        foreach ($ary as $val) {

            // find satus line
            $str = strtolower($val);
            if (strstr($str, 'status')) {
                $str = str_replace('status', '', $str);
                $str = str_replace(':', '', $str);
                $str = str_replace(' ', '', $str);
                $str = trim($str);
                return $str;
            }
        }
        return null;
    }
    
    /**
     * Returns email from [message/delivery-status] part of message
     * looks for 'final-recipient: ' and returns the email
     * @param string $mail the mail message
     * @return string $email the email
     */
    public static function getBounceEmail($mail) {

        // make txt an array
        $ary = explode("\n", $mail);
        foreach ($ary as $val) {

            // find satus line
            $str = strtolower($val);
            if (strstr($str, 'final-recipient')) {
                $str = str_replace('final-recipient', '', $str);
                return self::getEmailFromStr($str);
            }
        }
        return null;
    }
    
    /**
     * Search a message for an email
     * @param type $str
     * @return boolean
     */
    public static function getEmailFromStr ($str) {
        $pattern = "/([\s]*)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*([ ]+|)@([ ]+|)([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,}))([\s]*)/i";
        
        // preg_match_all
        preg_match_all($pattern, $str, $matches);

        // all emails caught in $matches[0]
        if (!empty($matches[0])) {
            $matches = array_unique($matches[0]);
            return array_pop($matches);
        }
        return false;
        
    }
}
