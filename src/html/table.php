<?php

namespace diversen\html;

use diversen\html;

/**
 * File containing class for building tables
 * @package html 
 */

/**
 * Class for building tables
 * @package html
 */
class table {
    
    /**
     * Return <td>val</td>
     * @param string $val
     * @return string $html
     */
    public static function td ($val, $options = array ()) {
        $extra = html::parseExtra($options);
        return "<td $extra>$val</td>";
    }
    
    /**
     * returns <tr>
     * @return string $html <tr> begin
     */
    public static function trBegin ($options = array ()) {
        $extra = html::parseExtra($options);
        return "<tr $extra>\n";
    }
    
    /**
     * return </tr>
     * @return string $html </tr> end 
     */
    public static function trEnd () {
        return "</tr>\n";
    }
    
    /**
     * Return <table> begin
     * @param array $options table options
     * @return string $html <table>
     */
    public static function tableBegin ($options) {
        $extra = html::parseExtra($options);
        return "<table $extra>\n";
    }
    
    /**
     * Return </table>
     * @return string $html </table>
     */
    public static function tableEnd () {
        return "</table>\n";
    }
}
