<?php

namespace diversen\commands;

use diversen\translate\extractor;
use diversen\conf;
use diversen\cli\common;

class translateCommand {

    /**
     * Define shell command and options
     * @return array $ary
     */
    public function getCommand() {
            return
                array(
                    'usage' => 'Auto-extract strings and add them to translation files',
                    'options' => array(
                        '--all-up' => 'Update all translation files for all modules and templates. Default is en'
                    ),
                    'arguments' => array(
                        'language' => "Specicify the language, e.g. 'de' or 'da' to extract to. Default is 'en'"
                    ),
        );
    }
    
    /**
     * Run the command
     * @param \diversen\parseArgv $args
     */
    public function runCommand ($args) {
        $language = $args->getValueByKey(0);
        
        if ($args->getFlag('all-up')) {
            if (!$language) {
                $language = 'en';
            }
            $this->updateAll($language);
        }
    }

    
    /**
     * will update all translation files in specified language
     * @param array $options
     */
    public function updateAll($language) {
        $e = new extractor();
        $e->defaultLanguage = $language;
        $e->setDirsInsideDir('modules/');
        $e->setDirsInsideDir('htdocs/templates/');
        $e->setSingleDir("vendor/diversen/simple-php-classes");
        $e->setSingleDir("vendor/diversen/simple-pager");
        $e->updateLang();
    }

    /**
     * will update all translation files in specified language
     * @deprecated
     * @param array $options
     */
    public function translate_path($options) {
        if (!isset($options['path'])) {
            common::abort('Add a path');
        }

        $path = conf::pathBase() . "/$options[path]";
        if (!file_exists($path) OR ! is_dir($path)) {
            common::abort('Specify a dir as path');
        }

        $e = new extractor();
        if (!empty($options['language'])) {
            $e->defaultLanguage = $options['language'];
        }

        $e->setSingleDir($options['path']);
        $e->updateLang();
        common::echoStatus('OK', 'g', 'Extraction done');
    }
}
