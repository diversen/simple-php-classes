<?php

namespace diversen\commands;

use diversen\profile;
use diversen\moduleloader;
use diversen\git;
use diversen\cli\common;
use diversen\conf;

class upgrade {

    /**
     * Define shell command and options
     * @return array $ary
     */
    public function getCommand() {
        return
                array(
                    'usage' => 'Upgrade existing system',
                    'options' => array (
                        '--upgrade' => 'Upgrade the system to a newer version if available'
                    )
        );
    }

    /**
     * Run command
     * @param \diversen\parseArgv $args
     */
    public function runCommand($args) {

        if ($args->getFlag('upgrade')) {
            $this->runUpgrade();
        }
    }

    /**
     * Run the upgrade command
     */
    function runUpgrade() {
        moduleloader::includeModule('system');
        $p = new profile();

        if (git::isMaster()) {
            common::abort('Can not make upgrade from master branch');
        }

        $repo = conf::getModuleIni('system_repo');
        $remote = git::getTagsRemoteLatest($repo);
        if ($p->upgradePossible()) {

            common::echoMessage("Latest version/tag: $remote", 'y');
            $continue = common::readlineConfirm('Continue the upgrade');
            if ($continue) {
                $this->upgradeTo($remote);
            }
        } else {
            $locale = git::getTagsInstallLatest();
            common::echoMessage("Latest version/tag: $locale", 'y');

            $continue = common::readlineConfirm('Continue. Maybe your upgrade was interrupted. ');
            if ($continue) {
                $this->upgradeTo($remote);
            }
        }
    }

    /**
     * Upgrade to a specific version
     * @param string $version
     */
    public function upgradeTo($version) {
        common::echoMessage("Will now pull source, and checkout latest tag", 'y');

        $command = "git fetch --tags && git checkout master && git pull && git checkout $version";
        $ret = common::execCommand($command);
        if ($ret) {
            common::abort('Aborting upgrade');
        }

        common::echoMessage("Will upgrade vendor with composer according to version", 'y');

        $command = "composer update";
        $ret = common::systemCommand($command);
        if ($ret) {
            common::abort('Composer update failed.');
        }

        common::echoMessage("Will upgrade all modules and templates the versions in the profile", 'y');

        // Upgrade all modules and templates
        $profile = conf::getModuleIni('system_profile');
        if (!$profile) {
            $profile = 'default';
        }

        $p = new \diversen\commands\profileCommand();
        $p->upgradeFromProfile($profile);

        // reload any changes
        common::echoMessage("Reloading all configuration files, menus and module changes", 'y');
        $p = new profile();
        $p->reloadProfile($profile);
    }
}
