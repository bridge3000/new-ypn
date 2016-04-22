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
	
	public function saveResult($hostTeamId, $guestTeamId, $result)
	{
		if($result == 1)
		{
			$this->update(array("score"=>"+3","win"=>"+1"), array('team_id'=>$hostTeamId));
			$this->update(array("lose"=>"+1"), array('team_id'=>$guestTeamId));
		}
		else if($result == 2)
		{
			$this->update(array("score"=>"+3","win"=>"+1"), array('team_id'=>$guestTeamId));
			$this->update(array("lose"=>"+1"), array('team_id'=>$hostTeamId));
		}
		else if($result == 3)
		{
			$this->update(array("score"=>"+1","draw"=>"+1"), array('team_id'=>$hostTeamId));
			$this->update(array("score"=>"+1","draw"=>"+1"), array('team_id'=>$guestTeamId));
		}
	}
}