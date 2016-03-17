<?php
namespace Model\Manager;

class FamilyNameManager extends DataManager 
{
    public $table = "familynames";
    
    public function getFamilyNames()
    {
        $records = $this->find('all', array(
            'fields' => array('title'),
            'limit' => 60
        ));
        return $records;
    }
}