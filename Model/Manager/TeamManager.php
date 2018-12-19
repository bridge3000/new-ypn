<?php
namespace Model\Manager;
use MainConfig;
use Model\Core\Team;

class TeamManager extends DataManager
{
    public $table = "teams";

    public function getAll()
    {
        $data = $this->find('all');
        return $data;
    }

    public function getTeams($teamIds)
    {
        $data = $this->find('all', array(
            'conditions' => array('id' => $teamIds)
        ));
        
        $teams = array();
        foreach ($data as $d)
        {
            $newTeam = new Team();
            foreach ($d as $k=>$v)
            {
                $newTeam->$k = $v;
            }
            
            $teams[$newTeam->id] = $newTeam;
        }
        
        return $teams;
    }
    
    public function addOtherLeagueTeamSalary($myTeamId)
    {
		$otherLeagueTeams = $this->find('all', array(
			'conditions' => array('NOT' => array('league_id' => array(1, 3, 100))),
			'fields' => array('id','money','TicketPrice','seats'),
			));
		
		$values = array();
		foreach($otherLeagueTeams as $t)
		{
			$money =  $t['money'] + ($t['TicketPrice'] * $t['seats'] * (50 + mt_rand(1, 50)) / (100 * 10000) );
			$values[] = array('id'=>$t['id'], 'money'=>$money);
		}
		
		$this->update_batch($values);
    }
    
    public function resetTeams()
    {
        $fields = array('website');
        DBManager::getInstance()->copyTable(MainConfig::PREFIX . 'bak_teams', MainConfig::PREFIX . $this->table, $fields);
    }
    
    /**
     * 获得所有computer teams
     */
    public function getAllComputerTeams()
    {
        $records = $this->find('all', array(
            'fields' => array('id', 'money', 'name', 'formattion', 'player_count', 'league_id', 'bills'),
            'conditions' => array('manager_id'=>0, 'league_id<>'=>100),
            'order' => array('league_id' => 'asc'),
            ));
        
		$computerTeams = $this->loadData($records);
        return $computerTeams;
    }
    
    /**
     * 获取有钱的电脑队
     * @return array(Team) $computerTeams
     */
    public function getComputerLeagueTeams()
    {
        $records = $this->find('all', array(
            'fields' => array('id', 'money', 'name', 'formattion', 'league_id', 'manager_id', 'player_count', 'bills', 'TotalSalary'),
            'conditions' => array('manager_id'=>0, 'league_id<>'=>100, 'money>'=>0),
            'order' => array('league_id' => 'asc'),
            ));
        
		$computerTeams = $this->loadData($records);
        return $computerTeams;
    }
	
	public function setAttack($teamId, $attack)
	{
		$data = array(
            'attack' => $attack,
        );
        TeamManager::getInstance()->update($data, array('id'=>$teamId));
	}
	
	public function getAllTeamIds()
	{
		$teams = TeamManager::getInstance()->find('all', array(
			'fields' => array('id'),
			'contain' => array()
			)
		);
		
		$allTeamIds = array();
		for ($i = 0; $i < count($teams); $i++)
		{
			$allTeamIds[] = $teams[$i]['id'];
		}
		return $allTeamIds;
	}
	
	/**
	 * 
	 * @param type $teamId
	 * @param type $dir 1收入 2支出
	 * @param type $money
	 * @param type $content
	 */
	public function changeMoney($teamId, $dir, $money, $nowDate, $content)
	{
		$curTeam = $this->findById($teamId);
		if ($dir == 1)
		{
			$curTeam['money'] += $money;
		}
		else
		{
			$curTeam['money'] -= $money;
		}
		$bills = json_decode($curTeam['bills'], TRUE);
		$bills[] = array('dir' => $dir, 'money' => $money, 'remain' => $curTeam['money'], 'content' => $content, 'date'=>strtotime($nowDate));
		$curTeam['bills'] = json_encode($bills);
		$this->saveModel($curTeam, 'update');
	}
	
	public function addMoneyBatch($teamIds, $money, $msg, $nowDate)
	{
		$successTeamArr = $this->find('all', array(
			'options' => array('id'=>$teamIds),
			'fields' => array('money','bills')
		));
		
		$successTeams = $this->loadData($successTeamArr);
		foreach($successTeams as $t)
		{
			$t->addMoney($money, $msg, $nowDate);
		}

		TeamManager::getInstance()->saveMany($successTeams);
	}
}