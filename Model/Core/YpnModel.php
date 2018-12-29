<?php
namespace Model\Core;

use \MainConfig;
use \Model\Manager\DBManager;

class YpnModel
{
    protected $table = '';
    public $id = 0;
    
    public function __construct()
    {
    }
	
	public function getTable()
	{
		if(!$this->table)
		{
			$arr = explode("\\", static::class);
			$this->table = self::humpToLine(lcfirst($arr[count($arr)-1])).'s';
		}
		
		return $this->table;
	}
	
	private static function humpToLine($str){
		$str = preg_replace_callback('/([A-Z]{1})/',function($matches){
			return '_'.strtolower($matches[0]);
		},$str);
		return $str;
	}
	
	public function save()
    {
		$saveType = $this->getSaveType($this);
        $executeResult = DBManager::getInstance()->execute($this->generateSaveSql($this, $saveType));
		
		if($saveType == 'INSERT')
		{
			$this->id = DBManager::getInstance()->getInsertId();
		}
		
		return $executeResult;
    }
	
	private function getSaveType($obj)
	{
		$type = '';
		if (isset($obj->id) && $obj->id)
		{
			$type = 'UPDATE';
		}
		else
		{
			$type = 'INSERT';
		}
		return $type;
	}

	private function generateSaveSql($obj, $type)
    {
        $sql = '';

        if ($type === 'INSERT')
        {
            $keys = array();
            $values = array();
            foreach($obj as $k=>$v)
            {
				if($k<>'table')
				{
					$keys[] = "`$k`";
					$v = str_replace("'", "''", $v);
					$values[] = "'$v'";
				}
            }

            $sql = 'INSERT into ' . MainConfig::PREFIX . $this->getTable() . '(' . implode(",", $keys)  . ') values(' . implode(",", $values) . ')';
        }
        else
        {
            $sql = 'UPDATE ' . MainConfig::PREFIX . $this->getTable() . ' SET ';
            $arr = array();
            foreach($obj as $k => $v)
            {
				if (!in_array($k, ['id', 'table']))
				{
					$v = str_replace("'", "''", $v);
					self::explainFieldValue($v);
					$arr[] = $k . '=' . $v;
				}
            }

            $sql .= implode(",", $arr);
			if (is_array($obj))
			{
				$sql .= ' WHERE id=' . $obj['id'];
			}
			else
			{
				$sql .= ' WHERE id=' . $obj->id;
			}
        }

        return $sql;
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
	
	public static function getById($id)
    {
        $options['conditions'] = array('id'=>$id);
        $data = self::findArray('first', $options);
		$obj = self::loadOne($data);
        return $obj;
    }
	
	public static function findArray($type, $option = array())
    {
		$data = NULL;
        $fields = array();
        if (array_key_exists('fields', $option))
        {
            $fields = $option['fields'];
        }
		
		if ($type == 'count')
		{
			$fields = "count(*) as count";
		}
		elseif ($type == 'sum')
		{
			$fields = "sum({$fields[0]}) as field_sum";
		}
        else if (empty($fields))
        {
            $fields = "*";
        }
        else
        {
            $fields = implode(",", $fields);
        }
		
		$table = (new static)->getTable();
        
        $sql = "SELECT $fields FROM " . MainConfig::PREFIX . $table . " WHERE 1=1 ";
        
        if (array_key_exists('conditions', $option))
        {
            $sql .= self::explainCondition($option['conditions']);
        }
        
        if (array_key_exists('order', $option))
        {
            $sql .= " order by ";
            
            foreach ($option['order'] as $k=>$v)
            {
                $sql .= $k . ' ' . $v . ',';
            }
            $sql = substr($sql, 0, strlen($sql)-1);
        }
        
        if (array_key_exists('limit', $option))
        {
			if (is_array($option['limit']))
			{
				$sql .= " limit " . $option['limit'][0] . ',' . $option['limit'][1];
			}
			else
			{
				$sql .= " limit " . $option['limit'];
			}
        }
		
        if ($type == "all")
        {
            $data = DBManager::getInstance()->fetch($sql);
        }
        else if ($type == "first")
        {
            $data = DBManager::getInstance()->fetch($sql);

            if ($data != null)
            {
                $data = $data[0];
            }
        }
        else if ($type == "list")
        {
            $data = DBManager::getInstance()->fetchList($sql);
        }
		else if ($type == "count")
        {
            $data = DBManager::getInstance()->fetch($sql);
			$data = $data[0]['count'];
        }
		else if ($type == "sum")
        {
            $data = DBManager::getInstance()->fetch($sql);
			$data = $data[0]['field_sum'];
        }
        return $data;
    }
	
	public static function find($type, $option = array())
	{
		$data = self::findArray($type, $option);
		if($type == 'first')
		{
			return self::loadOne($data);
		}
		elseif($type == 'all')
		{
			$objs = [];
			foreach($data as $a)
			{
				$objs[] = self::loadOne($a);
			}
			return $objs;
		}
		else
		{
			return $data;
		}
	}
	
	private static function explainCondition($conditions)
    {
        $sql = '';
        foreach($conditions as $k=>$v)
        {
            $k = strtolower($k);
            if ($k == 'or')
            {
                $orStr = " and (1=2 ";
                foreach($conditions['or'] as $k1 => $v1)
                {
                    $orStr .= ' or ' . self::explainValue($k1, $v1);
                }

                $orStr .= ") ";
                $sql .= $orStr;
            }
            else if ($k == 'not')
            {
                $k1 = array_keys($v);
                
                $str = ' and ' . $k1[0] . ' not in (' . implode(",", $v[$k1[0]]) . ') ';
                $sql .= $str;
            }
            else
            {
                $sql .= ' and ' . self::explainValue($k, $v);
            }
        }
        
        return $sql;
    }
	
	private static function loadOne($arr)
    {
		$newInstance = NULL;
		if($arr)
		{
			$newInstance = new static();
			foreach($arr as $k=>$v)
			{
				$k = str_replace("-", "_", $k);

				$newInstance->$k = $v;
			}
		}
        return $newInstance;
    }
	
	private static function explainValue($k, $v)
    {
        $str = '';
        
        if (is_array($v) && (!empty($v)))
        {
            $str = $k . ' IN (' . implode(",", $v) . ') ';
        }
        else
        {
            if (strpos($k, '<') || strpos($k, '>') || strpos($k, '<>'))
            {
                $str = $k . '"' . $v . '" ';
            }
            else if (strpos($k, 'like') != false)
            {
                $str = $k . '"' . $v . '"';
            }
            else
            {
                $str = $k . '=' . '"' . $v . '"';
            }
        }
        
        return $str;
    }
	
	private static function explainFieldValue(&$v)
    {
        $hasMath = false;
        $yunsuanfu = array('+', '-', '*');
        foreach($yunsuanfu as $oper)
        {
            $yIndex = strpos($v, $oper);
            if ($yIndex != false)
            {
                $data = explode($oper, $v);
                {
                    if (!is_numeric($data[0]) && is_numeric($data[1]))
                    {
                        $hasMath = true;
                    }
                }
                break;
            }
        }
        
        if (!$hasMath)
        {
            $v = "'" . $v . "'";
        }
    }
	
	public function delete()
	{
		$sql = "DELETE FROM " . MainConfig::PREFIX . "{$this->getTable()} WHERE id={$this->id}";
		DBManager::getInstance()->execute($sql);
	}
}