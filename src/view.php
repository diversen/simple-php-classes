<?php

namespace diversen;

use diversen\conf;
use diversen\layout;


/**
 * @description A collection of simple view methods which can be overridden by
 * template views
 */
class view {
   
    /**
     * indicate if we will override a view
     * @var boolean $override
     */
    public static $override = false;
    
    /**
     * var holding options for views
     * @var array $options
     */
    public static $options = array (
        'folder' => 'views'
    );
    
    /**
     * currenct view
     * @var string $view
     */
    public static $view = null;
    

    
    /**
     * Get file name of a view override
     * If a view exists in a template, then the view will override the default view
     * @return string $str filename of the view 
     */
    public static function getOverrideFilename () {
        
        if (isset(self::$options['template'])) {
            $filename = conf::pathHtdocs() . "/template/" . self::$options[template];
        } 
        
        if (isset(self::$options['module'])) {
            $filename = conf::pathModules() . '/' . self::$options['module'];
        }
        
        $filename.= '/' . self::$options['folder'];
        if (isset(self::$options['view'])) {
            $filename.= '/' . self::$options['view'];
        } else {
            $filename.= '/' . self::$view; 
        }
        
        if (isset(self::$options['ext'])) {
            $filename.= '.' . self::$options['view'];
        } else {
            $filename.= '.' . 'inc'; 
        }
        return $filename;
    }
    
    /**
     * Method for including a view file.
     * @param string $module
     * @param string $view
     * @param array  $vars to parse into template
     * @param boolean return as string (1) or output directly (0)
     * @return string|void $str 
     */
    public static function includeModuleView ($module, $view, $vars = null, $return = null){

        if (self::$override) {
            $filename = self::getOverrideFilename();
        } else {
            $filename = conf::pathModules() . "/$module/" . self::$options['folder'] . "/$view.inc";
        }
        
        if (is_file($filename)) {
            ob_start();
            include $filename;
            $contents = ob_get_contents();
            ob_end_clean();
            if ($return) {
                return $contents;
            } else {
                echo $contents;
            }
        } else {
            echo "View: $filename not found";
            return false;
        }
    }
    
    /**
     * Shorthand for includeModuleView. Will always return the parsed template 
     * instead of printing to standard output. 
     * 
     * @param string $module the module to include view from
     * @param string $view the view to use
     * @param mixed $vars the vars to use in the template
     * @return string $parsed the parsed template view  
     */
    public static function get ($module, $view, $vars = null) {
        $view = $view . ".inc";
        return self::getFile($module, $view, $vars);
    }
    
    /**
     * Shorthand for includeModuleView. Will always return the parsed template 
     * instead of printing to standard output. 
     * 
     * @param string $module the module to include view from
     * @param string $view the view to use
     * @param mixed $vars the vars to use in the template
     * @return string $parsed the parsed template view  
     */
    public static function getFile ($module, $view, $vars = null) {
       
        // only template who has set name will be able to override this way
        $template = conf::getMainIni('template');
        if ($template) {
            $override = conf::pathHtdocs() . "/templates/$template/$module/$view";
            if (is_file($override)) {
                return self::getFileView($override, $vars);
            }
        }
        $file = conf::pathModules() . "/$module/views/$view";
        return self::getFileView($file, $vars);
    }
    
    /**
     * Include a set of module function used for a module. These 
     * functions will be overridden in template if they exists in a template
     * @param string $module
     * @param string $file
     * @return void
     */
    public static function includeOverrideFunctions ($module, $file) {

        // Check for view in template
        $template = layout::getTemplateName();
        if ($template) {
            $override = conf::pathHtdocs() . "/templates/$template/$module/$file";
            if (is_file($override)) {
                include_once $override;
                return;
            }
        }
        include_once conf::pathModules() .  "/$module/$file";
    }
    
    
    /**
     * Load a view from file and substitute PHP vars
     * @param string $filename
     * @param mixed $vars
     * @return strin $str 
     */
    public static function getFileView ($filename, $vars = null) {
            ob_start();
            include $filename;
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
    }
}
