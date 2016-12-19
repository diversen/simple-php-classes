<?php

namespace diversen\commands;

use diversen\apache2;


/**
 * File contains shell commands for apache2 on Debian systems for fast
 * creatiion of apache2 web hosts
 */
class apache2Command {

    public function getHelp() {
        return 
            array (
                'usage' => 'Apache2 commands (For Linux). Install, remove hosts.',
                'options' => array (
                    '--enable' => 'Will enable current directory as an apache2 virtual host. Will also add new sitename to your /etc/hosts file',
                    '--disable' => 'Will disable current directory as an apache2 virtual host, and remove sitename from /etc/hosts files',
                    '--ssl' => 'Set this flag and enable SSL mode'),
                'arguments' => array (
                    'Hostname' => 'Specify the apache hostname to be used for install or uninstall. yoursite will be http://yoursite'
                )
            );
    }

    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {

        $hostname = $args->getValueByKey(0);
        
        if (!$hostname OR empty($hostname)) {
            echo "Specify hostname" . PHP_EOL;
            exit(128);
        }
        
        $options['hostname'] = $hostname;
        
        if ($args->getFlag('ssl')) {
            apache2::setUseSSL();
        }        
        if ($args->getFlag('enable')) {
            apache2::enableSite($options);
        }
        if ($args->getFlag('disable')) {
            apache2::disableSite($options);
        }
        return 0;
    }
}
