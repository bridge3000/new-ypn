<?php
namespace Model\Collection;

use Countable;

/**
 * 集合基类
 *
 * @author Administrator
 */
class Collection implements Countable
{
	protected $items = [];
	
	public function __construct($items=[])
	{
		$this->items = $items;
	}
	
	 //Countable
    public function count()
    {
        return count($this->items);
	}
}
