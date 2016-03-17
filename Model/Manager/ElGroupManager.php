<?php
namespace Model\Manager;
use MainConfig;
use Model\Manager\CoachManager;
use Model\Core\ElGroup;

class ElGroupManager extends DataManager
{
    public $table = "elgroups";
    
    public function resetElGroup()
    {
        $allUclgroupArray = $this->query('select * from ' . MainConfig::PREFIX . 'elgroups');
        $this->saveMany($this->loadData($allUclgroupArray), 'insert');
    }
}

?>