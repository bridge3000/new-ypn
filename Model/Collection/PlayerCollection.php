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
	public function loadQuickCollection($players)
	{
		foreach($players as $player)
		{
			if( ($player->CornerPosition_id == 4) && ($player->position_id != 4) )
			{
				$this->items[] = $player;
			}
		}
		
		shuffle($this->items);
	}
	
	public static function findGoalkeeper($players)
	{
		foreach($players as $player)
		{
			if($player->position_id == 4)
			{
				return $player;
			}
		}
	}
	
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
}