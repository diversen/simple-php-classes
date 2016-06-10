<?php

namespace diversen\mailer;

use cebe\markdown\GithubMarkdown;
use diversen\conf;
use diversen\html;
use diversen\log;

class markdown {
        
    /**
     * Include title in body
     * @var type 
     */
    public $titleInBody = true;

    /**
     * Get HTML mail part from ID
     * @param string $title
     * @param string $body
     * @paramn string $template_path
     * @return string $html
     */
    public function getEmailHtml ($title, $body, $template_path = null) {

        $template = $this->getHtmlTemplate($template_path);  
        if ($this->titleInBody) {
            $body = "# " . $title . PHP_EOL . PHP_EOL . $body;
        }
        
        
        $parser = new GithubMarkdown();
        $body = $parser->parse($body);
        $subject = html::specialEncode($title);
        $str = str_replace(array('{title}', '{content}'), array ($subject, $body), $template);
        return $str;
    }
    
    /**
     * Get HTML template for email
     * NOTE: Email needs to be set in config/config.ini when using CLI
     * @param string $path path to template
     * @return string $html
     */
    public function getHtmlTemplate($path = null) {
        
        // Default path
        if (!$path) {
            $template = conf::getMainIni('template');
            $path = conf::getTemplatePath($template) . '/mail/template.html';
        }
        
        if (!file_exists($path)) {
            log::error('mailer/markdown: path does not exists: ' . $path);
            die();
        }
        
        $email = file_get_contents($path);
        return $email;
    }
}
