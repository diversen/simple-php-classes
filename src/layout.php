<?php

namespace diversen;

use diversen\moduleloader;
use diversen\conf;
use diversen\template;
use diversen\session;
use diversen\db\q;
use diversen\db;
use diversen\file;
use diversen\lang;
use diversen\uri;
use diversen\view;
use diversen\html;

use modules\blocks\module as blocks;

/**
 * File contains contains class for creating layout, which means menus and
 * blocks. It operates closely with template. It loads template. In normal
 * loading mode it is almost only used in head.
 *
 * @package    layout
 */

/**
 * class for creating layout, which means menus and blocks
 * class also loads template in its constructor.
 *
 * @package    layout
 */
class layout {

    public static $current = array ();
    /**
     * var holding menus
     * @var array $menu
     */
    public static $menu = array();

    /**
     * var holding all blocks content. 
     * @var array $blocksContent  
     */
    public static $blocksContent = array();

    /**
     * construtor method
     * checks for a admin template. 
     * loads a template from _COS_HTODCS . '/templates'
     * loads a template's common file conf::pathHtdocs() . /templates/template/common.inc
     * @param string $template
     */
    public function __construct($template = null){
        
        if (!isset($template)) {
            $template = self::getTemplateName();
        }
        
        template::init($template);
        template::loadTemplateIniAssets();
        self::includeTemplateCommon($template);
        
        self::$menu['module'] = array ();
        self::$menu['sub'] = array ();
        self::$menu['main'] = array ();
        
    }
    
    public static function includeTemplateCommon($template = null) {
        if (!$template) { 
            return;
        }
        // load template. This is done before parsing the modules. Then the 
        // modules still can effect the template. Set header, css, js etc. 
        $template_path = conf::pathHtdocs(). "/templates/" . $template . "/template.php";
        if (file_exists($template_path)) {
            include_once $template_path;
        }
        
    }
    
    /**
     * gets template name, the folder where the template is located
     * @return string $template
     */
    public static function getTemplateName () {
        // check is a admin template is being used. 
        if (session::isAdmin() && isset(conf::$vars['coscms_main']['admin_template'])){
            $template = conf::$vars['coscms_main']['admin_template'];
        }
        
        if (!isset($template)) {
            $template = conf::getMainIni('template');
        }
        
        return $template;
    }
    
    /**
     * @ignore
     */
    public static function setLayout ($template) {
        $layout = null;
        $layout = new self($template);
    }
    
    /**
     * method for loading menus. All Main menu entries is generated from
     * database, while all module or submodule menus are generated from
     * files (menu.inc).
     * 
     */
    public function loadMenus(){
        $num = uri::getInstance()->numFragments();

        // always a module menu in web mode
        self::$menu['main'] = 
                q::select('menus')->
                filter('parent =', 0)->condition('AND')->
                filter('admin_only =', 0)->
                order('weight')->
                fetch();

        // admin item are special
        if (session::isAdmin()){
            self::$menu['admin'] = 
                    q::select('menus')->filter('admin_only =', 1)->condition('OR')->
                    filter('section !=', '')->
                    order('weight')->
                    fetch();

        }

        self::setMainMenuTitles();
        
        // if status is set we don't load module menus. Must be 404 or 403.
        // we then return empty array. module loader will know what to do when
        // including correct error pages. No menus from normal main module 
        // should then be loaded. 
        if (!empty(moduleloader::$status)){
            return array();
        }

        // get base menu from file. Base menu is always loaded if found.
        // we decide this from num fragments in uri. 
        $module = uri::getInstance()->fragment(0);
        
        // if no module it must be 'frontpage_module' set
        // in configuration
        if (!$module) {
            $module = conf::getMainIni('frontpage_module');
            $menu = self::getBaseModuleMenu($module);
            self::$menu['module'] = array_merge(self::$menu['module'], $menu);
            return;
        }
        
        // main module, e.g content
        if ($num >= 2){           
            $menu = self::getBaseModuleMenu($module);
            self::$menu['module'] = array_merge(self::$menu['module'], $menu);
        }

        // sub module e.g. content/article
        if ($num > 2){
            $sub = uri::getInstance()->fragment(0). '/' . 
                   uri::getInstance()->fragment(1);
            self::$menu['sub'] = self::getSubMenu($sub);
        }

    }
    
    /**
     * sets a module menu from module name
     * implemented when I needed to use a parent module's menu in a 
     * sub module. 
     * @param string $module
     */
    public static function setModuleMenu ($module) {
        if (moduleloader::moduleExists($module)) {
            moduleloader::includeModule($module);
        } else {
            return;
        }
        
        $menu = self::getMenuFromFile($module);
        self::$menu['module'] = array_merge(self::$menu['module'], $menu);
    }
    
    /**
     * @deprecated
     * @param type $parent
     * @param type $running
     */
    public static function setParentModuleMenu ($parent, $running = null) {
        if (!$running) {
            $running = moduleloader::$running;
        }
        self::setCurrentModuleMenu($running, $parent);
        self::setModuleMenu($parent);
    }
    
    /**
     * sets the self::$current array with an entry
     * Then we can set menu items and set the class 'current'
     * @param string $current the current module
     * @param string $module the module menu which should be set
     */
    public static function setCurrentModuleMenu ($key, $module) {
        self::$current[$key] = $module;
    }
    
    /**
     * Sets a module main and sub menu from class path. 
     * @param string $path e.g. blog or content/article
     */
    public static function setMenuFromClassPath ($path) {
        $path = str_replace('_', '/', $path);
        $ary = explode("/", $path);
        
        moduleloader::includeModule($ary[0]);
        if (count($ary) == 1) {
            $menu = self::getBaseModuleMenu($ary[0]);
            self::$menu['module'] = $menu;
        }
        
        if (count($ary) == 2) {
            $menu = self::getBaseModuleMenu($ary[0]);
            self::$menu['module'] = $menu;
            self::$menu['sub'] = self::getSubMenu($path);
        }
        
    }
    
    /**
     * 
     */
    public static function disableMainModuleMenu () {
        unset(self::$menu['module']);
    }
    
    /**
     * translate all menu items. 
     * With main menu items we look for human translation.
     */
    public static function setMainMenuTitles () {
        foreach (self::$menu['main'] as &$val) {
            $val['title'] = lang::translate($val['title']);
            if (!empty($val['title_human'])) { 
                $val['title'] = $val['title_human'];
            }
        }
        
        if (session::isAdmin()) {
            foreach (self::$menu['admin'] as &$val) {
                $val['title'] = lang::translate($val['title']);
            }    
        }
    }

    /**
     * init blocks and parse blocks. 
     * blocks which should be inited are set in main config/config.ini
     * and looks like this: 
     * 
     * blocks_all = 'blocks,blocks_sec,blocks_top'
     * 
     * Above ini setting means that we use three block sections called:
     *     blocks, blocks_sec and blocks_top. 
     * These names we then can use in our template, and display the blocks 
     * using these names. 
     */
    public static function initBlocks () {
        
        $blocks = conf::getMainIni('blocks_all');
        if (!isset($blocks)) { 
            return;
        }
        
        $blocks = explode(',', $blocks);
        foreach ($blocks as $val) {
            self::$blocksContent[$val] = self::parseBlock($val);
        }        
    }
    
    /**
     * returns a array of all templates found in template_dir
     * @return array $templates
     */
    public static function getAllTemplates () {
        return file::getFileList(conf::pathHtdocs() . "/templates", array ('dir_only' => true));
    }
    
    /**
     * return a whole block which can be used in templates. 
     * e.g. blocks_top will return all blocks in section blocks_top
     * @param type $block
     * @return type 
     */
    public static function getBlock ($block) {
        if (isset(self::$blocksContent[$block])){
            return self::$blocksContent[$block];
        }
        return array();
    }
    
    /**
     * method for getting child menus to a module. 
     * @param  string   $module
     * @return array    children menus items
     */
    public static function getChildrenMenus ($module){
        
        $db = new db();
        $children = $db->selectAll('menus', null, array('parent' => $module));
        
        foreach ($children as $key => $val) {
            $children[$key]['title'] = lang::translate($val['title']);
        }
        
        return $children;
    }

    /**
     * reads a module menu from a file. 
     * @param  string   $module e.g. content or content/article
     * @return array    $menu as array
     */
    public static function getMenuFromFile ($module){
        $module_menu = conf::pathModules() . '/' . $module . '/menu.inc';

        if (file_exists($module_menu)){
            include $module_menu;
        }

        if (isset($_MODULE_MENU)){
            return $_MODULE_MENU;
        }

        if (isset($_SUB_MODULE_MENU)){
            return $_SUB_MODULE_MENU;
        }

        return array();

    }

    /**
     * attach a emnu item to a specified menu
     * @param string $menu (sub, main, module)
     * @param array $item menu item
     */
    public static function attachMenuItem ($menu, $item) {     
        self::$menu[$menu][] = $item;
    }
    
    /**
     * sets specified menu
     * @param string $item (module, sub, main)
     * @param array $menu
     */
    public static function setMenu ($item, $menu) {
        self::$menu[$item] = $menu;
    }

    
    /**
     * gets the base modules menu. 
     * @param   string  module name
     * @return  array   array with top level module menu
     */
    public static function getBaseModuleMenu($module){
        $module_menu = self::getMenuFromFile($module);   
        $children_menu = self::getChildrenMenus($module);
        $module_menu = array_merge($module_menu, $children_menu);  
        return $module_menu;
    }

    /**
     * gets a modules sub menu from file. 
     * @param  string  $sub the name of the module to get menu from
     * @return array $ary the module menu. 
     */
    public static function getSubMenu ($sub) {
        return (self::getMenuFromFile($sub));
    }
    
        
    /**
     * check a menu array and set options['class'] as 'current' 
     * to link creation
     * @param array $menu a menu item
     * @return array $options options to be given to html::createLink
     */
    public static function isActiveModuleMenu ($menu) {

        if ($menu['url'] == $_SERVER['REQUEST_URI']) {
            return true;
        } else {
            return false;
        }
    }
    
    

    
    public static $active ='current';
    
    /**
     * function for parsing MAIN menu list.
     * Main menu is the menu holding all info about modules in database.
     * Therefore it is also some sort of top level module menu.
     */
    public static function parseMainMenuList (){

        
        $menus = array();
        $menus = self::$menu['main'];
        $str = '';
        foreach($menus as $menu){
            if (!self::checkMenuAuth($menu)) {
                continue;
            }
            $str.= static::getMainMenuLink($menu);             
        }
        return $str;
    }
    
    /**
     * Get a main menu link <li>...</li>
     * @param array $menu
     * @return string $str html link
     */
    public static function getMainMenuLink ($menu) {
        $options = array();
        $str = '';
        if (static::isActiveMainMenu($menu)) {
            $options['class'] = static::$active;
        }

        $str.= "<li>";
        $str.= html::createLink($menu['url'], $menu['title'], $options);
        $str.= "</li>\n";
        return $str;
    }
    
    public static function isActiveMainMenu($v) {

        $module_frag = uri::$info['module_base'];
        $url = explode('/', $v['url']);
        if (isset($url[1]) && isset($module_frag)) {
            if ("/$url[1]" == $module_frag) {
                return true;
            }
        }
        return false;
    }

    /**
     * method for getting main module menu as html
     *
     * @return string containing menu module menu as html
     */
    public static function getMainMenu(){

        $str = '';
        $str.= '<ul>' . "\n";
        $str.= self::parseMainMenuList();
        $str.= "</ul>\n";
        return $str;
    }
    

    /**
     * function for parsing Admin menu list.
     * Admin menu is the menu holding all info which is only accessed
     * by super or admin. 
     * 
     * Therefore it is also some sort of top level admin menu.
     * @param array $options
     * @return string $admin_menu as a str li ... li
     */
    public static function parseAdminMenuList ($options = array()){

        
        if (isset($options['menu'])) {
            $menu = $options['menu'];
        } else {
            $menu = self::$menu['admin'];
        }
        
        $str = '';
        foreach($menu as $v){
            if (!self::checkMenuAuth($v)) {
                continue;
            }

            $str.= self::getModuleLink($v);
        }
        return $str;

    }




    /**
     * method for parsing a module menu.
     * A module menu is a menu connected to a main menu item.
     *
     * @param array $menu array to parse
     * @param array $options
     * @return string $str containing menu in html form, an ul with li elements
     */
    public static function parseModuleMenu($menu){
        
        $str = '';
        foreach($menu as $v){         
            if (!self::checkMenuAuth($v)) {
                continue;
            }
            $str.= static::getModuleLink($v);
        }

        return $str;
    }
    
    public static function getModuleLink($menu) {
        $options = array();
        $str = '';
        if (self::isActiveModuleMenu($menu)) {
            $options['class'] = 'current';
        }

        $str.= "<li>";
        $str.= html::createLink($menu['url'], $menu['title'], $options);
        $str.= "</li>\n";
        return  $str;
    }

    /**
     * checks if menu should be displayed to the user depending 
     * on the users credentials
     * @param array $item menu item
     * @return boolean $res true if we display and false if we don't
     */
    public static function checkMenuAuth ($item = array ()) {
        if ( !empty($item['auth'])){
            
            // anon
            if ($item['auth'] == 'anon') {
                return true;
            }
            
            // anon_only
            if ($item['auth'] == 'anon_only') {
                if (session::isUser()) {
                    return false;
                } else {
                    return true;
                }
            }
            
            // if set we need at least a user
            if (!session::isUser()) { 
                return false;
            }
            // if admin is set we need admin
            if (!session::isAdmin() && $item['auth'] == 'admin') { 
                return false;
            }
            // we need super
            if (!session::isSuper()  && $item['auth'] == 'super') { 
                return false;
            }
            return true;
        }
        return true;
    }
    
    
    /**
     * method for adding extra menu items to a menu
     * @param array $items menu items to add.  
     */
    public static function setModuleMenuExtra($items) {
        self::$menu['extra'] = $items;
    }
    
    /**
     * attach $menu['extra'] to a menu
     * @return string $str the html menu 
     */
    static function parseModuleMenuExtra () {
        $str = '';              
        $num_items = $ex = count(self::$menu['extra']);

        foreach(self::$menu['extra'] as $v){         
            $str.= "<li>";
            if ($num_items && ($num_items != $ex) ){
                $str .= MENU_SUB_SEPARATOR;
            }
            $num_items--;       
            $str .= $v;
            $str.= "</li>\n";
        }
        return "<ul>\n$str</ul>\n";
    }
    
    public static function attachMenus ($type, $menu) {
        if ($type == 'module') {
            self::$menu['module']+=$menu;
        }
        
        if ($type == 'sub') {
            self::$menu['sub']+=$menu;
        }
    }

    /**
     * method for getting module menu as html
     * We just parse module menu and sub (if any) and extra (if any)
     * and return it as html.
     *
     * @return  string  containing menu as html
     */
    public static function getModuleMenu() {
        $str = '';
        if (!empty(self::$menu['module'])){            
            $str.= static::parseModuleMenu(self::$menu['module'], 'module');
        }
        return $str;
    }
    
    public static function getModuleMenuSub() {
        $str = '';
        if (!empty(self::$menu['sub'])){
            $str.= static::parseModuleMenu(self::$menu['sub'], 'sub');
        }
        return $str;
    }
    
    public static function getModuleMenuExtra() {
        $str = '';
        if (isset(self::$menu['str'])) {
            $str.=static::$menu['str'];
        }
        return $str;
    }
    
    public static function setStrAfterMenu($str) {
        self::$menu['str'] = "<li>" . $str . "</li>";
    }

    
    /**
     * method for getting all parsed blocks
     * @todo clearify what is going on
     * @param string $block
     * @return array blocks containing strings with html to display
     */
    public static function parseBlock($block){

        $blocks = array();
        if (isset(conf::$vars['coscms_main'][$block],conf::$vars['coscms_main']['module'][$block])){
            $blocks = array_merge(conf::$vars['coscms_main'][$block], conf::$vars['coscms_main']['module'][$block]);
        } else if (isset(conf::$vars['coscms_main'][$block])) {
            $blocks = conf::$vars['coscms_main'][$block];
        } else if (isset(conf::$vars['coscms_main']['module'][$block])){
            $blocks = conf::$vars['coscms_main']['module'][$block];
        } else {
            return $blocks;
        }

        $ret_blocks = array();
        foreach ($blocks as $val) {
            
            // numeric is custom block added to database
            if (is_numeric($val)) {
                moduleloader::includeModule('blocks');
                $row = blocks::getOne($val); 
                $row['content_block'] = moduleloader::getFilteredContent(
                    conf::getModuleIni('blocks_filters'), $row['content_block']
                );
                $row['title'] = htmlspecialchars($row['title']);
                $content = view::get('blocks', 'block_html', $row);
                $ret_blocks[] = $content;
                continue;
            }
            
            if ($val == 'module_menu'){
                $ret_blocks[] = self::getMainMenu();
                continue; 
            }
            $func = explode('/', $val);
            $num = count($func) -1;
            $func = explode ('.', $func[$num]);
            $func = 'block_' . $func[0];
            $path_to_function = conf::pathModules() . "/$val";
            include_once $path_to_function;
            ob_start();
            $ret = $func();
            if ($ret) {
                $ret_blocks[] = $ret; 
            } else {
                $ret_blocks[] = ob_get_contents();
                ob_end_clean();
            }
        }
        return $ret_blocks;
    }
    
    /**
     * generates a standard menu list from an array
     * @param array $options
     * @return string $str
     */
    public static function parseMenuArray ($options) {
        
        $str = '';
        $i = count($options);
        foreach ($options as $option) {
            $i--;
            
            // if option is a stand menu item
            if (is_array($option)) {
                $add = self::parseMenuLinkFromArray($option);
                if (!$add) {
                    continue;
                } else {
                    $str.=$add;
                }
            } else {
                $str.=$option;
            }
            if ($i) {
                $str.= MENU_SUB_SEPARATOR;
            }
        }
        return $str;
    }
    
    /**
     * transforms a menu array into a menu link
     * @param array $menu
     * @return string $str
     */
    public static function parseMenuLinkFromArray ($menu) {
        if (!isset($menu['extra'])) {
            $menu['extra'] = array ();
        }
        if (isset($menu['auth']) && !empty($menu['auth'])) {
            if (!session::checkAccessClean($menu['auth'])) {
                return false;
            }
            return html::createLink($menu['url'], $menu['title'], $menu['extra']);
        } else {
            return html::createLink($menu['url'], $menu['title'], $menu['extra']);
        }
    }
}
