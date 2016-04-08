<?php
namespace Model\Manager;
use MainConfig;
use Model\Core\Player;

class PlayerManager extends DataManager 
{
    public $table = "players";
    static $youngPlayers = array();
    
    public function doNormal()
    {
        $sqlArr = array();
        $sqlArr[] = "update ypn_players set condition_id=3 where condition_id=5 and Punish1Count=0 and Punish2Count=0 and Punish3Count=0";
		$sqlArr[] = "update ypn_players set sinew=sinew+25,YellowTodayCount=0";
		$sqlArr[] = "update ypn_players set sinew=SinewMax where sinew>SinewMax";
		$sqlArr[] = "update ypn_players set state=100 where state>100";
		$sqlArr[] = "update ypn_players set InjuredDay=InjuredDay-1 where InjuredDay>0";
		$sqlArr[] = "update ypn_players set condition_id=3 where InjuredDay=0 and condition_id not in (1,2)";
		$sqlArr[] = "update ypn_players set cooperate=100 where cooperate>100";
		$sqlArr[] = "update ypn_players set state=state-2 where state>66";
        
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
        
    public function setShoufa($players, $matchClassId, $formattion)
    {
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

        /*公共的 门将 左右前卫 左右后卫*/
        $this->online2(13, $punishField, $players, $matchClassId);
        $this->online2(14, $punishField, $players, $matchClassId);
        $this->online2(9, $punishField, $players, $matchClassId);
        $this->online2(10, $punishField, $players, $matchClassId);
        $this->online2(4, $punishField, $players, $matchClassId);

        /*根据阵型判断*/
        switch ($formattion) 
        {
        	case "4-4-2":
	            $this->online2(8, $punishField, $players, $matchClassId);
	        	$this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(1, $punishField, $players, $matchClassId);
	            $this->online2(1, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
        		break;
        	case "3-5-2":
            case "5-3-2":
        	    $this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(8, $punishField, $players, $matchClassId);
	            $this->online2(1, $punishField, $players, $matchClassId);
	            $this->online2(1, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
            	break;
        	case "3-4-3":
				$this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(5, $punishField, $players, $matchClassId);
	            $this->online2(6, $punishField, $players, $matchClassId);
	            $this->online2(7, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
				break;
				
            case "4-3-3":
				$this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(5, $punishField, $players, $matchClassId);
	            $this->online2(6, $punishField, $players, $matchClassId);
	            $this->online2(7, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
            	break;
            case "4-5-1":
            	$this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(8, $punishField, $players, $matchClassId);
	            $this->online2(1, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
            	break;
			case "5-4-1":
            	$this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
	            $this->online2(1, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
            	break;
            case "圣诞树":
	            $this->online2(2, $punishField, $players, $matchClassId);
	            $this->online2(8, $punishField, $players, $matchClassId);
	            $this->online2(8, $punishField, $players, $matchClassId);
	            $this->online2(1, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
	            $this->online2(3, $punishField, $players, $matchClassId);
	            break;
        }

        /*设置5个替补队员*/
        $j = 0;
        $isGetKeeper = false;

        for ($i = 0;$i < count($players);$i++)
        {
        	if ( ($players[$i]->condition_id == 3) && ($players[$i]->position_id == 4)  && ($players[$i]->$punishField == 0) && !$isGetKeeper)
        	{
        		$players[$i]->condition_id = 2;
        		$isGetKeeper = true;
        	}
        	
        	if ( ($players[$i]->condition_id == 3) && ($players[$i]->position_id != 4) && ($players[$i]->$punishField == 0) && ($j < 4) )
        	{
        		$players[$i]->condition_id = 2;
        		$j++;
        	}
                	
        	if ($j == 4 && $isGetKeeper) break;
        }
        
		/*ClubDepending 首发+1,场外-1*/
		for ($i = 0;$i < count($players);$i++)
		{
			if ($players[$i]->condition_id == 1)
			{
				if ($players[$i]->ClubDepending < 100)
				{
					$players[$i]->ClubDepending += 1;
				}
				
				if ($players[$i]->loyalty < 100)
				{
					$players[$i]->loyalty += 1;
				}
			}
			
			if ( ($players[$i]->condition_id == 3) && ($players[$i]->ClubDepending > 30) && ($players[$i]->state > 95) && ($players[$i]->sinew > 78) && ($players[$i]->$punishField == 0) )
			{
				$players[$i]->ClubDepending -= 1;
				$players[$i]->loyalty -= 1;
			}
		}
        
        $matchPlayers = array();
        
        foreach($players as $player)
        {
            if ($player->condition_id == 1)
            {
                $matchPlayers['shoufa'][] = $player;
            }
            else if ($player->condition_id == 2)
            {
                $matchPlayers['bandeng'][] = $player;
            }
        }
        
        return $matchPlayers;
    }
    
    private function online2($position_id, $punish, &$players, $matchClassId)
    {		        
		//如果是友谊赛，遍历所有球员，状态*磨合度低的球员上场
		if ($matchClassId == 24)
		{
			$minPower = 10000;
			$onlineIndex = -1;
			for ($i = 0;$i < count($players);$i++)
			{
				if ( ($players[$i]->condition_id == 3) && ($players[$i]->sinew > 70) && ($players[$i]->position_id == $position_id) )
				{
					if ($players[$i]->cooperate * $players[$i]->state < $minPower)
					{
						$minPower = $players[$i]->cooperate * $players[$i]->state;
						$onlineIndex = $i;	
					}
				}
			}
			
			if ($onlineIndex <> -1)
			{
                $players[$onlineIndex]->condition_id = 1;
				
				return;
			}
		}
		
		//其他比赛类型走正常步骤
        switch ($position_id)
        {
            case 5:
            case 9:
            case 13:
                $dirProperties = "LeftProperties";
                break;
            case 6:
            case 10:
            case 14:
                $dirProperties = "RightProperties";
                break;
            default:
                $dirProperties = "MidProperties";
                break;
        }

        /*根据位置来设置不同的排序方式*/
        $max = 0;
        $temp = 0;
        $bestIndex = -1;
        for ($i = 0;$i < count($players);$i++)
        {
        	if ($players[$i]->condition_id == 3 && $players[$i]->$punish == 0 && $players[$i]->sinew > 70)
        	{
	        	switch ($position_id)
		        {
		            case 1:
		                $temp = $players[$i]->ShotDesire + $players[$i]->ShotAccurate + $players[$i]->ShotPower + $players[$i]->qiangdian;
		                break;
		            case 2:
		                $temp = $players[$i]->pinqiang + $players[$i]->tackle + $players[$i]->BallControl + $players[$i]->close_marking + $players[$i]->pass + $players[$i]->SinewMax;
		                break;
		            case 3:
		                $temp = $players[$i]->tackle + $players[$i]->qiangdian + $players[$i]->close_marking + $players[$i]->height / 2;
		                break;
		            case 4:
		                $temp = $players[$i]->save + $players[$i]->height / 2;
		            	break;
                    case 5:
		            case 6:
		                $temp = ($players[$i]->agility + $players[$i]->speed + $players[$i]->beat);
		                break;
		            case 7:
		                $temp = $players[$i]->header + $players[$i]->ShotDesire + $players[$i]->ShotAccurate + $players[$i]->ShotPower + $players[$i]->qiangdian  + $players[$i]->height / 2;
		                break;
		            case 8:
		                $temp = $players[$i]->ShotAccurate + $players[$i]->BallControl + $players[$i]->pass;
		                break;
		            case 9:
		            case 10:
		                $temp = ($players[$i]->agility + $players[$i]->BallControl + $players[$i]->beat + $players[$i]->speed);
		                break;
		            case 13:
		            case 14:
		                $temp = ($players[$i]->agility/2 + $players[$i]->tackle + $players[$i]->close_marking + $players[$i]->speed/2);
		                break;
		        }
		        
		        $temp *= $players[$i]->state * $players[$i]->$dirProperties;
		        if ($temp > $max)
		        {
		        	$max = $temp;
		        	$bestIndex = $i;
		        }
        	}
        }

        /*如果没有找到sinew<70的*/
        if ($bestIndex == -1)
        {
            for ($i = 0;$i < count($players);$i++)
        	{
	        	if ($players[$i]->condition_id == 3 && $players[$i]->$punish == 0)
	        	{
		        	$bestIndex = $i;
		        	break;
	        	}
        	}
        }
        
        if ($bestIndex == -1) return; 
        
        $players[$bestIndex]->condition_id = 1;
        $players[$bestIndex]->position_id = $position_id;
    }
    
    /**
     *
     * @param type $attackDir
     * @param type $attackPlayers
     * @param type $defensePlayers 
     * @return array('result'=>1, 'playerIndex'=>58)
     */
    public function collision($attackDir, $attackPlayers, $defensePlayers)
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
		for ($i = 0;$i < count($attackPlayers);$i++)
		{
			if (in_array($attackPlayers[$i]->position_id, $attackPoses, true))
			{
                $attackPower += $attackPlayers[$i]->getAttackPower($attackDirField);
				$temp = $attackPlayers[$i]->getPassRate($attackDirField);
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
		for ($i = 0;$i < count($defensePlayers);$i++)
		{
			if (in_array($defensePlayers[$i]->position_id, $defenserPoses, true))
			{
                $defensePower += $defensePlayers[$i]->getDefensePower($defenseDirField);
				$temp = $defensePlayers[$i]->getTackleRate($defenseDirField);
				if ($temp > $max)
				{
					$max = $temp;
					$tacklerIndex = $i;
				}
			}
		}
        
        $result = ($attackPower > $defensePower);
        return array('result'=>$result, 'attackerIndex'=>$passerIndex, 'defenserIndex'=>$tacklerIndex);
    }
    
    public function train($trainingTeamsId, $myTeamId)
	{
        $myInjuredPlayers = array();
		
		/*状态满的增加训练经验值*/
		$trainings = MainConfig::$trainings;
		
		/*这里改为了体力大于等于80的训练值才可能上升，如果是训练体力，需要重新设置*/
		foreach ($trainings as $id=>$training)
        {
			if ($id == 8) //sinew
			{
                $this->update(array($training['experience'] => $training['experience'] . "+1"), array("training_id" => $id, 'condition_id <' => 4, 'team_id'=> $trainingTeamsId));
			}
			else
			{
                $this->update(array($training['experience'] => $training['experience'] . "+1"), array("training_id" => $id, 'condition_id <' => 4, 'team_id'=> $trainingTeamsId, 'sinew>' => 79));
            }
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
    
    public function checkTrainingAdd($nowDate)
	{
        $trainings = MainConfig::$trainings;
        foreach($trainings as $t)
        {
            $conditions['or'][$t['experience'] . " >"] = 99;
        }
        $fields = array('id', 'name', 'ImgSrc', 'ShotAccurateExperience', 'ShotAccurate', 'PassExperience', 'pass', 'TackleExperience', 'tackle',
            'BallControlExperience', 'BallControl', 'BeatExperience', 'beat', 'SaveExperience', 'save', 'SinewMaxExperience', 'SinewMax',
            'QiangdianExperience', 'qiangdian', 'HeaderExperience', 'header', 'position_id');
        $allPlayers = $this->find('all', compact('conditions', 'fields'));    
            
		foreach ($trainings as $id=>$t)
		{
			for ($i = 0;$i < count($allPlayers);$i++)
			{
                if ($allPlayers[$i][$t['experience']] > 99) //相应的经验值大于99，可以升级
                {
                    $imgSrc = $allPlayers[$i]['ImgSrc'];
                    $updateMsg = "<font color=green><strong>" . $allPlayers[$i]['name'] . "</strong></font>的<font color=red><strong>" . $t['title'] . "</strong></font>提高了";
    //				$News->Add1($updateMsg, $players[$j]['team_id'], $nowDate, $imgSrc);
                    $this->changeTrainingState($allPlayers[$i]);
                }
                
			}
			$this->query("update ypn_players set " . $t['experience'] . "=" . $t['experience'] . "-100, `" . $t["skill"] . "`=`" . $t["skill"] . "`+1 where " . $t['experience'] . " > 99 and `" . $t["skill"] . "` <99");
			$this->query("update ypn_players set " . $t['experience'] . "=0 where `" . $t["skill"] . "` >98");
		}
	}
    
	/**
	 * 更改升级状态，最后allplayer一并修改数据库
	 * @param type $playerData 单个player数据
	 * @param type $trainingList 
	 */
    public function changeTrainingState($playerData)
	{
        $curPlayers = $this->loadPlayers(array($playerData));
		$curPlayer = $curPlayers[0];
        $nowDate = SettingManager::getInstance()->getNowDate();
		$isChanged = false;
		
		switch ($playerData['position_id']) 
		{
			case 1:
				$trainingIds = array(1, 9, 4, 6, 2, 5);
				break;
			case 2:
				$trainingIds = array(3, 5, 2, 1, 8);
				break;
			case 3:
				$trainingIds = array(9, 3, 4, 2, 5);
				break;
			case 4:
				$trainingIds = array(7, 5, 9, 3, 4);
				break;
			case 5:
			case 6:
				$trainingIds = array(5, 1, 2, 3);
				break;	
			case 7:
				$trainingIds = array(4, 1, 9, 5, 6);
				break;	
			case 8:
				$trainingIds = array(2, 5, 1, 6, 3);
				break;
			case 9:
			case 10:
				$trainingIds = array(6, 3, 2, 1);
				break;	
			case 13:
			case 14:
				$trainingIds = array(3, 6, 9, 2);
				break;						
		}

		$playerAge = $curPlayer->getAge($nowDate);
		if ( ($playerAge > 30) && ($curPlayer->training_id != 8) )
		{
			$curPlayer->training_id = 8;
			$isChanged = true;
		}
		else
		{
	        for ($i = 0; $i < count($trainingIds); $i++)
            {
				if ( ($curPlayer->$allTrainings[$trainingIds[$i]]['skill'] < 85) && ($curPlayer->training_id != $trainingIds[$i]) )
				{   
					$isChanged = true;
					$curPlayer->training_id = $trainingIds[$i];
					break;
				}
        	}
		}
		
		if ($isChanged)
		{
			$this->save($curPlayer);
		}
	}
    
	public function calTotalSalary($team_id)
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
    public function shot($passerIndex, &$attackPlayers, &$defensePlayers, $attackDir)
    {
        $shoterIndex = -1;
        $goalkeeperIndex = -1;
        $max = 0;
        for($i=0;$i<count($attackPlayers['shoufa']);$i++)
        {
            if ($i == $passerIndex)                continue;
            $shotRate = $attackPlayers['shoufa'][$i]->getShotRate();
            if ($shotRate > $max)
            {
                $max = $shotRate;
                $shoterIndex = $i;
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
        
        $result = array('shoterIndex'=>$shoterIndex, 'goalkeeperIndex'=>$goalkeeperIndex);
        
        if ($attackPlayers['shoufa'][$shoterIndex]->getShotValue($attackDir) > $defensePlayers['shoufa'][$goalkeeperIndex]->getSaveValue())
        {
            $result['result'] = 1;
        }
        else
        {
            $result['result'] = mt_rand(2, 3);
        }
        
        return $result;
    }
    
    public function getCornerKickerIndex($attackPlayers, $cornerKickerId)
    {
        $cornerKickerIndex = -1;
        $isGetCornerKicker = false;
        for($i=0;$i<count($attackPlayers);$i++)
        {
            if ($attackPlayers[$i]->id == $cornerKickerId)
            {
                $cornerKickerIndex = $i;
                $isGetCornerKicker = true;
                break;
            }
        }
        
        if (!$isGetCornerKicker)
        {
            $max = 0;
            $maxIndex = -1;
            $attackPlayerCount = count($attackPlayers);
            for($i=0;$i<$attackPlayerCount;$i++)
            {
                $cornerValue = $attackPlayers[$i]->getCornerValue();
                if ($cornerValue > $max)
                {
                    $max = $cornerValue;
                    $maxIndex = $i;
                }
            }
            
            $cornerKickerIndex = $maxIndex;
        }
        
        return $cornerKickerIndex;
    }
    
    public function qiangdian($attackPlayers, $defensePlayers, $cornerKickerId, $cornerPosition)
    {
        $isAttackingGet = true;
        $max = 0;
        $headerIndex = -1;
        $goalkeeperIndex = -1;
        for($i=0;$i<count($attackPlayers);$i++)
        {
            if ($attackPlayers[$i]->id == $cornerKickerId)                continue;
            if ($attackPlayers[$i]->CornerPosition_id == $cornerPosition)
            {
                $qiangdianValue = $attackPlayers[$i]->getQiangdianValue();
                if ($qiangdianValue > $max)
                {
                    $max = $qiangdianValue;
                    $headerIndex = $i;
                }
            }
        }
        
        for($i=0;$i<count($defensePlayers);$i++)
        {
            if ($defensePlayers[$i]->position_id == 4) 
            {
                $goalkeeperIndex = $i;
            }
            
            if ($defensePlayers[$i]->CornerPosition_id == $cornerPosition)
            {
                $qiangdianValue = $defensePlayers[$i]->getQiangdianValue();
                if ($qiangdianValue > $max)
                {
                    $max = $qiangdianValue;
                    $headerIndex = $i;
                    $isAttackingGet = false;
                }
            }
        }
        
        if ($isAttackingGet && ($headerIndex != -1) )
        {
            $headerValue = $attackPlayers[$headerIndex]->getHeaderValue();
            $saveValue = $defensePlayers[$goalkeeperIndex]->getSaveValue();
            if ($headerValue > $saveValue)
            {
                $result = 1;
            }
            else
            {
                $result = 2;
            }
        }
        else if ($headerIndex == -1)
        {
            $result = 4;
        }
        else if(!$isAttackingGet)
        {
            $result = 3;
        }
        
        return array('headerIndex'=>$headerIndex, 'goalkeeperIndex'=>$goalkeeperIndex, 'isAttackingGet'=>$isAttackingGet, 'result'=>$result);
    }
    
    public function sellBestPlayer($teamId)
    {
        $maxId = 0;
        $maxFee = 0;
        $temp = 0;
        $maxIndex = -1;
        $nowDate = SettingManager::getInstance()->getNowDate();

        $playerArray = $this->find('all', array(
        	'conditions' => array(
			'team_id' => $teamId        
	        ),
	        'contain' => array()
        ));
        
        $players = $this->loadData($playerArray);
        
        for ($i=0;$i<count($players);$i++)
        {
            $temp = $players[$i]->estimateFee($nowDate);
            if ($temp > $maxFee)
            {
                $maxId = $players[$i]->id;
                $maxIndex = $i;
                $maxFee = $temp;
            }
        }


        $this->query("update ypn_players set isSelling=1,fee=" . $maxFee . " where id=" . $maxId);
        
        return array('name'=>$players[$maxIndex]->name, 'fee'=>$maxFee);
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
        $players = $this->loadData($playersArray);
        unset($playersArray);
        
        $this->sellExcessPositionPlayer($players, 4, 3);
		$this->sellExcessPositionPlayer($players, 3, 3);
		$this->sellExcessPositionPlayer($players, 9, 3);
		$this->sellExcessPositionPlayer($players, 10, 3);
		$this->sellExcessPositionPlayer($players, 13, 3);
		$this->sellExcessPositionPlayer($players, 14, 3);
		$this->sellExcessPositionPlayer($players, 2, 3);
		
    	switch ($formattion) 
		{
			case "4-4-2":
				$this->sellExcessPositionPlayer($players, 1, 5);
				$this->sellExcessPositionPlayer($players, 8, 3);
				$this->sellExcessPositionPlayer($players, 3, 3);
                $this->sellExcessPositionPlayer($players, 5, 0);
                $this->sellExcessPositionPlayer($players, 6, 0);
                $this->sellExcessPositionPlayer($players, 7, 0);
				break;
        	case "3-5-2":
        		$this->sellExcessPositionPlayer($players, 2, 3);
        		$this->sellExcessPositionPlayer($players, 8, 3);
        		$this->sellExcessPositionPlayer($players, 1, 5);
                $this->sellExcessPositionPlayer($players, 5, 0);
                $this->sellExcessPositionPlayer($players, 6, 0);
                $this->sellExcessPositionPlayer($players, 7, 0);
        		break;
            case "5-3-2":
				$this->sellExcessPositionPlayer($players, 3, 5);
				$this->sellExcessPositionPlayer($players, 1, 5);
                $this->sellExcessPositionPlayer($players, 5, 0);
                $this->sellExcessPositionPlayer($players, 6, 0);
                $this->sellExcessPositionPlayer($players, 7, 0);
            	break;
        	case "3-4-3":
		        $this->sellExcessPositionPlayer($players, 2, 3);
		        $this->sellExcessPositionPlayer($players, 5, 3);
		        $this->sellExcessPositionPlayer($players, 6, 3);
		        $this->sellExcessPositionPlayer($players, 7, 3);
				break;
            case "4-3-3":
            	$this->sellExcessPositionPlayer($players, 3, 3);
				$this->sellExcessPositionPlayer($players, 5, 3);
		        $this->sellExcessPositionPlayer($players, 6, 3);
		        $this->sellExcessPositionPlayer($players, 7, 3);
            	break;
            case "4-5-1":
            	$this->sellExcessPositionPlayer($players, 3, 3);	
		        $this->sellExcessPositionPlayer($players, 7, 3);	
		        $this->sellExcessPositionPlayer($players, 2, 3);	
		        $this->sellExcessPositionPlayer($players, 8, 3);		
            	break;
            case "圣诞树":
            	$this->sellExcessPositionPlayer($players, 3, 3);	
            	$this->sellExcessPositionPlayer($players, 8, 5);		
            	$this->sellExcessPositionPlayer($players, 7, 3);
                $this->sellExcessPositionPlayer($players, 5, 0);
                $this->sellExcessPositionPlayer($players, 6, 0);
                $this->sellExcessPositionPlayer($players, 1, 0);
	            break;
		}

        /*save*/
        for ($i = 0;$i < count($players);$i++)
        {
        	if ($players[$i]->isSelling)
        	{
        	}
            else
            {
                unset($players[$i]);
            }
        }
        
        $this->saveMany($players);
        
        return $players;
    }
    
    private function sellExcessPositionPlayer(&$players, $position_id, $maxCount)
    {
        $nowDate = SettingManager::getInstance()->getNowDate();
        $sameCount = 0;

        for ($i = 0;$i < count($players);$i++)
        {
        	if (($players[$i]->isSelling == 0) && ($players[$i]->position_id == $position_id))
        	{
        		$sameCount++;
                if ($sameCount > $maxCount)
	            {
	                $sellPrice = round(($players[$i]->estimateFee($nowDate) * $players[$i]->ClubDepending * (70 + mt_rand(1, 60)) / 100 / 100), -1);
	                $players[$i]->isSelling = 1;
	                $players[$i]->fee = $sellPrice;
	            }
        	}
        }
    }
    
    public function resetPlayers()
    {
        $ignoreFields = array();
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
        $newPlayer = array();
        $names = array();
        for ($i = 0; $i < ($needPosCount); $i++)
        {
            $newPlayer = $this->getYoung($leagueId, $teamId, $position_id, $firstNames, $familyNames, $countries, $usedNOs, $nowDate, $existPlayerNames);
            
            $names[] = $newPlayer['name'];
            $usedNOs[] = $newPlayer['ShirtNo'];
        }
        return $names;
	}
	
	private function getYoung($league_id, $team_id, $position_id, $firstNames, $familyNames, $countries, &$usedNOs, $nowDate, &$existPlayerNames)
	{
        $fullName = "";
        $isNameExist = true;
		$thisYear = date('Y', strtotime($nowDate));
        
		shuffle($countries);

        while ($isNameExist)
        {
        	$isNameExist = false;			
            $fullName = $firstNames[mt_rand(0, 59)]['title'] . "·" . $familyNames[mt_rand(0, 59)]['title'];

            if (in_array($fullName, $existPlayerNames, TRUE))
            {
                $isNameExist = true;
            }
            
            if ($isNameExist)
            {
            	$isNameExist = false;
                $fullName = chr(64+mt_rand(1, 26)) . "·" . $fullName;
                if (in_array($fullName, $existPlayerNames, TRUE))
                {
                    $isNameExist = true;
                }
            }
        }
        
        $existPlayerNames[] = $fullName;
		        
        $imgSrc = '/img/DefaultPlayer.jpg';
        $imgSrc = str_replace("ypn_img", "", $imgSrc);
        
        /*获得号码，调用getNewNumber*/
        $newPlayer['position_id'] = $position_id;
        $newPlayer['team_id'] = $team_id;

        /*已经获得名字和号码开始签合同，随机生成生日、合同，角球位随机*/
        $newPlayer['name'] = $fullName;
        $newPlayer['league_id'] = $league_id;

        $newPlayer['country_id'] = $countries[5]['id'];
		$newPlayer['country'] = $countries[5]['title'];
        $newPlayer['CornerPosition_id'] = mt_rand(1, 4);
        $newPlayer['birthday'] = ($thisYear - mt_rand(16, 20)) . "-" . mt_rand(1, 12) . "-" . mt_rand(1,28);
        $newPlayer['ContractBegin'] = $nowDate;
        $newPlayer['ContractEnd'] = ($thisYear+mt_rand(1,5)) . "-6-30";

        /*公用的*/
        $newPlayer['ImgSrc'] = $imgSrc;
        
        $newPlayer['creativation'] = 73 + mt_rand(1, 10);
        $newPlayer['pass'] = 73 + mt_rand(1, 8);
        $newPlayer['speed'] = 75 + mt_rand(1, 10);
        $newPlayer['ShotDesire'] = 75 + mt_rand(0, 6);
        $newPlayer['ShotPower'] = 78 + mt_rand(0, 21);
        $newPlayer['ShotAccurate'] = 74 + mt_rand(0, 4);
        $newPlayer['agility'] = 75 + mt_rand(1, 10);
        $newPlayer['SinewMax'] = 78 + mt_rand(0, 19);
        $newPlayer['cooperate'] = 80;
        $newPlayer['ShirtNo'] = $this->getPlayerNewShirtNo($newPlayer, $usedNOs);
        $newPlayer['arc'] = 73 + mt_rand(0, 26);
        
        /*写入新闻*/
//        $News->add1('我们已从二线队抽调了' . '<font color=green><strong>' . $fullName . '</strong></font>', $team_id, $nowDate, $imgSrc);

        /*根据不同位置获得不同的训练方式*/
        switch ($position_id)
        {
            case 1: //forward
                $newPlayer['ShotDesire'] = 80 + mt_rand(1, 10);
                $newPlayer['ShotPower'] = 80 + mt_rand(1, 10);
                $newPlayer['ShotAccurate'] = 76 + mt_rand(1, 10);
                $newPlayer['qiangdian'] = 70 + mt_rand(1, 10);
                $newPlayer['training_id'] = 1;
                break;
            case 2://dm
                $newPlayer['tackle'] = 76 + mt_rand(1, 10); 
                $newPlayer['pinqiang'] = 76 + mt_rand(1, 10); 
                $newPlayer['scope'] = 70 + mt_rand(1, 10); 
                $newPlayer['close-marking'] = 75 + mt_rand(0, 10); 
                $newPlayer['training_id'] = 3;
                break;
            case 3: //cb
                $newPlayer['tackle'] = 73 + mt_rand(1, 10); 
                $newPlayer['header'] = 74 + mt_rand(1, 10); 
                $newPlayer['height'] = 185 + mt_rand(1, 10); 
                $newPlayer['weight'] = 75 + mt_rand(1, 10); 
                $newPlayer['close-marking'] = 75 + mt_rand(0, 10); 
                $newPlayer['training_id'] = 3;
                break;
            case 4://gk
                $newPlayer['ShotDesire'] = 30;
                $newPlayer['save'] = 78 + mt_rand(1, 5);
                $newPlayer['BallControl'] = 74 + mt_rand(1, 10);
                $newPlayer['height'] = 185 + mt_rand(1, 10);
                $newPlayer['weight'] = 75 + mt_rand(1, 10); 
                $newPlayer['training_id'] = 7;
                break;
            case 7: //cf
                $newPlayer['ShotDesire'] = 80 + mt_rand(1, 10);
                $newPlayer['ShotPower'] = 80 + mt_rand(1, 10);
                $newPlayer['ShotAccurate'] = 70 + mt_rand(1, 10);
                $newPlayer['header'] = 78 + mt_rand(1, 10);
                $newPlayer['qiangdian'] = 74 + mt_rand(1, 10);
                $newPlayer['height'] = 185 + mt_rand(1, 10);
                $newPlayer['weight'] = 75 + mt_rand(1, 10); 
                $newPlayer['training_id'] = 4;
                break;
            case 8: //am
                $newPlayer['ShotDesire'] = 73 + mt_rand(1, 10);
                $newPlayer['ShotPower'] = 80 + mt_rand(1, 10);
                $newPlayer['ShotAccurate'] = 76 + mt_rand(0, 4);
                $newPlayer['pass'] = 78 + mt_rand(1, 10);
                $newPlayer['training_id'] = 2;
                $newPlayer['arc'] = 76 + mt_rand(0, 23);
                break;
            case 9: //lm
            case 13: //lb
                $newPlayer['beat'] = 73 + mt_rand(1, 10); 
                $newPlayer['BallControl'] = 73 + mt_rand(1, 10); 
                $newPlayer['tackle'] = 73 + mt_rand(1, 10); 
                $newPlayer['close-marking'] = 75 + mt_rand(0, 10); 
                $newPlayer['speed'] = 78 + mt_rand(1, 10); 
                $newPlayer['pass'] = 73 + mt_rand(1, 10);
                $newPlayer['LeftProperties'] = 100;
                $newPlayer['MidProperties'] = 95;
                $newPlayer['RightProperties'] = 90;
                $newPlayer['training_id'] = 6;
                break;
            case 10: //rm
            case 14: //rb
                $newPlayer['beat'] = 73 + mt_rand(1, 10); 
                $newPlayer['BallControl'] = 73 + mt_rand(1, 10); 
                $newPlayer['tackle'] = 73 + mt_rand(1, 10); 
                $newPlayer['close-marking'] = 75 + mt_rand(0, 10); 
                $newPlayer['speed'] = 78 + mt_rand(1, 10); 
                $newPlayer['pass'] = 73 + mt_rand(1, 10);
                $newPlayer['MidProperties'] = 90;
                $newPlayer['training_id'] = 6; 
                break;
            case 5: //lw
                $newPlayer['ShotDesire'] = 77 + mt_rand(1, 10);
                $newPlayer['ShotPower'] = 80 + mt_rand(1, 10);
                $newPlayer['ShotAccurate'] = 76 + mt_rand(0, 4);
                $newPlayer['speed'] = 80 + mt_rand(1, 10);
                $newPlayer['beat'] = 80 + mt_rand(1, 10);
                $newPlayer['LeftProperties'] = 100;
                $newPlayer['MidProperties'] = 90;
                $newPlayer['RightProperties'] = 95;
                $newPlayer['training_id'] = 6;
                break;
            case 6: //rw
                $newPlayer['ShotDesire'] = 77 + mt_rand(1, 10);
                $newPlayer['ShotPower'] = 80 + mt_rand(1, 10);
                $newPlayer['ShotAccurate'] = 76 + mt_rand(0, 4);
                $newPlayer['speed'] = 80 + mt_rand(1, 10);
                $newPlayer['beat'] = 80 + mt_rand(1, 10);
                $newPlayer['training_id'] = 6;
                break;
        }
            
		$this->addNew($newPlayer);
		
        /*抽调一名年轻球员减5W欧元*/
//        $this->writeJournal($team_id, 2, 5, '抽调新队员');

        return $newPlayer;
	}
    
    public function addNew($newPlayer)
    {
        self::$youngPlayers[] = $newPlayer;
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
	
	/**
	 * 获取每个位置的数量数组
	 * @param type $teamId
	 * @return int
	 */
	public function groupByPosition($teamId)
	{
		$players = $this->find('all', array(
			'conditions' => array('team_id'=>$teamId),
			'fields' => array('position_id')
		));
		
		$positionCountArr = array();
		foreach($players as $p)
		{
			if (array_key_exists($p['position_id'], $positionCountArr))
			{
				$positionCountArr[$p['position_id']]++;
			}
			else
			{
				$positionCountArr[$p['position_id']] = 1;
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
	
	public function resetTotalSalaryAndPlayerCount()
    {
        $allTeamPlayerData = array();
        $allPlayers = PlayerManager::getInstance()->find('all', array(
            'conditions' => array('team_id<>'=>0),
            'fields' => array('id', 'team_id', 'salary')
        ));
        
		$total = 0;
		$playerCount = 0;
		foreach($allPlayers as $k=>$player)
		{
			if(isset($allTeamPlayerData[$player['team_id']]))
			{
				$allTeamPlayerData[$player['team_id']]['player_count'] += 1;
				$allTeamPlayerData[$player['team_id']]['TotalSalary'] += $player['salary'];
			}
			else
			{
				$allTeamPlayerData[$player['team_id']] = array('id'=>$player['id'], 'player_count'=>1, 'TotalSalary'=>$player['salary']);
			}
		}
        
		return $allTeamPlayerData;
    }
}