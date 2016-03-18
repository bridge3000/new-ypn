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
	
    public function buySomePlayers(&$team, $allTeamUsedNOs, $allCanBuyPlayers)
    {
        /*前锋2名　守门员1名　中后卫2名　左右边卫前后腰1名*/
        $this->flushNow("<br><font color=blue><strong>" . $team['Team']['name'] . "</strong></font>正在转会<br>");

        $needPoses = array(
            array('positionId'=>4, 'minCount'=>3),
            array('positionId'=>3, 'minCount'=>2),
            array('positionId'=>9, 'minCount'=>2),
            array('positionId'=>10, 'minCount'=>2),
            array('positionId'=>13, 'minCount'=>2),
            array('positionId'=>14, 'minCount'=>2),
            array('positionId'=>2, 'minCount'=>2),
        );

        switch ($team['Team']['formattion']) 
        {
            case "4-4-2":
                $needPoses[] = array('positionId'=>1, 'minCount'=>4);
                $needPoses[] = array('positionId'=>8, 'minCount'=>2);
                $needPoses[] = array('positionId'=>3, 'minCount'=>2);
                break;
            case "3-5-2":
                $needPoses[] = array('positionId'=>2, 'minCount'=>2);
                $needPoses[] = array('positionId'=>8, 'minCount'=>2);
                $needPoses[] = array('positionId'=>1, 'minCount'=>4);
                break;
            case "5-3-2":
                $needPoses[] = array('positionId'=>3, 'minCount'=>4);
                $needPoses[] = array('positionId'=>1, 'minCount'=>4);
                break;
            case "3-4-3":
                $needPoses[] = array('positionId'=>2, 'minCount'=>2);
                $needPoses[] = array('positionId'=>5, 'minCount'=>2);
                $needPoses[] = array('positionId'=>6, 'minCount'=>2);
                $needPoses[] = array('positionId'=>7, 'minCount'=>2);
                break;
            case "4-3-3":
                $needPoses[] = array('positionId'=>3, 'minCount'=>2);
                $needPoses[] = array('positionId'=>5, 'minCount'=>2);
                $needPoses[] = array('positionId'=>6, 'minCount'=>2);
                $needPoses[] = array('positionId'=>7, 'minCount'=>2);
                break;
            case "4-5-1":
                $needPoses[] = array('positionId'=>3, 'minCount'=>2);
                $needPoses[] = array('positionId'=>7, 'minCount'=>2);
                $needPoses[] = array('positionId'=>2, 'minCount'=>2);
                $needPoses[] = array('positionId'=>8, 'minCount'=>2);
                break;
            case "圣诞树":
                $needPoses[] = array('positionId'=>3, 'minCount'=>2);
                $needPoses[] = array('positionId'=>8, 'minCount'=>4);
                $needPoses[] = array('positionId'=>7, 'minCount'=>2);
                break;
        }
                
        $myPoses = array();
        for ($i = 0;$i < count($team['PlayerPosition']);$i++)
        {
            if (array_key_exists($team['PlayerPosition'][$i]['position_id'], $myPoses))
            {
                $myPoses[$team['PlayerPosition'][$i]['position_id']] += 1;
            }
            else
            {
                $myPoses[$team['PlayerPosition'][$i]['position_id']] = 1;
            }
        }
        
        if (array_key_exists($team['Team']['id'], $allTeamUsedNOs))
        {
            $usedNOs = $allTeamUsedNOs[$team['Team']['id']];
        }
        else
        {
            $usedNOs = array();
        }

        foreach($needPoses as $np)
        {
            $posCount = 0;
            if (array_key_exists($np['positionId'], $myPoses))
            {
                $posCount = $myPoses[$np['positionId']];
            }
            
            if ($posCount >= $np['minCount']) continue;
            
            $newNO = $this->buySuitablePlayer($team, $np['positionId'], $usedNOs, $allCanBuyPlayers);
            if ($newNO != null)
            {
                $usedNOs[] = $newNO;
//                break; //一个球队每次只买成功一个球员
            }
        }
    }

    /**
     * 购买一名特定位置的球员
     * @param type $team
     * @param type $position_id
     * @param type $minCount
     * @param type $usedNOs
     * @return type
     */
    private function buySuitablePlayer(&$team, $position_id, $usedNOs, $allCanBuyPlayers)
    {
        $newSalary;
        $playerValue;
        $playerNO = 0;
        $News = $this->getModel("News");
        $Player = $this->getModel("Player");

        for ($i = 0;$i < count($this->allCanBuyPlayers);$i++) //traverse allplayers to transfer
        {
            if (!array_key_exists("isChanged", $this->allCanBuyPlayers[$i]))
            {
            	$this->allCanBuyPlayers[$i]['isChanged'] = false;
            }
            
            if (in_array($this->allCanBuyPlayers[$i]['id'], $this->futrueContractPlayers)) continue;
            
            if ( ($this->allCanBuyPlayers[$i]['team_id'] != $team['Team']['id']) && ($this->allCanBuyPlayers[$i]['position_id'] == $position_id) && !$this->allCanBuyPlayers[$i]['isChanged'])
            {
            	if (mt_rand(1, 2) == 1) return;
                
            	$transferCompleteNow = false;
                
                /*free*/
                if ($this->allCanBuyPlayers[$i]["team_id"] == 0)
                {
                	$transferCompleteNow = true;
                    echo("<font color=green><strong>" . $this->allCanBuyPlayers[$i]['name'] . "</strong></font>自由转会去了" . $team['Team']['name'] . "<br>");flush();
                }
                /*normal*/
                else if ( ($team['Team']['money'] > $this->allCanBuyPlayers[$i]['fee']) && $this->allCanBuyPlayers[$i]['isSelling'])
                {
                    $playerFee = $Player->estimateFee($this->allCanBuyPlayers[$i]);
                    $playerValue = $Player->estimateValue($this->allCanBuyPlayers[$i]);
                	if ($playerFee < $this->allCanBuyPlayers[$i]['fee'])
                	{
                		$News->Add1($team['Team']['name'] . "希望通过<font color=red><strong>" . $playerFee . "</strong></font>万欧元的价格买进<font color=blue><strong>" . $this->allCanBuyPlayers[$i]['name'] . "</strong></font>", $this->allCanBuyPlayers[$i]['team_id'], $this->nowDate, $this->allCanBuyPlayers[$i]['ImgSrc']);
                		return;
                	}
                	
					if (mt_rand(1, 2) == 1)
					{
						/*买进球员的队减钱*/
                        $this->writeJournal($team['Team']['id'], 2, $this->allCanBuyPlayers[$i]['fee'], '买进球员' . $this->allCanBuyPlayers[$i]['name']);
                            
						/*卖出球员的队加钱*/
                        $this->writeJournal($this->allCanBuyPlayers[$i]['team_id'], 1, $this->allCanBuyPlayers[$i]['fee'], '卖出球员' . $this->allCanBuyPlayers[$i]['name']);
						
                        $News->Add1("<font color=green><strong>" . $this->allCanBuyPlayers[$i]['name'] . "</strong></font>已经被" . $team['Team']['name'] . "成功引进", $this->allCanBuyPlayers[$i]['team_id'], $this->nowDate, $this->allCanBuyPlayers[$i]['ImgSrc']);
	
                        $transferCompleteNow = true;
                            
                        echo("<font color=green><strong>" . $this->allCanBuyPlayers[$i]['name'] . "</strong></font>以<font color=red><strong>" . $this->allCanBuyPlayers[$i]['fee'] . "</strong></font>万欧元去了" . $team['Team']['name'] . "<br>");flush();
					}
				}
                /*last 6 month，忠诚度小于85的会自由转会*/
                elseif ((date("Y-m-d", strtotime("$this->nowDate + 181 day")) > $this->allCanBuyPlayers[$i]['ContractEnd']) && !$this->allCanBuyPlayers[$i]['isChanged'] && ($this->allCanBuyPlayers[$i]['loyalty'] < 85))
                {
                    $newSalary = $Player->getExpectedSalary($this->allCanBuyPlayers[$i]);
                    $this->query("insert into ypn_future_contracts (player_id, NewContractEnd, NewTeam_id,NewSalary,OldContractEnd) values(" . $this->allCanBuyPlayers[$i]['id'] . ", '" . date("Y", strtotime("$this->nowDate + " . $this->getRandom(1, 6) . " year")) . "-6-30" . "'," . $team['Team']['id'] . "," . $newSalary . ",'" . $this->allCanBuyPlayers[$i]['ContractEnd'] . "')");
                    $this->allCanBuyPlayers[$i]['isChanged'] = true;  
					$info = "<font color=green><strong>" . $this->allCanBuyPlayers[$i]['name'] . "</strong></font>将在6个月内自由转会加盟<font color=blue>" . $team['Team']['name'] . "</font>";
                    $News->Add1($info, $this->allCanBuyPlayers[$i]['team_id'], $this->nowDate, $this->allCanBuyPlayers[$i]['ImgSrc']);
                    echo($info . "<br>");flush();                    
                }
                
                if ($transferCompleteNow)
                {
                    $newSalary = $Player->getExpectedSalary($this->allCanBuyPlayers[$i]);
                    $playerNO = $this->getPlayerNewShirtNo($this->allCanBuyPlayers[$i], $usedNOs);
                
                    if ($this->allCanBuyPlayers[$i]['league_id'] == $team['Team']['league_id'])
                	{
						$this->allCanBuyPlayers[$i]['cooperate'] = 90;
                	}
                	else
                	{
                        $this->allCanBuyPlayers[$i]['cooperate'] = 80;
						$this->allCanBuyPlayers[$i]['league_id'] = $team['Team']['league_id'];
                	}

                	$this->allCanBuyPlayers[$i]['team_id'] = $team['Team']['id'];
                	$this->allCanBuyPlayers[$i]['ClubDepending'] = 85;
                    $this->allCanBuyPlayers[$i]['loyalty'] = 80;
                	$this->allCanBuyPlayers[$i]['salary'] = $newSalary;
                	$this->allCanBuyPlayers[$i]['ShirtNo'] = $playerNO;
                	$this->allCanBuyPlayers[$i]['ContractBegin'] = $this->nowDate;
                	$this->allCanBuyPlayers[$i]['ContractEnd'] = date('Y', strtotime($this->nowDate))+$this->getRandom(1, 5) . "-6-30";
                	$this->allCanBuyPlayers[$i]['isSelling'] = false;
                	$this->allCanBuyPlayers[$i]['isChanged'] = true;
                    return $playerNO;
                }
            }
        }
    }
    
    public function resetAllFormattion(&$allTeams, $formattions)
    {
        for ($i = 0;$i < count($allTeams);$i++)
        {
            $allTeams[$i]['Team']['formattion'] = $formattions[array_rand($formattions)]['Formattion']['title'];
        }
    }
    
    public function addOtherLeagueTeamSalary()
    {
        $rate = (50 + mt_rand(1, 50)) / 100 / 10000;
        
        $this->update(array('money' => 'money+TicketPrice*seats*' . $rate), array('NOT' => array('league_id' => array(1, 3, 100))));
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
            'fields' => array('id', 'money', 'name', 'formattion'),
            'conditions' => array('manager_id'=>0, 'league_id<>'=>100),
            'order' => array('league_id' => 'asc'),
            ));
        
        $newTeam = new Team();
        $computerTeams = array();
        foreach($records as $r)
        {
            $newTeam = clone $newTeam;
            $newTeam->setId($r['id']);
            $newTeam->setMoney($r['money']);
            $newTeam->setName($r['name']);
            $newTeam->setFormattion($r['formattion']); 
            $computerTeams[] = $newTeam;
        }
        
        return $computerTeams;
    }
    
    /**
     * 获取有钱的电脑队
     * @return type
     */
    public function getRichComputerTeams()
    {
        $records = $this->find('all', array(
            'fields' => array('id', 'money', 'name', 'formattion', 'league_id', 'manager_id', 'player_count'),
            'conditions' => array('manager_id'=>0, 'league_id<>'=>100, 'money>'=>0),
            'order' => array('league_id' => 'asc'),
            ));
        
        $newTeam = new Team();
        $computerTeams = array();
        foreach($records as $r)
        {
            $newTeam = clone $newTeam;
            $newTeam->setId($r['id']);
            $newTeam->setMoney($r['money']);
            $newTeam->setName($r['name']);
            $newTeam->setFormattion($r['formattion']); 
            $newTeam->setLeagueId($r['league_id']);
            $newTeam->setManagerId($r['manager_id']);
            $newTeam->setPlayerCount($r['player_count']);
            $computerTeams[] = $newTeam;
        }
        
        return $computerTeams;
    }
    
    public function saveMatchInfo($curTeam)
    {
        $data = array(
            'goals' => $curTeam->goals,
            'lost' => $curTeam->lost,
            'score' => $curTeam->score,
            'win' => $curTeam->win,
            'lose' => $curTeam->lose,
            'draw' => $curTeam->draw,
        );
        TeamManager::getInstance()->update($data, array('id'=>$curTeam->id));
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
}