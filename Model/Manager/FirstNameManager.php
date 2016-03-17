<?php
namespace Model\Manager;

class FirstNameManager extends DataManager 
{
    public $table = "firstnames";
    
    public function getFirstNames()
    {
        $records = $this->find('all', array(
            'fields' => array('title'),
            'limit' => 60
        ));
        return $records;
    }
}
