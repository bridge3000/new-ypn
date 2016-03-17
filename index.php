<?php
require 'MainConfig.php';
use Controller\MatchController as MatchController;
use Controller\ToolController as ToolController;
use Controller\YpnController;

//die(MainConfig::STATIC_DIR);

function __autoload($class) 
{  
 	 $class = str_replace('\\', '/', $class) . '.php';
	 require_once(MainConfig::ROOTPATH . MainConfig::DS . $class);
}

$strM = $_GET['c'];

if ($strM == "")
{
    header("location:index.html");
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
    
    $firstLetter = substr($strM, 0, 1);
    $ClassName = "Controller\\" . str_replace($firstLetter, strtoupper($firstLetter), $strM). "Controller";
    
    call_user_func_array(array($ClassName::getInstance(), $action), $params);
}
?>