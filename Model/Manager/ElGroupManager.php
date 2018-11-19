<?php
namespace Model\Manager;

use MainConfig;

class ElGroupManager extends DataManager
{
    public $table = "elgroups";
    
    public function resetElGroup()
    {
		$this->query('DELETE FROM '.MainConfig::PREFIX . $this->table);
		$fields = array();
        DBManager::getInstance()->copyTable(MainConfig::PREFIX . 'bakelgroups', MainConfig::PREFIX . $this->table, $fields);
    }
	
	public function saveResult($hostTeamId, $guestTeamId, $result)
	{
		if($result == 1)
		{
			$this->update(array("score"=>"score+3","win"=>"win+1"), array('team_id'=>$hostTeamId));
			$this->update(array("lose"=>"lose+1"), array('team_id'=>$guestTeamId));
		}
		else if($result == 2)
		{
			$this->update(array("score"=>"score+3","win"=>"win+1"), array('team_id'=>$guestTeamId));
			$this->update(array("lose"=>"lose+1"), array('team_id'=>$hostTeamId));
		}
		else if($result == 3)
		{
			$this->update(array("score"=>"score+1","draw"=>"draw+1"), array('team_id'=>$hostTeamId));
			$this->update(array("score"=>"score+1","draw"=>"draw+1"), array('team_id'=>$guestTeamId));
		}
	}
}