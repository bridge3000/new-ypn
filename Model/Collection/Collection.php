<?php
namespace Model\Collection;

/**
 * 集合基类
 *
 * @author Administrator
 */
class Collection implements Countable
{
	protected $items = [];
	
	 //Countable
    public function count()
    {
        return count($this->items);
	}
}
