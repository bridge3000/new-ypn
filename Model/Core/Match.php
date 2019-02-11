<?php
namespace Model\Core;

use Model\Collection\PlayerCollection;

class Match extends YpnModel
{
	protected $table = 'matches';
    private $faqiuquan = 1;
	static $cornerPositions = array(
        1 => '前点',
        2 => '中点',
        3 => '后点',
        4 => '禁区外'
    );
    
    public function turnFaqiuquan()
    {
        $this->faqiuquan = !$this->faqiuquan;
    }
    
    public function getFaqiuquan()
    {
        return $this->faqiuquan;
    }
    
    public function saveGoal()
    {
        if ($this->getFaqiuquan())
        {
            $this->HostGoals++;
        }
        else
        {
            $this->GuestGoals++;
        }
    }
    
    public function getMatchField()
	{
		switch ($this->class_id)
		{
			case 1:
			case 31:
				$data['fieldRedCard'] = "RedCard1Count";
				$data['fieldYellowCard'] = "YellowCard1Count";
				$data['fieldPunish'] = "Punish1Count";
				$data['fieldTackle'] = "Tackle1Count";
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
				$data['fieldRedCard'] = "RedCard3Count";
				$data['fieldYellowCard'] = "YellowCard3Count";
				$data['fieldPunish'] = "Punish3Count";
				$data['fieldTackle'] = "Tackle3Count";
				break;
			default:
				$data['fieldRedCard'] = "RedCard2Count";
				$data['fieldYellowCard'] = "YellowCard2Count";
				$data['fieldPunish'] = "Punish2Count";
				$data['fieldTackle'] = "Tackle2Count";
				break;
		}	

		return $data;
	}
	
	public static function create($hostTeam_id, $guestTeam_id, $nowDate, $classId, $isHostPark)
	{
        $newMatch = new static();
        $newMatch->HostTeam_id = $hostTeam_id;
		$newMatch->GuestTeam_id = $guestTeam_id;
		$newMatch->PlayTime = $nowDate;
		$newMatch->class_id = $classId;
		$newMatch->is_host_park = $isHostPark;
		$newMatch->save();
	}
	
	public function save()
	{
		unset($this->hostTeam);
		unset($this->guestTeam);
		unset($this->hostPlayers);
		unset($this->guestPlayers);
		unset($this->hostShoufaCollection);
		unset($this->hostBandengCollection);
		unset($this->guestShoufaCollection);
		unset($this->guestBandengCollection);
			
		parent::save();
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
	
	public function setShoufa($players, $isHostTeam)
    {
		$matchClassId = $this->class_id;
		$punishField = $this->getPunishFieldByMatchClassId($matchClassId);
		
		$curTeam = $isHostTeam ? $this->hostTeam : $this->guestTeam;
		
		if($curTeam->is_auto_format)
		{
			$this->autoSetShoufa($players, $matchClassId, $curTeam->formattion, $punishField);
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
        
		$matchPlayers['bandeng'] = [];
        foreach($players as $player)
        {
			$player->score = 0;
			$player->yellow_today = 0;
            if ($player->condition_id == 1)
            {
                $matchPlayers['shoufa'][] = $player;
            }
            else if ($player->condition_id == 2)
            {
                $matchPlayers['bandeng'][] = $player;
            }
			
			/*主队加成*/
			if($isHostTeam && $this->is_host_park) 
			{
				$player->state += 5;
			}
        }
		
		if($isHostTeam) //是主队
		{
			$this->hostPlayers = $matchPlayers; //数组是为了兼容老代码，以后都用集合
			$this->hostShoufaCollection = new PlayerCollection($matchPlayers['shoufa']);
			$this->hostBandengCollection = new PlayerCollection($matchPlayers['bandeng']);
		}
		else //是客队
		{
			$this->guestPlayers = $matchPlayers;
			$this->guestShoufaCollection = new PlayerCollection($matchPlayers['shoufa']);
			$this->guestBandengCollection = new PlayerCollection($matchPlayers['bandeng']);
		}
        
    }
	
	private function autoSetShoufa(&$players, $matchClassId, $formattion, $punishField)
	{
		foreach($players as &$player)
        {
            if (in_array($player->condition_id, array(1, 2)))
            {
                $player->condition_id = 3;
            }
        }
		
        /*公共的 门将 左右前卫 左右后卫*/
		$this->online2(4, $punishField, $players, $matchClassId); //优先门将

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
		
		$this->online2(13, $punishField, $players, $matchClassId);
        $this->online2(14, $punishField, $players, $matchClassId);
        $this->online2(9, $punishField, $players, $matchClassId);
        $this->online2(10, $punishField, $players, $matchClassId);

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
}