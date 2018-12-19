<?php
namespace Controller;

class AppController 
{
    public $name = "";
    public $data = array();
    
    public static function getInstance()
	{
        static $aoInstance = array(); 
        $calledClassName = get_called_class(); 
        
        if (! isset ($aoInstance[$calledClassName])) { 
            $aoInstance[$calledClassName] = new $calledClassName(); 
        } 
        return $aoInstance[$calledClassName]; 
	}
    
    public function render($view)
    {
        extract($this->data);
        require "View/Layout/$this->layout.html";
    }
    
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
    
    public function reset()
    {
        $this->data = array();
    }
    
    protected function oldRedirect($data, $jumpPage = true)
    {
        $controller = $data['controller'];
        $action = $data['action'];
        $params = isset($data['params']) ? $data['params'] : "";
        
        if ($jumpPage)
        {
            header("location:index.php?c=$controller&a=$action&p=$params");exit;
//			header("location:/$controller/$action");exit;
        }
        else
        {
            $firstLetter = substr($controller, 0, 1);
            $ClassName = "Controller\\" . str_replace($firstLetter, strtoupper($firstLetter), $controller). "Controller";
            $m = $ClassName::getInstance();
            $m->$action();
        }
    }
	
	protected function redirect($path)
    {
		header("location:{$path}");exit;
	}
    
    protected function flushNow($str)
    {
		echo $str;
        ob_flush();
        flush();
    }
	
	public function flushCss()
	{
		$this->flushNow("<link type=\"text/css\" rel=\"stylesheet\" href=\"" . \MainConfig::BASE_URL . "res/css/main.css\" />");
	}
	
	public function flushJs()
	{
		$this->flushNow("<script src=\"" . \MainConfig::BASE_URL . "res/js/jquery.js\"  type=\"text/javascript\"></script>");
	}
		
	protected function responseToClient($code, $data)
	{
		exit(json_encode(['code'=>$code, 'data'=>$data]));
	}
}