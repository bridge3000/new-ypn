<?php
namespace Model\Manager;
use MainConfig;
use Model\Core\Player;
use Model\Collection\PlayerCollection;

class PlayerManager extends DataManager 
{
    public $table = "players";
    static $youngPlayers = array();
    
    public function doNormal()
    {
        $sqlArr = array();
        $sqlArr[] = "UPDATE ypn_players SET condition_id=3 WHERE condition_id=5 AND Punish1Count=0 AND Punish2Count=0 AND Punish3Count=0";
		$sqlArr[] = "UPDATE ypn_players SET sinew=sinew+20,YellowTodayCount=0";
		$sqlArr[] = "UPDATE ypn_players SET sinew=SinewMax WHERE sinew>SinewMax";
		$sqlArr[] = "UPDATE ypn_players SET state=100 WHERE state>100";
		$sqlArr[] = "UPDATE ypn_players SET InjuredDay=InjuredDay-1 WHERE InjuredDay>0";
		$sqlArr[] = "UPDATE ypn_players SET condition_id=3 WHERE InjuredDay=0 AND condition_id not in (1,2)";
		$sqlArr[] = "UPDATE ypn_players SET cooperate=100 WHERE cooperate>100";
		$sqlArr[] = "UPDATE ypn_players SET state=state-1 WHERE state>66";
        
        DBManager::getInstance()->multi_execute(implode(";", $sqlArr));
    }
    
    public function getPlayers($option)
    {
        $players = $this->find('all', $option);
        
        return $this->loadPlayers($players);
    }
    
    public function getHealthyPlayers($teamIds)
    {
        $option['conditions'] = array('team_id'=>$teamIds, 'condition_id <'=>4);
        
        return $this->getPlayers($option);
    }
    
    public function loadPlayers($arrPlayers)
    {
        return $this->loadData($arrPlayers, 'Player');
    }
	
	public function getPunishFieldByMatchClassId($matchClassId)
	{
		$punishField = '';
		switch ($matchClassId)
        {
            case 1:
            case 31:
                $punishField = "Punish1Count";
                break;
            case 2:
            case 8:
            case 9:
            case 10:
                $punishField = "Punish2Count";
                break;
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 12:
            case 13:
            case 14:
            case 15:
            case 16:
            case 17:
            case 23:
                $punishField = "Punish3Count";
                break;
            default:
            	$punishField = "Punish2Count";
                break;
        }
		return $punishField;
	}
	
    
    /**
     * 对抗
     * @param type $attackDir
     * @param type $attackShoufaPlayers
     * @param type $defenseShoufaPlayers 
     * @return array('result'=>1射门 2犯规 3防守成功, 'playerIndex'=>58)
     */
    public function collision($attackDir, &$attackShoufaPlayers, &$defenseShoufaPlayers, $matchClassId)
    {
        switch ($attackDir)
		{
			case 1:
				$attackPoses = array(1, 5, 8, 9, 13);
				$defenserPoses = array(2, 10, 14);
				$attackDirField = "LeftProperties";
				$defenseDirField = "RightProperties";
				$aDirstr = "左";
				break;
			case 2:
				$attackPoses = array(1, 2, 7, 8);
				$defenserPoses = array(2, 3, 8);
				$attackDirField = "MidProperties";
				$defenseDirField = "MidProperties";
				$aDirstr = "中";
				break;
			case 3:
				$attackPoses = array(1, 6, 8, 10, 14);
				$defenserPoses = array(2, 9, 13);
				$attackDirField = "RightProperties";
				$defenseDirField = "LeftProperties";
				$aDirstr = "右";
				break;
		}
        
        /*attack power*/
        $max = 0;
		$passerIndex = -1;
        $attackPower = 0;
		for ($i = 0;$i < count($attackShoufaPlayers);$i++)
		{
			if (in_array($attackShoufaPlayers[$i]->position_id, $attackPoses))
			{
                $attackPower += $attackShoufaPlayers[$i]->getAttackPower($attackDirField);
				$temp = $attackShoufaPlayers[$i]->getPassRate($attackDirField);
				if ($temp > $max)
				{
					$max = $temp;
					$passerIndex = $i;
				}
			}
		}
        
        /*defense power*/
		$max = 0;
		$tacklerIndex = -1;
		$defensePower = 0;
		for ($i = 0;$i < count($defenseShoufaPlayers);$i++)
		{
			if (in_array($defenseShoufaPlayers[$i]->position_id, $defenserPoses))
			{
                $defensePower += $defenseShoufaPlayers[$i]->getDefensePower($defenseDirField);
				$temp = $defenseShoufaPlayers[$i]->getTackleRate($defenseDirField);
				if ($temp > $max)
				{
					$max = $temp;
					$tacklerIndex = $i;
				}
			}
		}
		
		if($attackPower > $defensePower)
		{
			if($tacklerIndex != -1)
			{
				$result = mt_rand(1, 2);
			}
			else
			{
				$result = 1;
			}
		}
		else
		{
			$result = 3;
			$defenseShoufaPlayers[$tacklerIndex]->addTackle($matchClassId);
		}
		
        return array('result'=>$result, 'attackerIndex'=>$passerIndex, 'defenserIndex'=>$tacklerIndex);
    }
    
    public function train($trainingTeamsId, $myTeamId)
	{
        $myInjuredPlayers = array();
		
		/*状态满的增加训练经验值*/
		$trainings = MainConfig::$trainings;
		
		/*这里改为了体力大于等于80的训练值才可能上升，如果是训练体力，需要重新设置*/
		foreach ($trainings as $trainingId=>$training)
        {
			$trainingCondition = ["training_id"=>$trainingId, 'condition_id <' => 4, 'team_id'=> $trainingTeamsId];
			if ($trainingId != 8) //体能训练不需要有潜力点
			{
				$trainingCondition['potential >'] = 0;
            }
			
			$this->update(array($training['experience'] => $training['experience'] . "+1"), $trainingCondition);
        }
        
        $this->update(array('sinew'=>'sinew-10', 'state'=>'state+3'), array('condition_id<>'=>4, 'team_id'=>$trainingTeamsId));
        
        $trainingPlayers = $this->find('all', array(
            'conditions' => array('team_id' => $trainingTeamsId, 'condition_id <>' => 4),
            'contain' => array(),
            'fields' => array('id', 'name', 'team_id', 'ImgSrc')
        ));
        
        shuffle($trainingPlayers);
        
        $injuredPlayerCount = mt_rand(0, (int)(count($trainingPlayers)/300) );
        $injuredDays = mt_rand(1, 30);
        $injuredIds = array();
        
        for ($i = 0;$i < $injuredPlayerCount;$i++)
        {
            $injuredIds[] = $trainingPlayers[$i]['id'];
            $trainingPlayers[$i]['InjuredDay'] = $injuredDays;
            
            if ($trainingPlayers[$i]['team_id'] == $myTeamId)
            {
                $myInjuredPlayers[] = $trainingPlayers[$i];
            }
        }
        
        if (!empty($injuredIds))
        {
            $this->update(array('condition_id'=>4, 'InjuredDay'=>$injuredDays), array('id'=>$injuredIds));
        }
        
        return $myInjuredPlayers;
	}
    
	public function caltotal_salary($team_id)
	{
        $data = $this->query('select sum(salary) as total from ' . MainConfig::PREFIX . $this->table . ' where team_id=' . $team_id);
        $totalSalary = round($data[0]['total'], 2);
		return $totalSalary;
	}
    
    public function getUsedNOs($teamId)
    {
        $data = $this->find('all', array(
            'conditions' => array('team_id'=>$teamId),
            'fields' => array('ShirtNo')
        ));
        
        $usedNOs = array();
        foreach($data as $d)
        {
            $usedNOs[] = $d['ShirtNo'];
        }
        
        return $usedNOs;
    }
    
    public function drink($myTeamId)
    {
        $myDrinkPlayers = array();
        $drinkIdArr = array();
        
        if (mt_rand(1, 2) == 1)
        {
            $idSort = "asc";
        }
        else
        {
            $idSort = 'desc';
        }

        $conditions = array('condition_id <>' => 4, 'moral <' => 90);
        $contain = array();
        $fields = array('id', 'name', 'moral', 'ImgSrc', 'team_id');
        $order = array('moral'=>'asc', 'id' => $idSort);

        $players = $this->find('all', compact('conditions', 'contain', 'fields', 'order'));

        $accidentCount = mt_rand(0, 200);
        foreach ($players as $p)
        {
            if (mt_rand(1, 30) == 1)
            {
                $drinkIdArr[] = $p['id'];
                if ($p['team_id'] == $myTeamId)
                {
                    $myDrinkPlayers[] = $p;
                }
                
                $accidentCount--;
                if ($accidentCount == 0) break;
            }
        }
        
        $downState = mt_rand(1, 20);
        
        $this->update(array("state" => "state -" . $downState), array('id' => $drinkIdArr));
        
        return $myDrinkPlayers;
    }
    
    /**
     *
     * @param type $passerIndex
     * @param type $attackPlayers
     * @param type $defensePlayers
     * @param type $attackDir
     * @return array(result 1goal 2corner 3tanchu)
     */
    public function shot($passerIndex, &$attackPlayers, &$defensePlayers, $attackDir, $matchClassId)
    {
        $goalkeeperIndex = -1;
        $max = 0;
		$shoter = NULL;
		
        for($i=0;$i<count($attackPlayers['shoufa']);$i++)
        {
            if ($i == $passerIndex)                
				continue;
			
            $shotRate = $attackPlayers['shoufa'][$i]->getShotRate();
            if ($shotRate > $max)
            {
                $max = $shotRate;
				$shoter = $attackPlayers['shoufa'][$i];
            }
        }
        
        for($i=0;$i<count($defensePlayers['shoufa']);$i++)
        {
            if ($defensePlayers['shoufa'][$i]->position_id == 4)
            {
                $goalkeeperIndex = $i;
                break;
            }
        }
        
        $shotResultData = ['shoter'=>$shoter, 'goalkeeperIndex'=>$goalkeeperIndex];
		
		if($goalkeeperIndex == -1)
		{
			var_dump($defensePlayers);exit;
		}
		
		if(!$shoter)
		{
			$shotResultData['result'] = 4;
		}
		else
		{
			if ($shoter->getShotValue($attackDir) > $defensePlayers['shoufa'][$goalkeeperIndex]->getSaveValue())
			{
				$shotResultData['result'] = 1;
				$defensePlayers['shoufa'][$goalkeeperIndex]->onGoaled($matchClassId);
			}
			else
			{
				$defensePlayers['shoufa'][$goalkeeperIndex]->onSaved($matchClassId);
				$shotResultData['result'] = mt_rand(2, 3);
			}
		}
        
        return $shotResultData;
    }
    
//    public function getCornerKickerIndex($attackShoufaPlayers, $cornerKickerId)
//    {
//        $cornerKickerIndex = -1;
//        $isGetCornerKicker = false;
//        for($i=0;$i<count($attackShoufaPlayers);$i++)
//        {
//            if ($attackShoufaPlayers[$i]->id == $cornerKickerId)
//            {
//                $cornerKickerIndex = $i;
//                $isGetCornerKicker = true;
//                break;
//            }
//        }
//        
//        if (!$isGetCornerKicker)
//        {
//            $max = 0;
//            $maxIndex = -1;
//            $attackPlayerCount = count($attackShoufaPlayers);
//            for($i=0;$i<$attackPlayerCount;$i++)
//            {
//                $cornerValue = $attackShoufaPlayers[$i]->getCornerValue();
//                if ($cornerValue > $max)
//                {
//                    $max = $cornerValue;
//                    $maxIndex = $i;
//                }
//            }
//            
//            $cornerKickerIndex = $maxIndex;
//        }
//        
//        return $cornerKickerIndex;
//    }
    
//    public function qiangdian($attackPlayers, $defensePlayers, $cornerKickerId, $cornerPosition, $isHigh)
//    {
//        $isAttackingGet = true;
//        $max = 0;
//        $headPlayer = NULL;
//		$goalkeeper = NULL;
//		
//        for($i=0;$i<count($attackPlayers);$i++)
//        {
//            if ($attackPlayers[$i]->id == $cornerKickerId)                
//				continue;
//			
//            if (($attackPlayers[$i]->CornerPosition_id == $cornerPosition) && ($attackPlayers[$i]->position_id != 4) )
//            {
//                $qiangdianValue = $attackPlayers[$i]->getQiangdianValue($isHigh);
//                if ($qiangdianValue > $max)
//                {
//                    $max = $qiangdianValue;
//                    $headPlayer = $attackPlayers[$i];
//                }
//            }
//        }
//        
//        for($i=0;$i<count($defensePlayers);$i++)
//        {
//            if ($defensePlayers[$i]->position_id == 4) 
//            {
//				$goalkeeper = $defensePlayers[$i];
//            }
//            
//            if ($defensePlayers[$i]->CornerPosition_id == $cornerPosition)
//            {
//                $qiangdianValue = $defensePlayers[$i]->getQiangdianValue($isHigh);
//                if ($qiangdianValue > $max)
//                {
//                    $max = $qiangdianValue;
//                    $headPlayer = $defensePlayers[$i];
//                    $isAttackingGet = false;
//                }
//            }
//        }
//        
//        return array('header'=>$headPlayer, 'goalkeeper'=>$goalkeeper, 'isAttackingGet'=>$isAttackingGet);
//    }
    
    public function sellBestPlayer($teamId)
    {
        $maxFee = 0;
        $temp = 0;
		$maxPlayer = NULL;
        $nowDate = SettingManager::getInstance()->getNowDate();

        $playerArray = $this->find('all', array('conditions' => array('team_id' => $teamId)));
        $players = $this->loadData($playerArray);
        for ($i=0;$i<count($players);$i++)
        {
            $temp = $players[$i]->estimateFee($nowDate);
            if ($temp >= $maxFee)
            {
				$maxPlayer = $players[$i];
                $maxFee = $temp;
            }
        }
		
		if($maxPlayer)
		{
			$maxPlayer->isSelling = 1;
			$maxPlayer->fee = $maxFee;
			$maxPlayer->save();
			return array('name'=>$maxPlayer->name, 'fee'=>$maxFee);
		}
		else
		{
			return NULL;
		}
    }
    
	/**
	 * 卖出多余球员
	 * @param type $teamId
	 * @param type $formattion
	 * @return type
	 */
    public function sellUnnecessaryPlayer($teamId, $formattion)
    {
    	$playersArray = $this->query('select * from ypn_players where team_id=' . $teamId . " and id not in (select player_id from ypn_future_contracts) order by ClubDepending desc, LastSeasonScore desc");
        $curTeamPlayers = $this->loadData($playersArray);
        unset($playersArray);
        
        $this->sellExcessPositionPlayer($curTeamPlayers, 4, 3);
		$this->sellExcessPositionPlayer($curTeamPlayers, 3, 3);
		$this->sellExcessPositionPlayer($curTeamPlayers, 9, 3);
		$this->sellExcessPositionPlayer($curTeamPlayers, 10, 3);
		$this->sellExcessPositionPlayer($curTeamPlayers, 13, 3);
		$this->sellExcessPositionPlayer($curTeamPlayers, 14, 3);
		$this->sellExcessPositionPlayer($curTeamPlayers, 2, 3);
		
    	switch ($formattion) 
		{
			case "4-4-2":
				$this->sellExcessPositionPlayer($curTeamPlayers, 1, 5);
				$this->sellExcessPositionPlayer($curTeamPlayers, 8, 3);
				$this->sellExcessPositionPlayer($curTeamPlayers, 3, 3);
                $this->sellExcessPositionPlayer($curTeamPlayers, 5, 0);
                $this->sellExcessPositionPlayer($curTeamPlayers, 6, 0);
                $this->sellExcessPositionPlayer($curTeamPlayers, 7, 0);
				break;
        	case "3-5-2":
        		$this->sellExcessPositionPlayer($curTeamPlayers, 2, 3);
        		$this->sellExcessPositionPlayer($curTeamPlayers, 8, 3);
        		$this->sellExcessPositionPlayer($curTeamPlayers, 1, 5);
                $this->sellExcessPositionPlayer($curTeamPlayers, 5, 0);
                $this->sellExcessPositionPlayer($curTeamPlayers, 6, 0);
                $this->sellExcessPositionPlayer($curTeamPlayers, 7, 0);
        		break;
            case "5-3-2":
				$this->sellExcessPositionPlayer($curTeamPlayers, 3, 5);
				$this->sellExcessPositionPlayer($curTeamPlayers, 1, 5);
                $this->sellExcessPositionPlayer($curTeamPlayers, 5, 0);
                $this->sellExcessPositionPlayer($curTeamPlayers, 6, 0);
                $this->sellExcessPositionPlayer($curTeamPlayers, 7, 0);
            	break;
        	case "3-4-3":
		        $this->sellExcessPositionPlayer($curTeamPlayers, 2, 3);
		        $this->sellExcessPositionPlayer($curTeamPlayers, 5, 3);
		        $this->sellExcessPositionPlayer($curTeamPlayers, 6, 3);
		        $this->sellExcessPositionPlayer($curTeamPlayers, 7, 3);
				break;
            case "4-3-3":
            	$this->sellExcessPositionPlayer($curTeamPlayers, 3, 3);
				$this->sellExcessPositionPlayer($curTeamPlayers, 5, 3);
		        $this->sellExcessPositionPlayer($curTeamPlayers, 6, 3);
		        $this->sellExcessPositionPlayer($curTeamPlayers, 7, 3);
            	break;
            case "4-5-1":
            	$this->sellExcessPositionPlayer($curTeamPlayers, 3, 3);	
		        $this->sellExcessPositionPlayer($curTeamPlayers, 7, 3);	
		        $this->sellExcessPositionPlayer($curTeamPlayers, 2, 3);	
		        $this->sellExcessPositionPlayer($curTeamPlayers, 8, 3);		
            	break;
            case "圣诞树":
            	$this->sellExcessPositionPlayer($curTeamPlayers, 3, 3);	
            	$this->sellExcessPositionPlayer($curTeamPlayers, 8, 5);		
            	$this->sellExcessPositionPlayer($curTeamPlayers, 7, 3);
                $this->sellExcessPositionPlayer($curTeamPlayers, 5, 0);
                $this->sellExcessPositionPlayer($curTeamPlayers, 6, 0);
                $this->sellExcessPositionPlayer($curTeamPlayers, 1, 0);
	            break;
		}
		
		$data = array();
		foreach($curTeamPlayers as $p)
		{
			if($p->isSelling)
			{
				$data[] = array('id'=>$p->id, 'isSelling'=>1, 'fee'=>$p->fee);
			}
		}
		
		PlayerManager::getInstance()->update_batch($data, 'id');

        return $curTeamPlayers;
    }
    
    private function sellExcessPositionPlayer(&$curTeamPlayers, $position_id, $maxCount)
    {
        $nowDate = SettingManager::getInstance()->getNowDate();
        $sameCount = 0;

        for ($i = 0;$i < count($curTeamPlayers);$i++)
        {
        	if (($curTeamPlayers[$i]->isSelling == 0) && ($curTeamPlayers[$i]->position_id == $position_id))
        	{
        		$sameCount++;
                if ($sameCount > $maxCount)
	            {
					$curTeamPlayers[$i]->setSelling($nowDate);
	            }
        	}
        }
    }
    
    public function resetPlayers()
    {
        $ignoreFields = ['is_push_baidu'];
        DBManager::getInstance()->copyTable(MainConfig::PREFIX . 'bak_players', MainConfig::PREFIX . $this->table, $ignoreFields);
    }
    
    public function getAllTeamUsedNOs($allRetiredShirts)
    {
        $allTeamUsedNOs = array();
        foreach($allRetiredShirts as $rs)
        {
            $allTeamUsedNOs[$rs['team_id']][] = $rs['shirt'];
        }
        
        $myPlayers = $this->find('all', array(
            'fields'=>array('ShirtNo', 'team_id'),
            'contain'=>array()
        ));
        
        foreach ($myPlayers as $ap)
        {
            $allTeamUsedNOs[$ap['team_id']][] = $ap['ShirtNo'];
        }
        
        return $allTeamUsedNOs;
    }

    public function getAllPlayers()
    {
        $records = $this->find('all', array(
            'fields' => array('name', 'ShirtNo', 'team_id', 'position_id'),
        ));
        
        $allPlayers = array();
        $newPlayer = new Player();
        foreach($records as $r)
        {
            $newPlayer = clone $newPlayer;
            $newPlayer->setName($r['name']);
            $newPlayer->setShirtNo($r['ShirtNo']);
            $newPlayer->setTeamId($r['team_id']);
            $newPlayer->setPositionId($r['position_id']);
            $allPlayers[] = $newPlayer;
        }
        
        return $allPlayers;
    }
    
    public function getLastPlayerId()
    {
        $lastPlayer = $this->find('first', array(
            'order' => array('id'=>'desc'),
        ));
        return $lastPlayer['id'];
    }
    
    /**
     * 最小量的抽取新队员
     * @param type $position_id
     * @param type $needPosCount 需要的数量
     * @param type $firstNames
     * @param type $familyNames
     * @param type $countries
     * @param type $usedNOs
     * @param type $curPosCount
     * @return type
     */
	public function getLeastYoungPlayers($position_id, $needPosCount, $firstNames, $familyNames, $countries, &$usedNOs, $leagueId, $teamId, $nowDate, &$existPlayerNames)
	{
        $names = array();
        for ($i = 0; $i < ($needPosCount); $i++)
        {
            $newPlayer = Player::generateYoung($leagueId, $teamId, $position_id, $firstNames, $familyNames, $countries, $usedNOs, $nowDate, $existPlayerNames);
            
            $names[] = $newPlayer->name;
            $usedNOs[] = $newPlayer->ShirtNo;
			
			self::$youngPlayers[] = $newPlayer;
        }
        return $names;
	}
	
    public function saveAllData()
    {
        if (!empty(self::$youngPlayers))
        {
            $this->saveMany(self::$youngPlayers);
        }
    }
	
	public function getExistNos($teamId)
	{
		$players = PlayerManager::getInstance()->find('all', array(
			'fields' => array('ShirtNo'),
			'conditions' => array('team_id'=>$teamId),
		));
		
		$nos = array();
		foreach($players as $p)
		{
			$nos[] = $p['ShirtNo'];
		}
		return $nos;
	}
	
	public function groupAllPositionByTeamId()
	{
		$players = $this->find('all', array(
			'conditions' => array('not'=>array('team_id'=>array(0,100))),
			'fields' => array('position_id', 'team_id')
		));
		
		$positionCountArr = array();
		foreach($players as $p)
		{
			if (isset($positionCountArr[$p['team_id']][$p['position_id']]))
			{
				$positionCountArr[$p['team_id']][$p['position_id']]++;
			}
			else
			{
				$positionCountArr[$p['team_id']][$p['position_id']] = 1;
			}
		}
		return $positionCountArr;
	}
	
	/**
	 * 将指定league的player排前面
	 * @param type $allCanBuyPlayers
	 * @param type $leagueId
	 * @return type
	 */
	public function sortByMyLeague($allCanBuyPlayers, $leagueId)
	{
		$myLeaguePlayers = array();
		for ($k = 0;$k < count($allCanBuyPlayers);$k++)
		{
			if ($allCanBuyPlayers[$k]['league_id'] == $leagueId)
			{
				$myLeaguePlayers[] = $allCanBuyPlayers[$k];
				unset($allCanBuyPlayers[$k]);
			}
		}

		$allCanBuyPlayers = array_merge($myLeaguePlayers, $allCanBuyPlayers);
		return $allCanBuyPlayers;
	}
	
	public function resettotal_salaryAndPlayerCount()
    {
        $allTeamPlayerData = array();
        $allPlayers = PlayerManager::getInstance()->find('all', array(
            'conditions' => array('team_id<>'=>0),
            'fields' => array('id', 'team_id', 'salary')
        ));
        
		foreach($allPlayers as $k=>$player)
		{
			if(isset($allTeamPlayerData[$player['team_id']]))
			{
				$allTeamPlayerData[$player['team_id']]['player_count'] += 1;
				$allTeamPlayerData[$player['team_id']]['total_salary'] += $player['salary'];
			}
			else
			{
				$allTeamPlayerData[$player['team_id']] = array('id'=>$player['team_id'], 'player_count'=>1, 'total_salary'=>$player['salary']);
			}
		}
		return $allTeamPlayerData;
    }
	
	
	/**
	 * 
	 * @param type $attackShoufaPlayers
	 * @param type $defenseShoufaPlayers
	 * @param type $teamPenaltyKickerId
	 * @param int $matchClassId
	 * @return int 1goal 2save
	 */
	public function penalty(PlayerCollection $attackShoufaCollection, PlayerCollection $defenseShoufaCollection, $teamPenaltyKickerId, $matchClassId)
	{
		$result = 1;
		$max = 0;
		$penaltyKicker = NULL;
		foreach($attackShoufaCollection as &$p)
		{
			if($p->id == $teamPenaltyKickerId)
			{
				$penaltyKicker = $p;
				break;
			}
			else
			{
				if($p->getPenaltyWeight() > $max)
				{
					$max = $p->getPenaltyWeight();
					$penaltyKicker = $p;
				}
			}
		}
		
		//goalkeeper
		$goalKeeper = $defenseShoufaCollection->getGoalkeeper();
		
		if($penaltyKicker->getPenaltyValue() > $goalKeeper->getPenaltySaveValue())
		{
			$result = 1;
			$penaltyKicker->addPenalty($matchClassId);
		}
		else
		{
			$result = 2;
			$goalKeeper->score += 4;
			if($goalKeeper->potential > 0)
			{
				$goalKeeper->SaveExperience += 4;
			}
		}
		
		return array('result'=>$result, 'penalty_kicker'=>$penaltyKicker, 'goal_keeper'=>$goalKeeper) ;
	}
	
	/**
	 * 
	 * @param type $attackShoufaPlayers
	 * @param type $defenseShoufaPlayers
	 * @param type $teamFreeKickerId
	 * @param int $matchClassId
	 * @return int 1goal 2save
	 */
	public function free(&$attackShoufaPlayers, &$defenseShoufaPlayers, $teamFreeKickerId, $matchClassId)
	{
		$result = 1;
		$max = 0;
		$freeKicker = NULL;
		foreach($attackShoufaPlayers as &$p)
		{
			if($p->id == $teamFreeKickerId)
			{
				$freeKicker = $p;
				break;
			}
			else
			{
				if($p->getFreeWeight() > $max)
				{
					$max = $p->getFreeWeight();
					$freeKicker = $p;
				}
			}
		}
		
		//goalkeeper
		$goalKeeper = NULL;
		foreach($defenseShoufaPlayers as &$p)
		{
			if($p->position_id == 4)
			{
				$goalKeeper = $p;
				break;
			}
		}
		
		if($freeKicker->getFreeValue() > $goalKeeper->getFreeSaveValue())
		{
			$result = 1;
		}
		else
		{
			$result = 2;
			$goalKeeper->score += 2;
			$goalKeeper->SaveExperience += 4;
		}
		
		return array('result'=>$result, 'free_kicker'=>$freeKicker, 'goal_keeper'=>$goalKeeper);
	}
	
	public function clearPunish($teamIds, $matchClassId)
	{
		$punishField = $this->getPunishFieldByMatchClassId($matchClassId);
		$this->update(array($punishField=>$punishField.'-1'), array($punishField.' >'=>0, 'team_id'=>$teamIds));
	}
}