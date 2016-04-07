<?php
namespace Model\Manager;
use MainConfig;
use Model\Core\Player;
use Util\CommonUtil;

class DataManager 
{
    public $table = "";
    
    public static function getInstance()
	{
        static $aoInstance = array(); 
        $calledClassName = get_called_class(); 
        
        if (! isset ($aoInstance[$calledClassName])) { 
            $aoInstance[$calledClassName] = new $calledClassName(); 
        } 
        return $aoInstance[$calledClassName]; 
	}
    
    public function query($sql)
    {
        $tmp = explode(" ", $sql);
        
        if ($tmp[0] == 'select')
        {
            return DBManager::getInstance()->fetch($sql);
        }
        else
        {
            return DBManager::getInstance()->execute($sql);
        }
    }
	
    public function find($type, $option = array())
    {
		$data = array();
        $fields = array();
        if (array_key_exists('fields', $option))
        {
            $fields = $option['fields'];
        }
		
		if ($type == 'count')
		{
			$fields = "count(*) as count";
		}
        else if (empty($fields))
        {
            $fields = "*";
        }
        else
        {
            $fields = implode(",", $fields);
        }
        
        $sql = "select $fields from " . MainConfig::PREFIX . $this->table . " where 1=1 ";
        
        if (array_key_exists('conditions', $option))
        {
            $sql .= $this->explainCondition($option['conditions']);
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
        return $data;
    }
    
    public function findById($id, $options=array())
    {
        $options['conditions'] = array('id'=>$id);
        $data = $this->find('first', $options);
        return $data;
    }
    
    private function explainFieldValue(&$v)
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

    public function update($data, $conditions)
    {
        $dataStr = "";
        foreach($data as $k => $v)
        {
            $this->explainFieldValue($v);
            $dataStr .= $k . '=' . $v . ',';
        }
        $dataStr = substr($dataStr, 0, strlen($dataStr)-1);
        
        $conditionStr = $this->explainCondition($conditions);
        
        $sql = "update " . MainConfig::PREFIX . $this->table . " set " . $dataStr . " where 1=1 " . $conditionStr;
        DBManager::getInstance()->execute($sql);
    }
    
    private function explainCondition($conditions)
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
                    $orStr .= ' or ' . $this->explainValue($k1, $v1);
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
                $sql .= ' and ' . $this->explainValue($k, $v);
            }
        }
        
        return $sql;
    }
    
    private function explainValue($k, $v)
    {
        $str = '';
        
        if (is_array($v) && (!empty($v)))
        {
            $str = $k . ' in (' . implode(",", $v) . ') ';
        }
        else
        {
            if (strpos($k, '<') || strpos($k, '>'))
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
    
    public function save($obj, $type='')
    {
        $sql = $this->generateSaveSql($obj, $type);
        
        return DBManager::getInstance()->execute($sql);
    }
    
	/**
	 * 
	 * @param array||obj $obj
	 * @param string $type
	 * @return string
	 */
    private function generateSaveSql($obj, $type)
    {
        $sql = '';
        if ($type === '')
        {
            if (is_array($obj))
            {
                $type = 'insert';
            }
            else
            {
                if (!isset($obj->id))
                {
                    $type = 'insert';
                }
                else
                {
                    $type = 'update';
                }
            }
        }

        if ($type === 'insert')
        {
            $keys = array();
            $values = array();
            foreach($obj as $k=>$v)
            {
                $keys[] = "`$k`";
				$v = str_replace("'", "''", $v);
                $values[] = "'$v'";
            }

            $sql = 'insert into ' . MainConfig::PREFIX . $this->table . '(' . implode(",", $keys)  . ') values(' . implode(",", $values) . ')';
        }
        else
        {
            $sql = 'update ' . MainConfig::PREFIX . $this->table . ' set ';
            $arr = array();
            foreach($obj as $k => $v)
            {
				if ($k != 'id')
				{
					$v = str_replace("'", "''", $v);
					$this->explainFieldValue($v);
					$arr[] = $k . '=' . $v;
				}
            }

            $sql .= implode(",", $arr);
			if (is_array($obj))
			{
				$sql .= ' where id=' . $obj['id'];
			}
			else
			{
				$sql .= ' where id=' . $obj->id;
			}
        }

        return $sql;
    }
    
    /**
     *
     * @param model-obj $arrObj
     * @return type 
     */
    public function saveMany($arrObj, $type='')
    {
        foreach($arrObj as $i=>$obj)
        {
            $sql = $this->generateSaveSql($obj, $type);
            DBManager::getInstance()->execute($sql);
        }
    }
    
	/**
	 * 将array批量装载到obj
	 * @param type $arrData
	 * @return \Model\Manager\className
	 */
    public function loadData($arrData)
    {
        $className = str_replace("Manager", "Core", get_called_class()) ;
        $className = substr($className, 0, strlen($className)-4);
        
        $models = array();
        foreach ($arrData as $ap)
        {
            $newInstance = new $className();
            foreach($ap as $k=>$v)
            {
                $k = str_replace("-", "_", $k);
                
                $newInstance->$k = $v;
            }
            $models[] = $newInstance;
        }
        
        return $models;
    }
    
    public function delete($id, $asso = false)
    {
        $sql = 'delete from ' . MainConfig::PREFIX . $this->table . ' where id=' . $id;
        return DBManager::getInstance()->execute($sql);
    }
    
    public function multi_execute($sql)
    {
        DBManager::getInstance()->multi_execute($sql);
    }

}

?>