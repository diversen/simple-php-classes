<?php

namespace diversen;

include_once "rb.php";

use R;

class queue {
    
    /**
     * Connect to database with a connection
     * @param type $dsn
     */
    public function connect ($dsn) {
        R::setup($dsn);
    }
    
    public function add($name, $unique, $object) {
        
    }
    
    public function delete () {
        
    }
    
    /**
     * Get all jobs in queue, which needs to be executed
     */
    public function getQueue ($name) {
        
    }
    
    
}