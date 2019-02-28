<?php
namespace Model\Collection;

use Model\Core\Player;

/**
 * Description of PlayerCollection
 *
 * @author Administrator
 */
class PlayerCollection extends Collection
{
	public function popRndPlayer()
	{
		if(empty($this->items))
		{
			return;
		}
		else
		{
			$rndIndex = array_rand($this->items);
			$rndPlayer = $this->items[$rndIndex];
			array_splice($this->items, $rndIndex, 1);
			return $rndPlayer;
		}
	}
	
	/**
	 * 组建防守反击集合
	 * @param type $players
	 */
	public function loadQuickCollection()
	{
		$newItems = [];
		foreach($this->items as $player)
		{
			if( ($player->CornerPosition_id == 4) && ($player->position_id != 4) )
			{
				$newItems[] = $player;
			}
		}
		
		shuffle($newItems);
		
		return new static($newItems);
	}
	
//	public static function findGoalkeeper($players)
//	{
//		foreach($players as $player)
//		{
//			if($player->position_id == 4)
//			{
//				return $player;
//			}
//		}
//	}
	
	public function getLongShoter()
	{
		$max = 0;
		$shoter = NULL;
		foreach($this->items as $player)
		{
			$longShotDesire = $player->getLongShotDesire();
			if($longShotDesire > $max)
			{
				$max = $longShotDesire;
				$shoter = $player;
			}
		}
		
		return $shoter;
	}
	
	public function getGoalkeeper()
	{
		$goalkeeper = NULL;
		foreach($this->items as $player)
		{
			if($player->position_id == 4)
			{
				$goalkeeper = $player;
				break;
			}
		}
		
		return $goalkeeper;
	}
	
	/**
	 * 
	 * @param type $attackDir 进攻端视角的方向
	 * @param type $isAttack
	 * @return \Model\Collection\PlayerCollection
	 */
	public function getChildren($attackDir, $isAttack)
	{
		$childCollection = new PlayerCollection;
		switch ($attackDir)
		{
			case 1: //左
				$attackPoses = $isAttack ? [1, 5, 8, 9, 13] : [2, 10, 14];
				break;
			case 2: //中
				$attackPoses = $isAttack ? [1, 2, 7, 8] : [2, 3, 8];
				break;
			case 3: //右
				$attackPoses = $isAttack ? [1, 6, 8, 10, 14] : [2, 9, 13];
				break;
		}
		
		foreach($this as $player)
		{
			if(in_array($player->position_id, $attackPoses))
			{
				$childCollection->push($player);
			}
		}
		
		return $childCollection;
	}
	
	public function getQiangdianPlayer($attackDir, $isHigh, $attackTeamId)
	{
		$max = 0;
		$qiangdianValue = 0;
		$qiangdianPlayer = NULL;
		
		$attackPoses = [];
		$defensePoses = [];
		if($attackDir == 1)
		{
			$attackPoses = [1,2,3,6,7,8,10,14];
			$defensePoses = [2,3,13];
		}
		elseif($attackDir == 3)
		{
			$attackPoses = [1,2,3,5,7,8,9,13];
			$defensePoses = [2,3,14];
		}
		
		foreach($this as $player)
		{
			if( ($player->team_id == $attackTeamId) && in_array($player->position_id, $attackPoses))
			{
				if($player->ShotDesire + mt_rand(-10, 10) > 80)
				{
					$qiangdianValue = $player->getQiangdianValue($isHigh);
					if($qiangdianValue > $max)
					{
						$max = $qiangdianValue;
						$qiangdianPlayer = $player;
					}
				}
			}
			elseif( ($player->team_id != $attackTeamId) && in_array($player->position_id, $defensePoses))
			{
				$qiangdianValue = $player->getQiangdianValue($isHigh);
				if($qiangdianValue > $max)
				{
					$max = $qiangdianValue;
					$qiangdianPlayer = $player;
				}
			}
		}
		
		return $qiangdianPlayer;
	}
	
	public function getCornerKicker($cornerKickerId)
	{
		$cornerKicker = NULL;
		
		foreach($this as $player)
		{
			if($player->id == $cornerKickerId)
			{
				$cornerKicker = $player;
				break;
			}
		}
        
        if (!$cornerKicker)
        {
            $max = 0;
            foreach($this as $player)
            {
                $cornerValue = $player->getCornerValue();
                if ($cornerValue > $max)
                {
                    $max = $cornerValue;
                    $cornerKicker = $player;
                }
            }
        }
        
        return $cornerKicker;
	}
	
	/**
	 * 角球位子集
	 * @param type $cornerPositionId
	 * @return \Model\Collection\PlayerCollection
	 */
	public function getChildrenByCornerPosition($cornerPositionId)
	{
		$children = new PlayerCollection();
		foreach($this as $player)
		{
			if($player->CornerPosition_id == $cornerPositionId)
			{
				$children->push($player);
			}
		}
		
		return $children;
	}
	
	public function autoSetShoufa($matchClassId, $formattion, $punishField)
	{
		foreach($this->items as &$player)
        {
            if (in_array($player->condition_id, array(1, 2)))
            {
                $player->condition_id = 3;
            }
        }
		
        /*公共的 门将 左右前卫 左右后卫*/
		$positions = [4]; //优先门将

        /*根据阵型判断*/
        switch ($formattion) 
        {
        	case "4-4-2":
				$positions[] = 8;
				$positions[] = 2;
				$positions[] = 1;
				$positions[] = 1;
				$positions[] = 3;
				$positions[] = 3;
        		break;
        	case "3-5-2":
            case "5-3-2":
				$positions[] = 2;
				$positions[] = 2;
				$positions[] = 8;
				$positions[] = 1;
				$positions[] = 1;
				$positions[] = 3;
            	break;
        	case "3-4-3":
				$positions[] = 2;
				$positions[] = 2;
				$positions[] = 5;
				$positions[] = 6;
				$positions[] = 7;
				$positions[] = 3;
				break;
            case "4-3-3":
				$positions[] = 2;
				$positions[] = 5;
				$positions[] = 6;
				$positions[] = 7;
				$positions[] = 3;
				$positions[] = 3;
            	break;
            case "4-5-1":
				$positions[] = 2;
				$positions[] = 2;
				$positions[] = 8;
				$positions[] = 1;
				$positions[] = 3;
				$positions[] = 3;
            	break;
			case "5-4-1":
				$positions[] = 2;
				$positions[] = 2;
				$positions[] = 3;
				$positions[] = 1;
				$positions[] = 3;
				$positions[] = 3;
            	break;
            case "圣诞树":
				$positions[] = 2;
				$positions[] = 8;
				$positions[] = 8;
				$positions[] = 1;
				$positions[] = 3;
				$positions[] = 3;
	            break;
        }
		
		$positions[] = 13;
		$positions[] = 14;
		$positions[] = 9;
		$positions[] = 10;
		
		foreach($positions as $positionId)
		{
			$this->setOnline($positionId, $punishField, $matchClassId);
		}

        /*设置5个替补队员*/
        $j = 0;
        $isGetKeeper = false;

        foreach($this->items as $curPlayer)
        {
        	if ( ($curPlayer->condition_id == 3) && ($curPlayer->position_id == 4)  && ($curPlayer->$punishField == 0) && !$isGetKeeper)
        	{
        		$curPlayer->condition_id = 2;
        		$isGetKeeper = true;
        	}
        	
        	if ( ($curPlayer->condition_id == 3) && ($curPlayer->position_id != 4) && ($curPlayer->$punishField == 0) && ($j < 4) )
        	{
        		$curPlayer->condition_id = 2;
        		$j++;
        	}
                	
        	if ($j == 4 && $isGetKeeper) break;
        }
	}
	
	private function setOnline($positionId, $punish, $matchClassId)
    {		        
		$players = $this->items;
		//如果是友谊赛，遍历所有球员，状态*磨合度低的球员上场
		if ($matchClassId == 24)
		{
			$minPower = 10000;
			$onlineIndex = -1;
			
			for ($i = 0;$i < count($players);$i++)
			{
				if ( ($players[$i]->condition_id == 3) && $players[$i]->hasEnoughSinew() && ($players[$i]->position_id == $positionId) )
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
        switch ($positionId)
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
        	if ( ($players[$i]->condition_id==3) && ($players[$i]->$punish==0) && $players[$i]->hasEnoughSinew())
        	{
	        	switch ($positionId)
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
        $players[$bestIndex]->position_id = $positionId;
    }
}