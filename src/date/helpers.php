<?php

namespace diversen\date;
use diversen\html;
use diversen\date;


/**
 * Class contains helper methods connected to date
 * @package main
 */
class helpers {

    /**
     * Return last 12 months in format: yyyy-mm-dd
     * 
     * @param string $date e.g. '2012-12-30'
     * @return array $ary the last 12 months and years as integers arranged in 
     *                    an assoc array with values like this: ('year' => 2013, 'month' => 7)
     */
    public static function last12Months ($date) {
        if (!$date) {
            $year = date::getCurrentYear();
            $month = (int)date::getCurrentMonth();
        } else {
            $ary = explode('-', $date);
            $year = $ary[0];
            $month = $ary[1];
        }
   
        $ary = array ();
        $ary[] = array ('year' => $year, 'month' => $month);
        $i = 11;
                
        $next = $month;
        while ($i) {
            if ($next == 1) {
                $next = 12;
                $year--;
            } else {
                $next--;
            }
            $ary[] = array ('month' => $next, 'year' => $year);
            $i--;
        }
        
        return $ary;
    }
    
    /**
     * Returns a form dropdown element with 12 months
     * 
     * @param string $name name of form element
     * @param int $selected the selected month
     * @return string $html the clean html select element
     */
    public static function monthDropdown ($name ='month', $selected = null) {
        for ($i= 1; $i <= 12; $i++) {
            $months[$i] = array (
                'id' => $i,
                 'value' =>  date::getMonthName($i)
            );
        }
        
        return html::selectClean(
            $name, $months, 'value', 'id', $selected);
    }
    
    /**
     * Returns a dropdown with he last 12 months. 
     * 
     * @param string $name name of form element
     * @param int $selected the selected month
     * @return string $html the clean html select element
     */
    public static function monthOffsetDropdown ($name ='month', $start = null, $selected = null, $extra = array ()) {
        
        if (!$start) { 
            $start = date::getCurrentYear() . '-' . date::getCurrentMonth ();
        } else {
            $ary = explode('-', $start);
            $start = $ary[0] . '-' . $ary[1];
        }
        if (!$selected) { 
            $selected = date::getCurrentYear () . '-' . date::getCurrentMonth ();
        } else {
            $ary = explode('-', $selected);
            $selected = $ary[0] . '-' . $ary[1];
        }
        
        $months = self::last12Months($start);  
        
        foreach ($months as $key => $val) {
            $months[$key] = array (
                'id' => $val['year'] . '-' . $val['month'],
                'value' =>  date::getMonthName($val['month'])
            );
        }
        
        return html::selectClean(
            $name, $months, 'value', 'id', $selected, $extra);
    }

    
    /**
     * Add or subtract days from timestamp (SQL like)
     * 
     * @param int $days e.g. 10 or -10
     * @param string $from e.g. 2013-10-10. Defaults to 'now'
     * @return string $stamp e.g. 2013-10-20
     */
    public static function daysToTimestamp ($days, $from = null) {
        
        if (!$from) { 
            $from = date::getDateNow ();
        }
        
        $date = strtotime("$from $days days");
        $date = date("Y-m-d", $date );
        return $date;
    }
}
