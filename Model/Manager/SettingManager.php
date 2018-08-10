<?php
namespace Model\Manager;
use Model\Manager\DataManager;
use MainConfig;

class SettingManager extends DataManager
{
    private static $data = null;
    public $table = "settings";
    
    private function getData()
    {
        if (self::$data == null)
        {
            self::$data = $this->find('first');
        }
        
        return self::$data;
    }
    
    public function getNowDate()
    {
        $data = $this->getData();
        return $data['today'];
    }
    
    public function getFifaDates()
    {
        return MainConfig::$bakFifaDates;
    }
    
    public function addDate()
    {
        $data = $this->getData();
        $tomorrow = date('Y-m-d', strtotime($data['today'] . ' +1 day'));
        $data['today'] = $tomorrow;
        $this->update(array('today'=>$tomorrow), array());
    }
}