<?php

namespace diversen\cli;

/**
 * A class for validating options
 * Checks for options using '-' or '--'. 
 * Any other option is ignored
 * @example 
<code>
        // The commandline string to examine 
        $str = "-s -S --cchapters=7 -V geometry:margin=1in -V documentclass=memoir -V lang=danish";

        // Allowed options
        $allow = array(
            // Produce typographically correct output, converting straight quotes to curly quotes 
            'S' => null,
            'smart' => null,
            // Specify the base level for headers (defaults to 1).
            'base-header-level' => null,
            // Produce output with an appropriate header and footer 
            's' => null,
            'standalone' => null,
            // Include an automatically generated table of contents
            'toc' => null,
            // Specify the number of section levels to include in the table of contents. The default is 3
            'toc-depth' => null,
            
            // no highlight of language
            'no-highlight' => null,
            // Options are pygments (the default), kate, monochrome, espresso, zenburn, haddock, and tango.
            
            'highlight-style' => null,
            // Produce HTML5 instead of HTML4. 
            'html5' => null,
            // Treat top-level headers as chapters in LaTeX, ConTeXt, and DocBook output.
            'chapters' => null,
            // Number section headings in LaTeX, ConTeXt, HTML, or EPUB output.
            'N' => null,
            'number-sections' => null,
            // Link to a CSS style sheet (for HTML - not allowed). 
            //'c' => null,
            //'css' => null,
            // user template
            
            'template' => null,
            // Use the specified CSS file to style the EPUB
            'epub-stylesheet' => null,
            'epub-chapter-level' => '1-6',
            // epub-embed-font
            'epub-embed-font' => null,
            // Specify output format.

            'V' => array(
                'geometry:margin',
                'documentclass', 
                'lang',
                'fontsize',
                'mainfont',
                'sansfont',
                'monofont',
                'boldfont',
                'version',
                'toc-depth'),
        );

        $o = new optValid();
        $ary = $o->split($str);
        $ary = $o->getAry($ary);
        $ary = $o->setSubVal($ary);
        $ok = $o->isValid($ary, $allow);
        if ($ok) {
            echo "Seems ok!";
        } else {
            echo "there seems to be something wrong";
        }
</code>  
 */
 
class optValid {

    /**
     * Splits string with '-' and '--' values
     * @param string $str string commandline options as a string
     * @return array $opts array of options given
     */
    public function split($str) {
        $str = trim($str);
        $str = " " . $str;

        // get opts raw which means space- or space--
        $opts = array_filter(preg_split("/[\s+][-]{1,2}/", $str));
        return $opts;
    }

    /**
     * From all opts we get an array of arrays with values where
     * '0' key = the command, and '1' = the key value of the command
     * @param array $opts
     * @return array $ret
     */
    public function getAry($opts) {

        $ret = array();
        foreach ($opts as $opt) {
            $opt = trim($opt);
            // space args, e.g. -V test=7
            $ary = preg_split("/[\s+]/", $opt);
            if (!isset($ary[1])) {
                // equal args e.g. --chapters=7
                $ret[] = preg_split("/[=]/", $opt);
            } else {
                $ret[] = $ary;
            }
        }
        return $ret;
    }
    
    /**
     * sets an array with sub commands, e.g.
     * -V val=test
     * @param array $ary
     * @return array
     */
    public function setSubVal ($ary) {
        foreach ($ary as $key => $opt) {
            if (isset($opt[1])) {
                $val = preg_split("/[=]/", $opt[1]);
                if (isset($val[1])) {
                    $ary[$key][2] = $val;
                }
            }
        }
        return $ary;
    }
    
    /**
     * Var holding errors
     * @var array $errors
     */
    public $errors = array ();
    
    /**
     * Checks if an array of commands are valid based on an array
     * of allowed commands
     * @param array $ary
     * @param array $allow
     * @return boolena $res true if the commands are valid else false
     */
    public function isValid($ary, $allow) {
        foreach ($ary as $key => $val) {
            $opt = $val[0];
            
            // check if option main option if OK
            if (!array_key_exists($opt, $allow)) {
                $this->errors[] = $opt;
            }
            
            // sub option
            if (isset($val[2])) {
                $sub_opt = $val[2][0];
                if (!in_array($sub_opt, $allow[$opt])) {
                    $this->errors[] = $sub_opt;
                }
            }
        }

        if (empty($this->errors)) {
            return true;
        }
        return false;
    }
}
