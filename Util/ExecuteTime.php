<?php
namespace Util;

class ExecuteTime 
{
    private static  $_instance = NULL;
    private static $startTime;
	
	private function __construct() {}
    
    public static function getInstance()
	{
		if(is_null(self::$_instance)) self::$_instance = new ExecuteTime();
		return self::$_instance;
	}
    
    public function start()
    {
        $pagestartime = microtime(); 
        self::$startTime = explode(" ", $pagestartime); 
    }
    
    public function end()
    {
        $pageendtime = microtime(); 
        $endtime = explode(" ",$pageendtime); 
        $totaltime = $endtime[0]-self::$startTime[0]+$endtime[1]-self::$startTime[1]; 
        $timecost = sprintf("%s",$totaltime); 
        echo "<br/><font color=red>页面运行时间: $timecost 秒</font><br/>"; 
    }

}

?>
