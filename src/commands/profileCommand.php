<?php

namespace diversen\commands;

use diversen\conf;
use diversen\file;
use diversen\layout;
use diversen\moduleinstaller;
use diversen\moduleloader;
use diversen\profile;
use diversen\cli\common;

/**
 * File containing profile functions for shell mode
 *
 * @package     shell
 */
class profileCommand {


    public function getHelp() {
        return
                array(
                    'usage' => 'Git module management. Install latest version of a module or template',
                    'options' => array(
                        '-m' => 'Use master instead of version numbers specified in profile.inc',
                        '--no-secrets' => 'Common secrets (like SMTP, DB etc) will not be hidden when building profile',
                        '--load' => 'Load a profile. Any ini file from a profile will overwrite current ini file, including config/config.ini',
                        '--reload' => 'Same as loading a profile, but config/config.ini will not be loaded',
                        '--create' => 'Create a profile with specified name which will be placed in /profiles/{profile}',
                        '--recreate' => 'Recreate profile with specified name. Same as create, but new config/config.ini-dist will not be created',
                        '--all-up' => 'Upgrade from specified profile. If a new module or templates exists they will be installed',
                        '--all-in' => 'Install all modules from specified profile',
                        '--purge' => 'Remove modules and templates installed, that are no longer specified in profile'
                        ),
                    'arguments' => array(
                        'profile' => 'Specify the profile to create or install'
                    ),
        );
    }
       
    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand ($args) {
        
        $profile = $args->getValueByKey(0);
        
        if ($args->getFlag('m')) {
            $this->master();
        }
        
        if ($args->getFlag('no-secrets')) {
            $this->noSecrets();
        }
        
        if ($args->getFlag('load')) {
            $this->checkProfile($profile);
            return $this->loadProfile($profile);
        }
        
        if ($args->getFlag('reload')) {
            $this->checkProfile($profile);
            return $this->reloadProfile($profile);
        }
        
        if ($args->getFlag('create')) {
            $this->checkProfile($profile);
            return $this->createProfile($profile);
        }
        
        if ($args->getFlag('recreate')) {
            $this->checkProfile($profile);
            return $this->recreateProfile($profile);
        }
        
        if ($args->getFlag('all-up')) {
            $this->checkProfile($profile);
            return $this->upgradeFromProfile($profile);
        }
        
        if ($args->getFlag('purge')) {
            $this->checkProfile($profile);
            return $this->purgeFromProfile($profile);
        }
    }
    
    private function checkProfile ($profile) {
        if (!$profile) {
            common::abort('Specify a profile');
        }
    }
    
    /**
     * wrapper function for loading a profile
     */
    public function loadProfile($profile) {
        $pro = new profile();

        $profiles = file::getFileList('profiles', array('dir_only' => true));
        if (!in_array($profile, $profiles)) {
            common::abort('No such profile');
        }
        
        $pro->loadProfile($profile);
        
    }

    /**
     * Upgrade from profile
     * @param type $options array ('profile' => 'default', 'clone_only' => false) 
     */
    public function upgradeFromProfile($profile) {

        $pro = new profile();
        $pro->setProfileInfo($profile);

        // Upgrade modules
        foreach ($pro->profileModules as $key => $val) {
            
            if (isset(conf::$vars['profile_use_master'])) {
                $val['module_version'] = 'master';
            }
            $val['module'] = $val['module_name'];

            $module = new moduleinstaller();
            $module->setInstallInfo($val);

            $g = new \diversen\commands\gitCommand();
            if ($module->isInstalled($val['module_name'])) {
                $g->upgradeFromArray($val, $val['module_version'], 'module');
            } else {
                $g->installMod($val['public_clone_url'], $val['module_version'], 'module');
            }
        }

        // install templates
        foreach ($pro->profileTemplates as $key => $val) {

            $val['repo'] = $val['public_clone_url'];
            $val['version'] = $val['module_version'];

            if (isset(conf::$vars['profile_use_master'])) {
                $val['version'] = 'master';
            }

            // No DB operations. Only clone to specific version.
            $g = new \diversen\commands\gitCommand();
            if ($module->isInstalled($val['module_name'])) {
                
                $g->upgradeFromArray($val, $val['version'], 'template');
            } else {
                $g->installMod($val['public_clone_url'], $val['version'], 'module');
                
            }
        }
    }

    /**
     * will purge module not found in a profile
     * @param array $options
     */
    public function purgeFromProfile($profile) {
        
        $pro = new profile();
        $pro->setProfileInfo($profile);

        $modules = $pro->getModules();
        
        $m = new \diversen\commands\module();
        foreach ($modules as $module) {
            if (!$pro->isModuleInProfile($module['module_name'])) {
                $m->purgeModule($module['module_name']); 
            }
        }

        $temps = layout::getAllTemplates();
        $t = new \diversen\commands\template();
        foreach ($temps as $template) {
            if (!$pro->isTemplateInProfile($template)) {
                $t->purgeTemplate($template);
            }
        }
    }

    /**
     * wrapper function for reloading a profile
     * does the same as loading a profile, but keeps config/config.ini
     */
    public function reloadProfile($profile) {
        $pro = new profile();
        $pro->reloadProfile($profile);
    }

    /**
     * wrapper function for creating a profile
     */
    public function recreateProfile($profile) {
        $pro = new profile();
        $pro->recreateProfile($profile);
    }

    /**
     * sets a flag that indicates that we uses master when 
     * making profiles
     * @param array $options
     */
    public function master() {
        conf::$vars['profile_use_master'] = 1;
        $pro = new profile();
        $pro->setMaster();
    }

    /**
     * sets a flag indicating that we dont hide common secrets
     * when building a profile
     */
    public function noSecrets() {
        $pro = new profile();
        $pro->setNoHideSecrets();
    }

    /**
     * wrapper function for creating a profile
     */
    public function createProfile($profile) {
        $pro = new profile();
        $pro->createProfile($profile);
    }
}
