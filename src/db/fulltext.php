<?php

namespace diversen\db;

use diversen\db\connect;
use PDO;

class fulltext extends connect {
    
    
    public $modifier = 'IN BOOLEAN MODE';
    /**
     * Method for doing a simple full-text mysql search in a database table
     *
     * @param   string  $table the table to search e.g. 'article'
     * @param   string  $match what to match, e.g 'title, content'
     * @param   string  $search what to search for e.g 'some search words'
     * @param   string  $select what to select e.g. '*'
     * @param   int     $from where to start getting the results
     * @param   int     $limit how many results to fetch e.g. 20
     * @return  array   $rows array of rows
     */
    public function simpleSearch($table, $match, $search, $select, $from, $limit ){
        // $search = self::quote($search);
        
        // WITH QUERY EXPANSION
        // IN BOOLEAN MODE
        
        
        $search = self::quote($search);
        
        $query = "SELECT ";
        if (empty($select)){
            $select = '*';
        }
        $query.= "$select, ";
        $query.= "MATCH ($match) ";
        $query.= "AGAINST ($search $this->modifier) AS score ";
        $query.= "FROM $table ";
        $query.= "WHERE MATCH ($match) AGAINST ($search $this->modifier) ";
        $query.= "ORDER BY score DESC ";
        $query.= "LIMIT $from, $limit";
        self::$debug[]  = "Trying to prepare simpleSearch sql: $query";
        try {
            $stmt = self::$dbh->prepare($query);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            self::fatalError ($e->getMessage());
        }
        return $rows;
    }

    /**
     * Method for counting rows when searching a mysql table with full-text
     *
     * @param   string  $table the table to search e.g. 'article'
     * @param   string  $match what to match, e.g 'title, content'
     * @param   string  $search what to search for e.g 'some search words'
     * @return  int     $num rows of search results from used full-text search
     */
    public function simpleSearchCount($table, $match, $search ){
        $search = self::quote($search);
        $query = "SELECT COUNT(*) AS num_rows ";
        $query.= "FROM $table ";
        $query.= "WHERE MATCH ($match) AGAINST ($search $this->modifier) ";
        self::$debug[] = "Trying to prepare simpleSearchCount sql: $query in ";
        $stmt = self::$dbh->prepare($query);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($row as $key => $val){
            return $val['num_rows'];
        }
    }
}
