<?php
namespace Model\Core;

class Player
{
    public $id;
    public $name;
    public $alias;
    public $ShirtNo;
    public $team_id;
    public $position_id;
	public $birthday;
    
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

    public function setAlias($alias) {
        $this->alias = $alias;
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

        $fee = round(($this->estimateValue($nowDate) * $this->ClubDepending / 100 * $contractXishu / 100), -2);
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
		$myBirthdayNo = date('Y', $this->birthday);
		
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
    	$newNO = 30;
    	$canUseThisNO = true;
    	
    	if (in_array($newNO, $usedNOs, true))
    	{
    		$canUseThisNO = false;
    	}
    	
    	if (!$canUseThisNO)
    	{
    		$canUseThisNO = true;
        	switch ($this->position_id) 
        	{
	    		case 1:
		    		$newNO = 9;
		    	break;
	     		case 2:
		    		$newNO = 6;
		    	break;  
	    		case 3:
		    		$newNO = 4;
		    	break; 
	    		case 4:
		    		$newNO = 1;
		    	break; 	
	    		case 5:
		    		$newNO = 7;
		    	break;	
	    		case 6:
		    		$newNO = 8;
		    	break;
	    		case 7:
		    		$newNO = 9;
		    	break;
	    		case 8:
		    		$newNO = 10;
		    	break;
	    		case 9:
		    		$newNO = 7;
		    	break;	
	    		case 10:
		    		$newNO = 8;
		    	break;
	    		case 13:
		    		$newNO = 3;
		    	break;	
	    		case 14:
		    		$newNO = 2;
		    	break;
	    		default:
	    			$newNO = 30;
	    		break;
	    	}
	    	
        	if (in_array($newNO, $usedNOs, true))
	    	{
	    		$canUseThisNO = false;
	    	}
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
                if (in_array($newNO, $usedNOs, true))
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
	public function getRndFee($nowDate)
	{
		$price = round(($this->estimateFee($nowDate) *  (70 + mt_rand(1, 60)) / 100), -1);
		return $price;
	}
}