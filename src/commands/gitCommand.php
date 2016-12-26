<?php

namespace diversen\commands;

use diversen\conf;
use diversen\profile;
use diversen\cli\common;
use diversen\git;
use diversen\commands\module;
use diversen\commands\template;

class gitCommand {

    public function getHelp() {
        return
                array(
                    'usage' => 'Git module management. Install latest version of a module or template',
                    'options' => array(
                        '-s' => 'Silence all questions',
                        '-m' => 'Use master',
                        '--mod-in' => 'Clone specified remote module url with latest version and install',
                        '--mod-up' => 'Check out latest tag of a module. If remote version is higher it will be checked out and installed',
                        '--mod-commit' => 'Commit single module',
                        '--temp-in' => 'Clone specified remote template url with latest version and install',
                        '--temp-up' => 'Check out latest tag of a template. If remote version is higher it will be checked out',
                        '--temp-commit' => 'Commit single template',
                        '--all-up' => 'Check out latest remote versions of modules and templates, and compare with locale version. If remote is higher it will be checked out and  upgraded',
                        '--all-commit' => 'Commit all modules and templates',
                        '--all-tag' => 'Tag all modules and templates with specified version, and push to remote',
                        '--compare' => 'Compare locale version and remote master'),
                    'arguments' => array(
                        'repo' => 'A git repo with the module. Or a module name'
                    ),
        );
    }
    
    /**
     * 
     * @param \diversen\parseArgv $args
     */
    public function runCommand ($args) {
        
        $repo =     $args->getValueByKey(0);
        $version =  $args->getValueByKey(1);
        
        if ($args->getFlag('s')) {
            $this->silence();
        }
        
        if ($args->getFlag('m')) {
            $this->master();
        }
 
        if ($args->getFlag('mod-in')) {
            if (!$repo) {
                common::abort('Specify a repo');
            }
            return $this->installMod($repo, $version, 'module');
        }
        
        if ($args->getFlag('mod-up')) {
            if (!$repo) {
                common::abort('Specify a module name');
            }
            return $this->upgradeModule($repo, $version, 'module');
        }
        
        if ($args->getFlag('mod-commit')) {
            if (!$repo) {
                common::abort('Specify a module name');
            }
            return $this->commitModule($repo, 'module');
        }
         
        if ($args->getFlag('temp-in')) {
            if (!$repo) {
                common::abort('Specify a template git repo');
            }
            return $this->installMod($repo, 'template');
        }
        
        if ($args->getFlag('temp-up')) {
            if (!$repo) {
                common::abort('Specify a template module');
            }
            return $this->upgradeModule($repo, $version, 'template');
        }
        
        if ($args->getFlag('temp-commit')) {
            if (!$repo) {
                common::abort('Specify a module name');
            }
            return $this->commitModule($repo, 'template');
        }
        
        if ($args->getFlag('all-up')) {
            return $this->upgradeAll();
        }
        
        if ($args->getFlag('all-commit')) {
            return $this->commitAll();
        }
        
        if ($args->getFlag('all-tag')) {
            return $this->gitTagAll();
        }
        
        if ($args->getFlag('compare')) {
            return $this->gitCompare();
        }
    }

    /**
     * funtion for installing a module from a git repo name
     * @param array     $options array ('repo' => 'git::repo')
     * @param string    $type (module, profile or template)
     */
    public function installMod($repo, $version, $type = 'module') {
        
        $mod = new module();
        $temp = new template();
        
        $module_name = git::getModulenameFromRepo($repo);
        if (!$module_name) {
            common::abort('Install command need a valid repo name');
        }

        $this->gitClone($repo, $version, $type);
        if ($type == 'module') {
            $ret = $mod->installModule($module_name);
            return $ret;
        }

        if ($type == 'template') {
            $ret = $temp->installTemplate($module_name);
            return $ret;
        }
    }

    /**
     * function for getting path to a repo.
     * @param string     $module_name
     * @param string     $type (module, profile, template)
     * @return string    $repo_path the locale path to the repo.
     */
    public function getRepoPath($module_name, $type = 'module') {
        // set repo_dir according to module type.
        if ($type == 'template') {
            $repo_dir = conf::pathBase() . "/htdocs/templates/$module_name";
        } else if ($type == 'profile') {
            $repo_dir = conf::pathBase() . "/profiles/$module_name";
        } else {
            $repo_dir = conf::pathModules() . "/$module_name";
        }
        return $repo_dir;
    }

    /**
     * function used for cloning a repo
     * @param array $options
     * @param string $type
     */
    public function gitClone($repo, $version, $type) {

        // Get version
        if (!$version) {
            $version = git::getTagsRemoteLatest($repo);  
        }

        // check if profile use master or if master is set
        if (isset(conf::$vars['git_use_master']) ) {
            $version = 'master';
        }

        // set dir according to module type. Template, profile or module.
        if ($type == 'template') {
            $clone_path = conf::pathHtdocs() . "/templates";
        }  else {
            $clone_path = conf::pathModules();
        }

        // create path if it does not exists
        if (!file_exists($clone_path)) {
            mkdir($clone_path);
        }

        
        $module_name = git::getModulenameFromRepo($repo);
        $module_path = "$clone_path/$module_name";

        // if dir exists we check if it is a git repo
        // or just a directory
        $ret = null;
        if (file_exists($module_path)) {
            $repo_dir = $clone_path . "/$module_name";

            // check if path is a git repo
            $git_folder = $repo_dir . "/.git";
            if (file_exists($git_folder)) {
                // repo exists. We pull changes and set version
                $git_command = "cd $repo_dir && git checkout master && git pull && git checkout $version";
            } else {
                // no git repo - empty dir we presume.
                $git_command = "cd $clone_path && git clone $repo $module_name && cd $module_name && git checkout $version";
            }
            $ret = common::execCommand($git_command);
        } else {
            $git_command = "cd $clone_path && git clone $repo $module_name && cd $module_name && git checkout $version";
            $ret = common::execCommand($git_command);
        }

        // evaluate actions
        if ($ret) {
            common::abort("$git_command failed");
        }
    }

    /**
     * cli call function is --master is set then master will be used instead of
     * normal tag
     *
     * @param array $options
     */
    public function master() {
        conf::$vars['git_use_master'] = 1;
    }

    /**
     * updates a single module
     * @param array $options
     */
    public function upgradeTemplate($template) {
        
        if (!\diversen\moduleloader::isInstalledModule($template)) {
            common::abort("$template is not an installed template");
        }

        $path = conf::pathHtdocs() . "/templates/$template";
        $p = new profile();
        if ($this->isRepo($path)) {
            $mod = $p->getTemplate($template);
            $this->upgradeTemplateFromArray($mod);
        } else {
            common::echoStatus('ERROR', 'r', "--temp-up needs a template name, e.g. 'clean'. The module must exists in the module dir");
        }
    }

    /**
     * Update a single module. It will checkout the latest tag. 
     * @param string $module
     */
    public function upgradeModule($module) {

        if (!\diversen\moduleloader::isInstalledModule($module)) {
            common::abort("$module is not an installed module");
        }
        
        // Path
        $path = conf::pathModules() . "/$module";
         
        $p = new profile();

        // Upgrade if repo
        if ($this->isRepo($path)) {
            
            // Get module from database
            $ary = $p->getModule($module);
            $this->upgradeModuleFromArray($ary);
        } else {
            common::echoStatus('ERROR', 'r', "Mdule needs to be a git repo");
        }
    }

    /**
     * Upgrade a module to latest tag based on databased values
     * @param array $val module array with public repo path set
     * @param string $version
     */
    public function upgradeModuleFromArray($val) {
        
        if (isset(conf::$vars['git_use_master'])) {
            $tag = 'master';
        } else {
            $tag = git::getTagsRemoteLatest($val['public_clone_url'], true);
        }

        if (($tag == 'master') OR ( $tag != $val['module_version'])) {
            $this->upgradeFromArray($val, $tag, 'module');
        } else {
            common::echoStatus('NOTICE', 'y', "Nothing to upgrade. Module '$val[module_name]' is latest version: $tag");
        }
    }

    /**
     * upgrade a template
     * @param type $val
     */
    public function upgradeTemplateFromArray($val) {
        if (isset(conf::$vars['git_use_master'])) {
            $tag = 'master';
        } else {
            $tag = git::getTagsRemoteLatest($val['public_clone_url'], true);
        }

        if (($tag == 'master') OR ( $tag != $val['module_version'])) {
            $this->upgradeFromArray($val, $tag, 'template');
        } else {
            common::echoStatus('NOTICE', 'y', "Nothing to upgrade: Version is: $tag");
        }
    }

    /**
     * get latest tag for modules and templates and
     * upgrade according to latest tag
     * @param   array   options from cli env
     */
    public function upgradeAll() {

        $profile = new profile();

        $modules = $profile->getModules();

        foreach ($modules as $key => $val) {
            $this->upgradeModuleFromArray($val);
        }

        $templates = $profile->getTemplates();
        foreach ($templates as $key => $val) {
            $this->upgradeTemplateFromArray($val);
        }
    }

    /**
     * function for adding and commiting all modules and templates
     * @param   array   options from cli env
     */
    public function commitAll() {

        $profile = new profile();
        $modules = $profile->getModules();
        foreach ($modules as $key => $val) {
            $this->commitModuleFromDbValues($val, 'module');
        }

        $templates = $profile->getTemplates();
        foreach ($templates as $key => $val) {
            $this->commitModuleFromDbValues($val, 'template');
        }
    }

    /**
     * function for adding and commiting all modules and templates
     * @param   array   options from cli env
     */
    public function commitModule($module, $type = 'module') {

        if ($type == 'template') {
            $path = conf::pathHtdocs() . "/templates/$module";
        } else {
            $path = conf::pathModules() . "/$module";
            
        }
        if (!$this->isRepo($path)) {
            common::abort("Module: $module is not a git repo. Specify installed module name (e.g. 'settings') when commiting");
        }

        $p = new profile();
        if ($type == 'template') {
            $mod = $p->getTemplate($module);
        } else {
            $mod = $p->getModule($module);
        }
        $this->commitModuleFromDbValues($mod, $type);
    }

    /**
     * shell callback function for commiting a single template
     * @param array $options array ('repo')
     */
    public function gitCommitTemplate($options) {
        $path = conf::pathHtdocs() . "/templates/$options[repo]";
        if (!$this->isRepo($path)) {
            common::abort("Template: $options[repo] is not a git repo. Specify installed template (e.g. 'clean') name when commiting");
        }

        $p = new profile();
        $mod = $p->getTemplate($options['repo']);
        $this->commitModuleFromDbValues($mod, 'template');
    }

    /**
     * function for tagging all modules and templates
     * @param   array   options from cli env
     */
    public function gitTagAll() {
        $profile = new profile();
        $version = common::readSingleline('Enter tag version to use ');
        $modules = $profile->getModules();
        foreach ($modules as $key => $val) {

            $tags = git::getTagsModule($val['module_name'], 'module');
            if (in_array($version, $tags)) {
                common::echoStatus('NOTICE', 'y', "Tag already exists local for module '$val[module_name]'.");
            }

            $val['new_version'] = $version;
            $this->gitTagModule($val, 'module');
        }

        $templates = $profile->getTemplates();
        foreach ($templates as $key => $val) {

            $tags = git::getTagsModule($val['module_name'], 'template');
            if (in_array($version, $tags)) {
                common::echoStatus('NOTICE', 'y', "Tag already exists local for template '$val[module_name]'");
            }

            $val['new_version'] = $version;
            $this->gitTagModule($val, 'template');
        }
    }

    public function gitCompare() {
        $profile = new profile();
        //$version = common::readSingleline('Enter tag version to use ');
        $modules = $profile->getModules();
        foreach ($modules as $key => $val) {

            $tags = git::getTagsModule($val['module_name'], 'module');
            $latest = array_values(array_slice($tags, -1))[0];

            common::execCommand("cd ./modules/$val[module_name] && git diff $latest --raw");
        }

        $templates = $profile->getTemplates();
        foreach ($templates as $key => $val) {

            $tags = git::getTagsModule($val['module_name'], 'template');
            $latest = array_values(array_slice($tags, -1))[0];

            common::execCommand("cd ./htdocs/templates/$val[module_name] && git diff $latest --raw");
        }
    }

    /**
     * function for tagging a module or all modules
     * @param array $val
     * @param string $typ (template or module)
     * @return type 
     */
    public function gitTagModule($val, $type = 'module') {
        $repo_path = $this->getRepoPath($val['module_name'], $type);

        if (!$this->isRepo($repo_path)) {
            common::echoMessage("$repo_path is not a git repo");
            return;
        }

        if (empty($val['private_clone_url'])) {
            common::echoMessage("No private clone url is set in install.inc of $val[module_name]");
            return;
        }

        if (!common::readlineConfirm("You are about to tag module: $val[module_name]. Continue?")) {
            return;
        }

        $git_command = "cd $repo_path && git add . && git commit -m \"$val[new_version]\" && git push $val[private_clone_url]";
        proc_close(proc_open($git_command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));

        $git_command = "cd $repo_path && git tag -a \"$val[new_version]\" -m \"$val[new_version]\"";
        proc_close(proc_open($git_command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));

        $git_command = "cd $repo_path && git push --tags $val[private_clone_url]";
        passthru($git_command);

        echo "\n---\n";
    }

    /**
     * function for commiting a module
     *
     * @param array     with module options
     * @param string    module, templatee or profile.
     */
    public function commitModuleFromDbValues($val, $type = 'module') {
        $repo_path = $this->getRepoPath($val['module_name'], $type);

        if (!$this->isRepo($repo_path)) {
            common::echoMessage("$repo_path is not a git repo or is not installed");
            return;
        }

        if (empty($val['private_clone_url'])) {
            common::echoMessage("No private clone url is set in install.inc of $val[module_name]");
            return;
        }

        if (!common::readlineConfirm("You are about to commit module: $val[module_name]. Continue?")) {
            return;
        }


        common::echoStatus('COMMIT', 'g', "Module: '$val[module_name]'");

        $git_add = "cd $repo_path && git add . ";
        common::execCommand($git_add);

        $git_command = "cd $repo_path && git commit ";
        proc_close(proc_open($git_command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));

        $git_push = "cd $repo_path && git push $val[private_clone_url]";
        //passthru($git_command);
        common::execCommand($git_push);
        echo PHP_EOL;
    }

    /**
     * function for upgrading a module, template or profile according to latest tag
     * or master
     *
     * @param array     with module options
     * @param string    tag with wersion or 'master'
     * @param string    module, templatee or profile. 
     */
    public function upgradeFromArray($val, $tag = 'master', $type = 'module') {

        if (!isset($val['module_name'])) {
            $val['module_name'] = git::getModulenameFromRepo($val['repo']);
        }

        $repo_path = $this->getRepoPath($val['module_name'], $type);
        if (!$this->isRepo($repo_path)) {
            common::echoMessage("$repo_path is not a git repo. Can not upgrade");
            return;
        }

        $git_command = "cd $repo_path && ";
        $git_command .= "git checkout master && ";
        $git_command .= "git pull && git fetch --tags && ";
        $git_command .= "git checkout $tag";

        common::execCommand($git_command);

        if ($type == 'module') {
            // It is called with a different name in the upgrade_module
            // function ...
            $val['module'] = $val['module_name'];

            // upgrade to latest set in $_INSTALL['VERSION']
            $val['version'] = null;
            
            $m = new module();
            $m->upgradeModule($val['module_name'], $val['version']);
        }

        // templates have no registry - they are tag based only in version
    }

    public function isRepo($path) {
        $repo = $path . "/.git";
        if (!file_exists($repo)) {
            return false;
        }
        return true;
    }

    public function silence() {
        common::readlineConfirm(null, 1);
    }

}
