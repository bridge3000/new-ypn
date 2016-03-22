<?php
namespace Model\Manager;
use MainConfig;

class DBManager 
{
	private static  $_instance = NULL;
    public $conn = null;
	
	private function __construct()
	{
        $this->conn = mysqli_connect(MainConfig::DB_HOST, MainConfig::DB_USER, MainConfig::DB_PASS, MainConfig::DB_NAME);
        
        if (!$this->conn) die('db connect error');
        mysqli_query($this->conn, "set names 'utf8'");
	}
	
	public static function getInstance()
	{
	    if(is_null(self::$_instance)) self::$_instance = new DBManager();

	    return self::$_instance;
	}
    
    public function execute($sql)
    {
        $result = mysqli_query(DBManager::getInstance()->conn, $sql);
        if (mysqli_error(DBManager::getInstance()->conn))
        {
            echo(mysqli_error(DBManager::getInstance()->conn) . '<br/>');
            echo($sql . '<br/>');
            trigger_error('');
            exit;
        }else{
            return $result;
        }
    }
    
    public function multi_execute($sql)
    {
        mysqli_multi_query(DBManager::getInstance()->conn, $sql);
//        while (mysqli_next_result(DBManager::getInstance()->conn)) {;}
//        if (mysqli_multi_query(DBManager::getInstance()->conn, $sql)) 
//        {
//            do {
//                // Store first result set
//                if ($result = mysqli_store_result(DBManager::getInstance()->conn)) {
//                    mysqli_free_result(DBManager::getInstance()->conn);
//                }
//            } while (mysqli_more_results(DBManager::getInstance()->conn));
//        }
//        else
//        {
//            echo(mysqli_error(DBManager::getInstance()->conn) . '<br/>');
//            echo($sql . '<br/>');
//            trigger_error('');
//            exit;
//        }
    }

    public function fetch($sql)
	{
	    $data = array();
        
        $query = mysqli_query(DBManager::getInstance()->conn, $sql);
        if (mysqli_error(DBManager::getInstance()->conn))
        {
            echo(mysqli_error(DBManager::getInstance()->conn) . '<br/>');
            echo($sql . '<br/>');
            trigger_error('');
            exit;
        }
        else
        {
            while ($row = mysqli_fetch_assoc($query))
            {
                $data[] = $row;
            }

            mysqli_free_result($query); 
            return $data;
        }
	}
            
    public function fetchList($sql)
	{
	    $data = array();
        
        $query = mysqli_query(DBManager::getInstance()->conn, $sql);
        if (mysqli_error(DBManager::getInstance()->conn))
        {
            echo(mysqli_error(DBManager::getInstance()->conn) . '<br/>');
            echo($sql . '<br/>');
            trigger_error('');
            exit;
        }
        else
        {
            while ($row = mysqli_fetch_row($query))
            {
                $data[$row[0]] = $row[1];
            }

            mysqli_free_result($query); 
            return $data;
        }
	}
        
    public function copyTable($oldTable, $newTable, $ignoreFields)
    {
        $sql = 'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE table_name = "' . $oldTable . '"';
        $data = $this->fetch($sql);

        $fields = array();
        foreach($data as $d)
        {
            if (!in_array($d['COLUMN_NAME'], $ignoreFields, true))
            {
                $fields[] = $d['COLUMN_NAME'];
            }
        }
        
        if (!empty($fields))
        {
            $fieldStr = implode(",", $fields);
            $sql = "INSERT INTO $newTable($fieldStr) SELECT $fieldStr FROM $oldTable";
        }
        else
        {
            $sql = "INSERT INTO $newTable SELECT * FROM $oldTable";
        }

        return $this->execute($sql);
    }
} 

?>