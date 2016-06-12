<?php

namespace diversen\mailer;

/**
 * Class with a few email helpers
 */
class helpers {

    /**
     * Get domain from an valid email
     * @param string $email
     * @return string $domain
     */
    public static function getDomain($email) {
        $exploded = explode('@', $email);
        return array_pop($exploded);
    }
    
    /**
     * Checks an email (by extracting the domain) against an array of domains 
     * to see if the email's domain is valid
     * @param string $email
     * @param array $domains
     * @return boolean $res true if valid else false
     */
    public static function isValidDomainEmail($email, $domains = array()) {
        $email_domain = self::getDomain($email);
        if (!in_array($email_domain, $domains)) {
            return false;
        }
        return true;
    }

    /**
     * Extract all emails from a text string
     * @param string $txt
     * @return array $emails
     */
    public static function getEmails($txt) {
        $pattern = "/([\s]*)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*([ ]+|)@([ ]+|)([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,}))([\s]*)/i";

        // preg_match_all returns an associative array
        preg_match_all($pattern, $txt, $matches);

        // all emails caught in $matches[0]
        if (!empty($matches[0])) {
            foreach ($matches[0] as $key => $val) {
                $matches[0][$key] = strtolower(trim($val));
            }
        }
        $matches = array_unique($matches[0]);
        return  $matches;
    }
}
