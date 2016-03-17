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
        $allUclgroupArray = $this->query('select * from ' . MainConfig::PREFIX . 'uclgroups');
        $this->saveMany($this->loadData($allUclgroupArray), 'insert');
    }
}

?>
