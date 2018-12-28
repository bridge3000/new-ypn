<?php
require 'MainConfig.php';

function __autoload($class) 
{  
 	 $class = str_replace('\\', '/', $class) . '.php';
	 require_once(MainConfig::ROOTPATH . MainConfig::DS . $class);
}

function handleError($errno,$errstr,$errfile,$errline,$errcontext)
{
//	echo "错误级别：" . $errno . "<br/>";
	echo $errstr . "<br/>";
	echo $errfile . $errline . "<br/>";
//	print_r($errcontext); //错误涉及的所有变量，太大，需要时打开
	
	$array = debug_backtrace();
    unset($array[0]);
	
	$html = '';
    foreach($array as $row)
    {
		if(isset($row['file']))
		{
			$html .= $row['file'].':'.$row['line'].'行,调用方法:'.$row['function']."<p>";
		}
		else
		{
			print_r($row);
		}
    }
    echo $html.'<hr/>';
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
//	print_r($tmpArr);exit;
	
	$controller = $tmpArr[0];
	if(isset($tmpArr[1]))
	{
		$action = $tmpArr[1];
	}
	else
	{
		$action = 'index';
	}
	
	for($i=2;$i<count($tmpArr);$i++)
	{
		$params[] = $tmpArr[$i];
	}
}
else
{
	$controller = 'match';
	$action = 'today';
}

//die(php_sapi_name());

//if(preg_match("/cli/i", php_sapi_name()))
if(php_sapi_name() == "cli")
{
	$controller = $argv[1];
	$action = $argv[2];
	$params = [];
}

$firstLetter = substr($controller, 0, 1);
$ClassName = "Controller\\" . str_replace($firstLetter, strtoupper($firstLetter), $controller). "Controller";
call_user_func_array(array($ClassName::getInstance(), $action), $params);