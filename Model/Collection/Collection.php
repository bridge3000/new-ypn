<?php
namespace Model\Collection;

use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * 集合基类
 *
 * @author Administrator
 */
class Collection implements Countable, IteratorAggregate 
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
	
	public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
	
	public function push($obj)
	{
		array_push($this->items, $obj);
	}
	
	public function merge($secondCollection)
    {
        return new static(array_merge($this->items, $secondCollection->items));
    }

	public function toArray()
	{
		return $this->items;
	}
	
	public function remove($obj)
	{
		foreach($this as $k=>$item)
		{
			if($obj === $item)
			{
				array_splice($this->items, $k, 1);
				break;
			}
		}
	}
}
