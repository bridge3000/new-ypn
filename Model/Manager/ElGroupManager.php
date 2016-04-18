<?php
namespace Model\Manager;
use MainConfig;
use Model\Core\ElGroup;

class ElGroupManager extends DataManager
{
    public $table = "elgroups";
    
    public function resetElGroup()
    {
		$this->query('delete from '.$this->table);
		$fields = array();
        DBManager::getInstance()->copyTable(MainConfig::PREFIX . 'bak_elgroups', MainConfig::PREFIX . $this->table, $fields);
    }
}