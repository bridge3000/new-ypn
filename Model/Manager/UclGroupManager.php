<?php
namespace Model\Manager;
use MainConfig;
use Model\Manager\CoachManager;
use Model\Core\UclGroup;

class UclGroupManager extends DataManager
{
    public $table = "uclgroups";
    
    public function resetUclGroup()
    {
		$this->query('delete from '.$this->table);
		$fields = array();
        DBManager::getInstance()->copyTable(MainConfig::PREFIX . 'bak_uclgroups', MainConfig::PREFIX . $this->table, $fields);
    }
	
}