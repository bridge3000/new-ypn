<?php
namespace Model\Manager;
use MainConfig;
use Model\Core\UclGroup;

class UclGroupManager extends DataManager
{
    public $table = "uclgroups";
    
    public function resetUclGroup()
    {
		$this->query('DELETE FROM '.MainConfig::PREFIX . $this->table);
		$fields = array();
        DBManager::getInstance()->copyTable(MainConfig::PREFIX . 'bakuclgroups', MainConfig::PREFIX . $this->table, $fields);
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
	
	public function getThirdTeamIds()
	{
		$groups = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h');
		$uclGroupTeams = $this->find('all', array('order' => array('score'=>'desc')));
		$alTeamIds = array();
		foreach($uclGroupTeams as $u)
		{
			$alTeamIds[$u['GroupName']][] = $u['team_id']; //按组为键，把teamid存入数组
		}
		
		$thirdTeams = array();
		foreach($groups as $k)
		{
			$thirdTeams[] = $alTeamIds[$k][2];
		}
		return $thirdTeams;
	}
}