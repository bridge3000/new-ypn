<?php
namespace Model\Manager;

class FutureContractManager extends DataManager
{
    public $table = 'future_contracts';
    
    public function getAllPlayerIds()
	{
		$allFutruePlayers = FutureContractManager::getInstance()->find('all', array('fields'=>array('player_id')));
		$futrueContractPlayerIds = array();
        foreach($allFutruePlayers as $fPlayer)
        {
            $futrueContractPlayerIds[] = $fPlayer['player_id'];
        }
        return $futrueContractPlayerIds;
	}
    
}