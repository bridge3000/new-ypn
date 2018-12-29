<?php
namespace Model\Core;

class Player extends YpnModel
{
    public $id;
	public $condition_id; //1首发 2板凳 3场外 4受伤
	
	public function getRndHeadStyle()
	{
		$headerStyles = ['头球攻门', '狮子甩头', '鱼跃冲顶', '回头望月'];
		return $headerStyles[array_rand($headerStyles)];
	}
    
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getAlias() {
        return $this->alias;
    }

    public function getShirtNo() {
        return $this->ShirtNo;
    }

    public function getTeamId() {
        return $this->team_id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setShirtNo($ShirtNo) {
        $this->ShirtNo = $ShirtNo;
    }

    public function setTeamId($teamId) {
        $this->team_id = $teamId;
    }
    
    public function getPositionId() {
        return $this->position_id;
    }

    public function setPositionId($positionId) {
        $this->position_id = $positionId;
    }

    public function getAge($nowDate)
    {
    	return intval((strtotime($nowDate) - strtotime($this->birthday)) / (3600 * 24 * 365));
    }
    
    public function getAttackPower($dirField)
    {
        $attackPower = ($this->beat + $this->pass + $this->agility + $this->scope + mt_rand(-40, 40)) / 4;
        
        if (in_array($dirField, array('LeftProperties', 'RightProperties'), true))
        {
            $attackPower += $this->speed;
            $attackPower += $this->BallControl / 2;
            $attackPower += $this->weight / $this->height * 100;
        }
        else
        {
            $attackPower += $this->speed / 2;
            $attackPower += $this->BallControl;
            $attackPower += $this->weight * 2 / $this->height * 100;
        }
        $attackPower *=  $this->cooperate / 100 * $this->creativation / 100 * $this->state / 100 * $this->$dirField / 100; 
        
        return $attackPower;
    }
    
    public function getDefensePower($dirField)
    {
        $defensePower = ($this->close_marking + $this->pinqiang + $this->scope + $this->agility + mt_rand(-40, 40)) / 4;
        
        if (in_array($dirField, array('LeftProperties', 'RightProperties'), true))
        {
            $defensePower += $this->speed;
            $defensePower += $this->tackle / 2;
            $defensePower += $this->weight / $this->height * 100;
        }
        else
        {
            $defensePower += $this->speed / 2;
            $defensePower += $this->tackle;
            $defensePower += $this->weight * 2 / $this->height * 100;
        }
        $defensePower *=  $this->cooperate / 100 * $this->creativation / 100 * $this->state / 100 * $this->$dirField / 100;
        
        return $defensePower;
    }
    
    public function getPassRate($dirField)
    {
        $rate = ($this->BallControl + $this->pass + $this->arc + 30 - mt_rand(1, 60)) * $this->state * $this->$dirField * $this->creativation;
        return $rate;
    }
    
    public function getTackleRate($dirField)
    {
        $rate = ($this->tackle + $this->close_marking + $this->pinqiang + 30 - mt_rand(1, 60)) * $this->state * $this->$dirField * $this->creativation;
        return $rate;
    }
    
    public function getShotRate()
    {
        $rate = ($this->ShotPower + $this->ShotAccurate + $this->ShotDesire + mt_rand(-30, 30)) * $this->state;
        return $rate;
    }
    
    public function getShotValue($dir)
    {
        if (in_array($dir, array(1, 3), true))
        {
            $shotValue = ($this->ShotPower + $this->ShotAccurate + $this->arc + mt_rand(-30, 30)) / 3 * $this->state / 100;
        }
        else 
        {
            $shotValue = ($this->ShotPower + $this->ShotAccurate + mt_rand(-20, 20)) / 2 * $this->state / 100;
        }
        
        return $shotValue;
    }
    
    public function getSaveValue()
    {
        $saveValue = (($this->save + $this->agility + mt_rand(-20, 20))/2 * $this->state / 100  + $this->height/2) / 2;
        return $saveValue;
    }
    
    public function getCornerValue()
    {
        $cornerValue = ($this->arc + $this->pass + mt_rand(-20, 20)) / 2 * $this->state / 100;
        return $cornerValue;
    }
    
    public function getQiangdianValue()
    {
        $qiangdianValue = ($this->qiangdian * 2 + mt_rand(-20, 20)) * $this->state / 100 + $this->height;
        return $qiangdianValue;
    }
    
    public function getHeaderValue()
    {
        $headerValue = ($this->header + mt_rand(-10, 10)) * $this->state / 100;
        return $headerValue;
    }
    
    public function estimateValue($nowDate)
    {
        $jishu = 3000;
        $dirXishu = 100 - (100 - $this->LeftProperties) - (100 - $this->MidProperties) - (100 - $this->RightProperties);
        $age = $this->getAge($nowDate);
        
        if ($age < 25)
        {
            $birthXishu = 100 - (25 - $age) * 10;
        }
        else if ($age > 28)
        {
            $birthXishu = 100 - ($age - 28) * 10;
        }
        else
        {
            $birthXishu = 100;
        }

        if ($birthXishu < 10)
        {
            $birthXishu = mt_rand(1, 10);
        }

        $playerValue = ($this->height / 2 + $this->weight + $this->ShotPower + $this->ShotAccurate + $this->header + $this->tackle + $this->BallControl + $this->speed + $this->agility + $this->pass + $this->qiangdian + $this->pinqiang + $this->arc + $this->scope + $this->beat + $this->close_marking + $this->SinewMax + $this->mind) * $jishu * $this->popular / 100 / 18 * $this->creativation / 100 / 100 * $birthXishu / 100 * $dirXishu / 100;
        return intval($playerValue);
    }
	
	public function getContractRemainMonth($nowDate)
	{
		return intval((strtotime($this->ContractEnd) - strtotime($nowDate)) / (3600 * 24 * 30));
	}
    
	public function estimateFee($nowDate)
    {
		$estimateValue = $this->estimateValue($nowDate);
		if($this->isSelling)
		{
			$contractXishu = 0;
			$monthDepart = $this->getContractRemainMonth($nowDate);
			if ($monthDepart <= 6)
			{
				$contractXishu = 0;
			}
			else if (($monthDepart < 12) && ($monthDepart>6))
			{
				$contractXishu = 100 - (12-$monthDepart) * 10;
			}
			else
			{
				$contractXishu = 100;
			}

			$fee = round(($estimateValue * $this->ClubDepending / 100 * $contractXishu / 100), -2);
		}
		else //force buy
		{
			$fee = round(($estimateValue * (100+$this->ClubDepending) / 100), -2);
		}

        return $fee;
    }
    
	public function getExpectedSalary($nowDate)
	{
    	$newSalary = round(($this->estimateValue($nowDate) * 2 * (200 - $this->loyalty) / 100 / 1000), 2);
    	return $newSalary;
	}
    
    public function getRndName()
    {
        if ($this->alias == "")
        {
            return $this->name;
        }
        else
        {
            return mt_rand(0, 1)?$this->name:$this->alias;
        }
    }
	
	/**
	 * 新入球员获得号码
	 * @param type $existTeamNos
	 * @return int
	 */
	public function setBestShirtNo($existTeamNos)
	{
		$newNo = 0;
		$positionMap = array(
			1 => 9,//'前锋',
			2 => 6,//'后腰',
			3 => 4,//'中后卫',
			4 => 1,//'门将',
			5 => 11,//'左边锋',
			6 => 17,//'右边锋',
			7 => 9,//'中锋',
			8 => 10,//'前腰',
			9 => 7,//'左前卫',
			10 => 8,//'右前卫',
			13 => 3,//'左后卫',
			14 => 2,//'右后卫'
		);
				
		$myPositionBestNo = $positionMap[$this->position_id];
		$myBirthdayNo = date('y', strtotime($this->birthday));
		
		if (!in_array($this->ShirtNo, $existTeamNos))
		{
			$newNo = $this->ShirtNo;
		}
		else if (!in_array($myPositionBestNo, $existTeamNos))
		{
			$newNo = $myPositionBestNo;
		}
		else if (!in_array($myBirthdayNo, $existTeamNos))
		{
			$newNo = $myBirthdayNo;
		}
		else
		{
			for($i=1;$i<100;$i++)
			{
				if(!in_array($i, $existTeamNos))
				{
					$newNo = $i;
					break;
				}
			}
		}

		$this->ShirtNo = $newNo;
	}
	
	public function getNewShirtNo($usedNOs)
    {
		$canUseThisNO = false;
    	$newNO = 0;
    	
		//position_id => best_no
		$bestNoMap = [
			1 => 9, 
			2 => 6,
			3 => 4,
			4 => 1,
			5 => 7,
			6 => 8,
			7 => 9,
			8 => 10,
			9 => 7,
			10 => 8,
			13 => 3,
			14 => 2
			];

		if (!in_array($bestNoMap[$this->position_id], $usedNOs))
		{
			$canUseThisNO = true;
			$newNO = $bestNoMap[$this->position_id];
		}
    	
    	/*如果相关位置的默认号码没有，则从12开始计算*/
    	if (!$canUseThisNO)
    	{
    		if ($this->position_id == 4)
    		{
    			$newNO = 12;
    		}
    		else 
    		{
    			$newNO = 13;
    		}

        	while(!$canUseThisNO)
	    	{
	    		$canUseThisNO = true;
                if (in_array($newNO, $usedNOs))
                {
                    $canUseThisNO = false;
                    $newNO++;
                    if ($newNO == 100) $newNO = 1;
                }
	    	}
    	}
		
		$this->ShirtNo = $newNO;
    	return $newNO;
    }
	
	/**
	 * 售出的时候随机转会费
	 * @param type $nowDate
	 * @return type
	 */
	public function setSelling($nowDate)
	{
		$this->isSelling = 1;
		$this->fee = round(($this->estimateFee($nowDate) *  (70 + mt_rand(1, 60)) / 100), -1);
	}
	
	public static function generateYoung($league_id, $team_id, $position_id, $firstNames, $familyNames, $countries, &$usedNOs, $nowDate, &$existPlayerNames)
	{
        $fullName = "";
        $isNameExist = true;
		$thisYear = date('Y', strtotime($nowDate));
		$newPlayer = new static();
        
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
		        
        /*获得号码，调用getNewNumber*/
        $newPlayer->position_id = $position_id;
        $newPlayer->team_id = $team_id;

        /*已经获得名字和号码开始签合同，随机生成生日、合同，角球位随机*/
        $newPlayer->name = $fullName;
        $newPlayer->league_id = $league_id;
        $newPlayer->country_id = $countries[5]['id'];
		$newPlayer->country = $countries[5]['title'];
        $newPlayer->CornerPosition_id = mt_rand(1, 4);
        $newPlayer->birthday = ($thisYear - mt_rand(16, 20)) . "-" . mt_rand(1, 12) . "-" . mt_rand(1,28);
        $newPlayer->ContractBegin = $nowDate;
        $newPlayer->ContractEnd = ($thisYear+mt_rand(1,5)) . "-6-30";

        $newPlayer->creativation = 73 + mt_rand(1, 10);
        $newPlayer->pass = 70 + mt_rand(1, 10);
        $newPlayer->speed = 75 + mt_rand(1, 10);
        $newPlayer->ShotDesire = 75 + mt_rand(0, 6);
        $newPlayer->ShotPower = 78 + mt_rand(0, 21);
        $newPlayer->ShotAccurate = 74 + mt_rand(0, 4);
        $newPlayer->agility = 75 + mt_rand(1, 10);
        $newPlayer->SinewMax = 78 + mt_rand(0, 19);
        $newPlayer->cooperate = 80;
        $newPlayer->ShirtNo = $newPlayer->getNewShirtNo($usedNOs);
        $newPlayer->arc = 73 + mt_rand(0, 10);
		$newPlayer->potential = mt_rand(20, 50);
        
        /*根据不同位置获得不同的训练方式*/
        switch ($position_id)
        {
            case 1: //forward
                $newPlayer->ShotDesire = 80 + mt_rand(1, 10);
                $newPlayer->ShotPower = 80 + mt_rand(1, 10);
                $newPlayer->ShotAccurate = 76 + mt_rand(1, 5);
                $newPlayer->qiangdian = 70 + mt_rand(1, 10);
                $newPlayer->training_id = 1;
                break;
            case 2://dm
                $newPlayer->tackle = 76 + mt_rand(1, 10); 
                $newPlayer->pinqiang = 76 + mt_rand(1, 10); 
                $newPlayer->scope = 70 + mt_rand(1, 10); 
                $newPlayer->close_marking = 75 + mt_rand(0, 10); 
                $newPlayer->training_id = 3;
                break;
            case 3: //cb
                $newPlayer->tackle = 73 + mt_rand(1, 10); 
                $newPlayer->header = 74 + mt_rand(1, 10); 
                $newPlayer->height = 185 + mt_rand(1, 10); 
                $newPlayer->weight = 75 + mt_rand(1, 10); 
                $newPlayer->close_marking = 75 + mt_rand(0, 10); 
                $newPlayer->training_id = 3;
                break;
            case 4://gk
                $newPlayer->ShotDesire = 30;
                $newPlayer->save = 78 + mt_rand(1, 5);
                $newPlayer->BallControl = 74 + mt_rand(1, 10);
                $newPlayer->height = 185 + mt_rand(1, 10);
                $newPlayer->weight = 75 + mt_rand(1, 10); 
                $newPlayer->training_id = 7;
                break;
            case 7: //cf
                $newPlayer->ShotDesire = 80 + mt_rand(1, 10);
                $newPlayer->ShotPower = 80 + mt_rand(1, 10);
                $newPlayer->ShotAccurate = 70 + mt_rand(1, 10);
                $newPlayer->header = 78 + mt_rand(1, 10);
                $newPlayer->qiangdian = 74 + mt_rand(1, 10);
                $newPlayer->height = 185 + mt_rand(1, 10);
                $newPlayer->weight = 75 + mt_rand(1, 10); 
                $newPlayer->training_id = 4;
                break;
            case 8: //am
                $newPlayer->ShotDesire = 73 + mt_rand(1, 10);
                $newPlayer->ShotPower = 80 + mt_rand(1, 10);
                $newPlayer->ShotAccurate = 76 + mt_rand(0, 4);
                $newPlayer->pass = 78 + mt_rand(1, 10);
                $newPlayer->training_id = 2;
                $newPlayer->arc = 76 + mt_rand(0, 23);
                break;
            case 9: //lm
            case 13: //lb
                $newPlayer->beat = 73 + mt_rand(1, 7); 
                $newPlayer->BallControl = 73 + mt_rand(1, 7); 
                $newPlayer->tackle = 73 + mt_rand(1, 10); 
                $newPlayer->close_marking = 75 + mt_rand(0, 5); 
                $newPlayer->speed = 78 + mt_rand(1, 10); 
                $newPlayer->pass = 73 + mt_rand(1, 7);
                $newPlayer->LeftProperties = 100;
                $newPlayer->MidProperties = 95;
                $newPlayer->RightProperties = 90;
                $newPlayer->training_id = 6;
                break;
            case 10: //rm
            case 14: //rb
                $newPlayer->beat = 73 + mt_rand(1, 7); 
                $newPlayer->BallControl = 73 + mt_rand(1, 7); 
                $newPlayer->tackle = 73 + mt_rand(1, 10); 
                $newPlayer->close_marking = 75 + mt_rand(0, 5); 
                $newPlayer->speed = 78 + mt_rand(1, 10); 
                $newPlayer->pass = 73 + mt_rand(1, 7);
                $newPlayer->MidProperties = 90;
                $newPlayer->training_id = 6; 
                break;
            case 5: //lw
                $newPlayer->ShotDesire = 77 + mt_rand(1, 10);
                $newPlayer->ShotPower = 80 + mt_rand(1, 10);
                $newPlayer->ShotAccurate = 76 + mt_rand(0, 4);
                $newPlayer->speed = 80 + mt_rand(1, 10);
                $newPlayer->beat = 70 + mt_rand(1, 10);
                $newPlayer->LeftProperties = 100;
                $newPlayer->MidProperties = 90;
                $newPlayer->RightProperties = 95;
                $newPlayer->training_id = 6;
                break;
            case 6: //rw
                $newPlayer->ShotDesire = 77 + mt_rand(1, 10);
                $newPlayer->ShotPower = 80 + mt_rand(1, 10);
                $newPlayer->ShotAccurate = 76 + mt_rand(0, 4);
                $newPlayer->speed = 80 + mt_rand(1, 10);
                $newPlayer->beat = 70 + mt_rand(1, 10);
                $newPlayer->training_id = 6;
                break;
        }
		
		return $newPlayer;
	}
	
	public function addGoal($matchClassId)
	{
		$goalField = '';
		if(in_array($matchClassId, array(1,31))) //league
		{
			$goalField = 'Goal1Count';
		}
		else
		{
			$goalField = 'Goal2Count';
		}
		$this->$goalField++;
		$this->ShotAccurateExperience += 2;
		$this->score += 4;
	}
	
	public function onSaved($matchClassId)
	{
		$this->SaveExperience += 2;
		$this->score += 2;
	}
	
	public function onGoaled($matchClassId)
	{
		$this->SaveExperience += 1;
		if($this->score > 0)
		{
			$this->score -= 1;
		}
	}
	
	public function addAssist($matchClassId)
	{
		$assistField = '';
		if(in_array($matchClassId, array(1,31))) //league
		{
			$assistField = 'Assist1Count';
		}
		else
		{
			$assistField = 'Assist2Count';
		}
		$this->$assistField++;
		$this->PassExperience += 2;
		$this->score += 2;
	}
	
	public function addTackle($matchClassId)
	{
		$tackleField = '';
		if(in_array($matchClassId, array(1,31))) //league
		{
			$tackleField = 'Tackle1Count';
		}
		else
		{
			$tackleField = 'Tackle2Count';
		}
		$this->$tackleField++;
		$this->TackleExperience += 1;
		$this->score += 1;
	}
	
	/**
	 * 
	 * @param type $matchClassId
	 * @return int $result 0无事 1yellow 2yellow out 3red out
	 */
	public function foul($matchClassId)
	{
		$result = 0;
		$yellowField = '';
		$redField = '';
		$punishField = '';
		if(in_array($matchClassId, array(1,31))) //league
		{
			$yellowField = 'YellowCard1Count';
			$redField = 'RedCard1Count';
			$punishField = 'Punish1Count';
		}
		else
		{
			$yellowField = 'YellowCard2Count';
			$redField = 'RedCard2Count';
			$punishField = 'Punish2Count';
		}
		
		if(mt_rand(1, 5) == 1) //yellow
		{
			$this->yellow_today++; 
			$this->$yellowField++;
			if($this->yellow_today == 2)
			{
				$this->$punishField = 1;
			}
			else if($this->$yellowField % 4 == 0)
			{
				$this->$punishField = 1;
			}
		}
		else if(mt_rand(1, 10) == 1) //red
		{
			$this->$redField++;
			$this->$punishField = 1;
		}
		return $result;
	}
	
	public function getPenaltyWeight()
	{
		return $this->ShotAccurate + $this->mind;
	}
	
	public function getPenaltyValue()
	{
		return $this->creativation + $this->ShotAccurate + $this->mind + mt_rand(1,10);
	}
	
	public function getPenaltySaveValue()
	{
		return $this->creativation + $this->save + $this->mind + mt_rand(1,10);
	}
	
	public function addPenalty($matchClassId)
	{
		$penaltyField = '';
		if(in_array($matchClassId, array(1,31))) //league
		{
			$penaltyField = 'Penalty1Count';
		}
		else
		{
			$penaltyField = 'Penalty2Count';
		}
		$this->$penaltyField++;
	}
	
	public function getFreeWeight()
	{
		return $this->ShotAccurate + $this->arc + $this->ShotPower;
	}
	
	public function getFreeValue()
	{
		return $this->ShotPower + $this->ShotAccurate + $this->arc + mt_rand(1,10);
	}
	
	public function getFreeSaveValue()
	{
		return $this->height/2 + $this->save + $this->agility + mt_rand(1,10);
	}
	
	/**
	 * 
	 * @return int 0
	 */
	public function getTransferType($nowDate)
	{
		$date = new DateTime($this->ContractEnd);
		$date->add(new DateInterval('P6M'));
		$next6MDay = $date->format('Y-m-d');

		$transferType = 0;
		if ($this->isSelling)
		{
			$transferType = 1;
		}
		else if($this->team_id == 0)
		{
			$transferType = 2;
		}
		else if($next6MDay > $nowDate)
		{
			$transferType = 3;
		}
		
		return $transferType;
	}
	
	public function getBestPosition()
	{
		$bestPositionId = 2;
		if($this->save >= 78)
		{
			$bestPositionId = 4;
		}
		elseif( ($this->MidProperties == 100) && ($this->ShotDesire>=82) )
		{
			if($this->height/2+$this->header > $this->speed+$this->beat)
			{
				$bestPositionId = 7;
			}
			else
			{
				$bestPositionId = 1;
			}
		}
		elseif(  ($this->LeftProperties == 100) ) //优先左侧
		{
			if($this->ShotDesire >= 81)
			{
				$bestPositionId = 5;
			}
			else
			{
				if($this->tackle > $this->beat)
				{
					$bestPositionId = 13;
				}
				else
				{
					$bestPositionId = 9;
				}
			}
		}
		elseif(  ($this->MidProperties == 100) )
		{
			if($this->tackle > $this->ShotAccurate)
			{
				if( ($this->height/2+$this->qiangdian) > ($this->pinqiang+$this->scope) )
				{
					$bestPositionId = 3;
				}
				else
				{
					$bestPositionId = 2;
				}
			}
			else
			{
				$bestPositionId = 8;
			}
		}
		elseif(  ($this->RightProperties == 100) )
		{
			if($this->ShotDesire >= 81)
			{
				$bestPositionId = 6;
			}
			else
			{
				if($this->tackle > $this->beat)
				{
					$bestPositionId = 14;
				}
				else
				{
					$bestPositionId = 10;
				}
			}
		}
		
		return $bestPositionId;
	}
	
	/**
	 * 更改升级状态，最后allplayer一并修改数据库
	 * @param type $playerData 单个player数据
	 * @param type $trainingList 
	 */
    public function changeTrainingState($trainings ,$nowDate)
	{
		switch ($this->position_id) 
		{
			case 1:
				$positionTrainingIds = array(1, 9, 4, 6, 2, 5);
				break;
			case 2:
				$positionTrainingIds = array(3, 5, 2, 1, 8);
				break;
			case 3:
				$positionTrainingIds = array(9, 3, 4, 2, 5);
				break;
			case 4:
				$positionTrainingIds = array(7, 5, 9, 3, 4);
				break;
			case 5:
			case 6:
				$positionTrainingIds = array(5, 1, 2, 3);
				break;	
			case 7:
				$positionTrainingIds = array(4, 1, 9, 5, 6);
				break;	
			case 8:
				$positionTrainingIds = array(2, 5, 1, 6, 3);
				break;
			case 9:
			case 10:
				$positionTrainingIds = array(6, 3, 2, 1);
				break;	
			case 13:
			case 14:
				$positionTrainingIds = array(3, 6, 9, 2);
				break;						
		}

		$playerAge = $this->getAge($nowDate);
		if ( ($playerAge > 30) && ($this->training_id != 8) )
		{
			$this->training_id = 8;
		}
		else
		{
	        foreach($positionTrainingIds as $positionTrainingId)
            {
				$skill = $trainings[$positionTrainingId]['skill'];
				if( ($this->$skill < 85) && ($this->training_id != $positionTrainingId) )
				{   
					$this->training_id = $positionTrainingId;
					break;
				}
        	}
		}
		
	}
	
	public function transfer($teamId, $newSalary, $oldLeagueId, $newLeagueId, $nowDate, $years)
	{
		$this->cooperate = ($oldLeagueId==$newLeagueId) ? 90 : 80;
		$this->league_id = $newLeagueId;
		$this->team_id = $teamId;
		$this->ClubDepending = 80;
		$this->loyalty = 80;
		$this->salary = $newSalary;
		$this->ContractBegin = $nowDate;
		$this->ContractEnd = date('Y', strtotime($nowDate))+$years . "-6-30";
		$this->isSelling = 0;
		$this->liquidated_damage = mt_rand(2,10) * $this->estimateValue($nowDate);
	}
}