<?php

namespace diversen\commands;

use diversen\translate\google;
use diversen\conf;
use diversen\cli\common;

class googleTranslate {

    /**
     * Define shell command and options
     * @return array $ary
     */    
    public function getCommand() {
        return
                array(
                    'usage' => 'Translate application into another language using Googles Translate API',
                    'options' => array(
                        '--all-up' => 'Translate all modules and common modules'),
                    'arguments' => array(
                        'target' => 'Specicify the target language which we will translate into',
                        'source' => 'Specicify the sourtce language which we will translate into',
                    ),
        );
    }
    
    /**
     * Run the command
     * @param \diversen\parseArgv $args
     */
    public function runCommand ($args) {
    
        $target = $args->getValueByKey(0);
        $source = $args->getValueByKey(1);
        
        if (!$target || !$source) {
            common::abort("Specify 'source' and 'target' language");
        }
        
        if ($args->getFlag('all-up')) {
            $this->update($target, $source);
        }
        
    }

    /**
     * Update translation from target and source
     * @param string $target
     * @param string $source
     */
    public function update($target, $source) {

        $t = new google();

        $t->target = $target;
        $t->source = $source;

        $key = conf::getMainIni('google_translate_key');
        $t->key = $key;
        $t->setDirsInsideDir('modules/');
        $t->setDirsInsideDir('htdocs/templates/');
        $t->setSingleDir("vendor/diversen/simple-php-classes");
        $t->setSingleDir("vendor/diversen/simple-pager");
        $t->updateLang();
    }

    /**
     * @deprecated 
     * @param type $options
     */
    function google_translate_path($options) {
        if (!isset($options['path'])) {
            common::abort('You need to specify path to translate');
        }
        if (!isset($options['target'])) {
            common::abort('You need to specify target language to translate into');
        }
        $e = new google();
        $key = conf::getMainIni('google_translate_key');
        $e->key = $key;
        $e->setSingleDir($options['path']);
        $e->updateLang();
    }
}
