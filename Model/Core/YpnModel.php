<?php
namespace Model\Core;

class YpnModel extends \Model\Manager\DataManager {

    public $table = '';
    public $id = 0;
    
    public function __construct()
    {
		if(!$this->table)
		{
			$arr = explode("\\", static::class);
			$this->table = $this->humpToLine(lcfirst($arr[count($arr)-1])).'s';
		}
    }
	
	private function humpToLine($str){
		$str = preg_replace_callback('/([A-Z]{1})/',function($matches){
			return '_'.strtolower($matches[0]);
		},$str);
		return $str;
	}
	
	public function save()
    {
		$this->saveModel($this);
    }

    
    /**
     * 把stdClass转换成当前model
     * @param stdClass $obj
     * @return \static
     */
    protected function objRevert2Model(stdClass $obj)
    {
		$obj2 = new static();
		foreach($obj as $k=>$v)
		{
			$obj2->$k = $v;
		}
		return $obj2;
    }
}