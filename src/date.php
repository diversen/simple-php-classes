<?php

namespace diversen;

/**
 * Class contains common function for doing date math with mainly SQL timestamps
 * @package date
 */
class date {
    
    /**
     * Checks if a date is in a range between start and end date
     * Start and end date are included
     * @param string $start_date timestamp
     * @param string $end_date timestamp
     * @param string $date_from_user timestamp
     * @return boolean $res true if in range else false
     */
    public static function inRange ($start_date, $end_date, $date_from_user) {
      $start_ts = strtotime($start_date);
      $end_ts = strtotime($end_date);
      $user_ts = strtotime($date_from_user);
      return (($user_ts >= $start_ts) && ($user_ts <= $end_ts));
    }

    /**
     * Gets a timestamp's day as 'weekday' from SQL timestamp
     * @param string  $stamp timestamp to be used with strtotime
     * @return string $weekday
     */
    public static function timestampToLocaleDay ($stamp) {
        $time = strtotime($stamp);
        $datearray = getdate($time);
        return $datearray["weekday"];
    }

    /**
     * Gets diff between two SQL dates as an int
     * @param string $from date to be parsed with strtotime
     * @param string $to date
     * @return int $days
     */
    public static function getDatesTimeDiff ($from, $to) {
        $from_t = strtotime($from);
        $to_t = strtotime($to);
        $diff = $to_t - $from_t;
        return floor($diff/(60*60*24));
    }

    /**
     * Get a month name from month as int
     * @param int $month_int
     * @param string $format
     * @return string $month_name 
     * 
     */
    public static function getMonthName($month_int, $format = 'F') {
        $month_int = (int)$month_int;
        $timestamp = mktime(0, 0, 0, $month_int);
        return strftime('%B', $timestamp);
    }

    /**
     * Get current year
     * @return int $year the current year
     */
    public static function getCurrentYear () {
        return strftime("%Y");
    }
    
    /**
     * Get current month
     * @return int $month the current month
     */
    public static function getCurrentMonth () {
        return strftime("%m");
    }

    /**
     * Get all dates, as array, between two timestamp dates
     * Excludes last day
     * @param string $from date
     * @param string $to date
     * @return array $dates
     */
    public static function getDatesAry ($from, $to) {
        $ary = array();    
        $i = self::getDatesTimeDiff($from, $to);
        $c = $i - 1;
        while ($c) {
            $date = strtotime("$from +$c days");
            $date = date("Y-m-d", $date );
            $ary[] = $date;
            $i--;
            $c--;
        }

        $ary[] = $from;
        return array_reverse($ary);
    }

    /**
     * Get currenct date as  timestamp
     * @param array $options, if we need hms then set hms => 1
     * @param int $unix_stamp
     * @return string $date
     */
    public static function getDateNow ($options = array (), $unix_stamp = null) {
        if (isset($options['hms'])) {
            $format = 'Y-m-d G:i:s';
        } else {
            $format = 'Y-m-d';
        }
        
        // for old times sake
        if (isset($options['timestamp'])) {
            $unix_stamp = $options['timestamp'];
        } else {
            $unix_stamp = null;
        }
        
        if ($unix_stamp) {
            $date = date($format, $unix_stamp);
        } else {
            $date = date($format );
        }
        return $date;
    }


    /**
     * Add days to a timestamp
     * @param string $from start date
     * @param int $days days to add
     * @return string $date timestamp 
     */
    public static function addDaysToTimestamp ($from, $days) {
        $date = strtotime("$from +$days days");
        $date = date("Y-m-d", $date );
        return $date;
    }

    /**
     * Subtract days from a timestamp
     * @param string $from date
     * @param int $days to subtract
     * @return string $date 
     */
    public static function substractDaysFromTimestamp ($from, $days) {
        $date = strtotime("$from -$days days");
        $date = date("Y-m-d", $date );
        return $date;
    }

    /**
     * Checks if a timestamp date is valid
     * @param string $date
     * @return boolean $res
     */
    public static function isValid ($date) {
        if (!is_string($date)) { 
            return false;
        }
        $ary = explode('-', $date);
        if (count($ary) == 3) {
            return checkdate (  (int)$ary[1] , (int)$ary[2] , (int)$ary[0]);
        }
        return false;
    }

    /**
     * Get week as number from a strtotime date
     * @param string $date (parsed by strtotime)
     * @return string $week 'W' 
     */
    public static function getWeekNumber ($date) {
        $week = date('W', strtotime($date));
        return $week;
    }

    /**
     * Get date as a day number 'N' from a timestamp
     * @param string $date (parsed by strtotime)
     * @return int $weekday 1-7 'N'
     */
    public static function getDayNumber ($date) {
        $weekday = date('N', strtotime($date)); // 1-7
        return $weekday;    
    }

    /**
     * Get day from date as string 'D' from a strtotime date
     * @param string $date (parsed by strtotime)
     * @return string $weekday 'D'
     */
    public static function getDayStr($date) {
        $weekday = strtoupper(date('D', strtotime($date))); // 1-7
        return $weekday;    
    }

    /**
     * Get a range of weeks between two dates (parsed by strtotime)
     * @param string $from date
     * @param string $to date
     * @return array $weeks array of weeks
     */
    public static function getWeekInRange ($from, $to) {
        $diff = self::getDatesTimeDiff($from, $to);
        $weeks = array (); 
        while($diff) {
            $from = self::addDaysToTimestamp($from, 1) ;
            $week = self::getWeekNumber($from); 
            $weeks[$week] = $week;
            $diff--;
        }
        return $weeks;
    }


    /**
     * Get week from date as array with 'startdate' and 'enddate'
     * @param string $date 
     * @return array $ary ('startdate' => 'date', 'enddate' => 'enddate');
     */
    public static function getWeek($date) {
            $start = strtotime($date) - strftime('%w', strtotime($date)) * 24 * 60 * 60;
            $end = $start + 6 * 24 * 60 * 60;
            return array('start' => strftime('%Y-%m-%d', $start),
                         'end' => strftime('%Y-%m-%d', $end));
    }

    /**
     * Return timestamp as an array with month, day and year
     * @param string $sql timestamp ('2012-10-09');
     * @return array $ary ('year' => 1972, 'month' => 02, 'day' => 1972);
     */
    public static function getAry ($sql) {
        $ary = explode ("-" , $sql);
        $ary['year'] = $ary[0];
        $ary['month'] = $ary[1];
        $ary['day'] = $ary[2];
        return $ary;
    }

    // found on
    // http://snippets.dzone.com/posts/show/1310
    /**
     * Gets years old from timestamp
     * @param string $birthday SQL birthday (e.g. 1965-05-21)
     * @return int $years 
     */
    public static function yearsOld ($birthday) {
        list($year,$month,$day) = explode("-",$birthday);
        $year_diff  = date("Y") - $year;
        $month_diff = date("m") - $month;
        $day_diff   = date("d") - $day;
        if ($month_diff < 0) { 
            $year_diff--; 
        } elseif (($month_diff==0) && ($day_diff < 0)) { 
            $year_diff--;
        }
        return $year_diff;
    }
    
    /**
     * Get locale date from a format
     */
    public static function dateGetDateNowLocale($format) {
        if (!$format) {
            $format = '%Y-%m-%d';
        }
        return strftime($format);
    }
}

