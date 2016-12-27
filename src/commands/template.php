<?php

namespace diversen\commands;

use diversen\conf;
use diversen\profile;
use diversen\templateinstaller;
use diversen\cli\common;

class template {
    
    /**
     * Define shell command and options
     * @return array $ary
     */
    public function getHelp() {
        return
                array(
                    'usage' => 'Template management',
                    'options' => array(
                        '--set' => 'Commit single module',
                        '--temp-in' => 'Commit single template',
                        '--purge' => 'Tag all modules and templates with specified version, and push to remote'),
                    'arguments' => array(
                        'repo' => 'Specify the version to upgrade or downgrade to'
                    ),
        );
    }

    /**
     * Run the command
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {
        
        $template = $args->getValueByKey(0);
        if ($args->getFlag('set')) {
            return $this->setTemplate($template);
        }
        if ($args->getFlag('temp-in')) {
            return $this->installTemplate($template);
        }
        
        if ($args->getFlag('purge')) {
            return $this->purgeTemplate($template);
        }
        return 0;
    }

    /**
     * Set a template in database settings
     * @param string $template
     */
    public function setTemplate($template) {
        $pro = new profile();
        return $pro->setProfileTemplate($template);
    }

    /**
     * Install a template
     * @param string $template
     */
    public function installTemplate($template) {

        $options = [];
        $options['template'] = $template;
        
        $str = "Proceeding with install of template $template";
        $install = new templateinstaller();
        
        if (!$install->setInstallInfo($options)) {
            return false;
        }
        $ret = $install->install();
        if (!$ret) {
            $str .= $install->error;
        } else {
            $str .= $install->confirm;
        }

        common::echoMessage($str);
    }

    /**
     * function for purgeing a template
     * @param string $template
     */
    public function purgeTemplate($template) {
        //uninstall_module($options);
        if (strlen($template) == 0) {
            common::echoMessage("No such template: $options[template]");
            common::abort();
        }
        $template_path = conf::pathBase() . '/htdocs/templates/' . $template;
        if (!file_exists($template_path)) {
            common::echoMessage("Template already purged: No such template path: $template_path");
            common::abort();
        }
        $command = "rm -rf $template_path";
        common::execCommand($command);
    }

}
