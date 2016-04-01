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
    
    protected function redirect($data, $jumpPage = true)
    {
        $controller = $data['controller'];
        $action = $data['action'];
        $params = isset($data['params']) ? $data['params'] : "";
        
        if ($jumpPage)
        {
            header("location:index.php?c=$controller&a=$action&p=$params");exit;
        }
        else
        {
            $firstLetter = substr($controller, 0, 1);
            $ClassName = "Controller\\" . str_replace($firstLetter, strtoupper($firstLetter), $controller). "Controller";
            $m = $ClassName::getInstance();
            $m->$action();
        }
    }
    
    protected function flushNow($str)
    {
        echo str_pad('<span>' . $str . '</span>', 4096);
        ob_flush();
        flush();
    }
    
    protected function changeStatus($str)
    {
        $this->flushNow($str);
    }
}