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
		$max = 0;
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
	
	public function getChildren($attackDir)
	{
		
	}
}
