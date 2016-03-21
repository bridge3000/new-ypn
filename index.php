<?php
require 'MainConfig.php';
//use Controller\MatchController as MatchController;

function __autoload($class) 
{  
 	 $class = str_replace('\\', '/', $class) . '.php';
	 require_once(MainConfig::ROOTPATH . MainConfig::DS . $class);
}

function handleError($errno,$errstr,$errfile,$errline,$errcontext)
{
	echo "错误级别：" . $errno . "<br/>";
	echo $errstr . "<br/>";
	echo $errfile . "<br/>";
	echo $errline . "<br/>";
//	print_r($errcontext); //错误涉及的所有变量，太大，需要时打开
	
	$array = debug_backtrace();
    unset($array[0]);
	
	$html = '';
    foreach($array as $row)
    {
       $html .= $row['file'].':'.$row['line'].'行,调用方法:'.$row['function']."<p>";
    }
    echo $html;
}

set_error_handler("handleError");

$controller = '';
$action = '';
$params = array();

$slashParams = array_keys($_GET);

if (isset($_GET['c'])) //问号传参
{
	$controller = isset($_GET['c']) ? $_GET['c'] : '';
	if ($controller == "")
	{
		MatchController::getInstance()->today();
	}
	else
	{
		$action = $_GET['a'];
		$paramString = isset($_GET['p']) ? $_GET['p'] : '';
		if (strpos($paramString, ',') != false)
		{
			$params = explode(",", $paramString);
		}
		else
		{
			$params = array($paramString);
		}
	}
}
else if (!empty($slashParams)) //根据斜线分割
{
	$tmpArr = explode("/", $slashParams[0]);
	$controller = $tmpArr[1];
	$action = $tmpArr[2];
	for($i=3;$i<count($tmpArr);$i++)
	{
		$params[] = $tmpArr[$i];
	}
}
else
{
	$controller = 'match';
	$action = 'today';
}

$firstLetter = substr($controller, 0, 1);
$ClassName = "Controller\\" . str_replace($firstLetter, strtoupper($firstLetter), $controller). "Controller";
call_user_func_array(array($ClassName::getInstance(), $action), $params);