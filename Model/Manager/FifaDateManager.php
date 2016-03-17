<?php
namespace Model\Manager;
use MainConfig;
use Model\Core\FifaDate;

class FifaDateManager extends DataManager
{
    public $table = 'fifa_dates';
    
    public function updateFifaDate()
    {
		$bakFifaDates = MainConfig::$bakFifaDates;
        $fifaDates = array();
		for ($i = 0;$i < count($bakFifaDates);$i++)
		{
			$newDate = new FifaDate();
            $newDate->PlayDate = $bakFifaDates[$i];
            $fifaDates[] = $newDate;
		}
        
        $this->saveMany($fifaDates);
    }
    
}