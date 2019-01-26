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
}
