<?php
namespace Model\Manager;

use Model\Manager\DataManager;
class BakMatchManager extends DataManager
{
	public $table = 'bak_matches';
	
	public function turnTable($sourceTable, $teamList, $classId)
	{
		$realTable = $this->table;
		
		$this->table = $sourceTable;

		$teamList = array_flip($teamList);
		
		$records = $this->find('all');
		
		$newMatches = array();
		foreach($records as $r)
		{
			$hostTeamId = $teamList[$r['host_name']];			
			$guestTeamId = $teamList[$r['guest_name']];
			
			$newMatches[] = array('PlayTime' => $r['PlayTime'], 'HostTeam_id'=>$hostTeamId, 'GuestTeam_id'=>$guestTeamId, 'class_id'=>$classId, 'is_host_park'=>1);
		}
		
		$this->table = $realTable;
		
		
// 		$this->saveMany($newMatches, 'insert');

		foreach ($newMatches as $m)
		{
			$this->saveModel($m, 'insert');
		}
		echo 'success';		
	}
}