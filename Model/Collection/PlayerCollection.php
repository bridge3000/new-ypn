<?php
namespace Model\Collection;

/**
 * Description of PlayerCollection
 *
 * @author Administrator
 */
class PlayerCollection extends \Model\Collection
{
	public function loadData($playerArray)
	{
		$this->data = $playerArray;
	}
	
	public function popRndPlayer()
	{
		$rndIndex = array_rand($this->data);
		$rndPlayer = $this->data[$rndIndex];
		array_splice($this->data, $rndIndex, 1);
		return $rndPlayer;
	}
}
