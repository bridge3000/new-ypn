<?php
namespace Controller;
use Controller\AppController;
use Model\Manager\BakMatchManager;
use Model\Manager\BakTeamManager;

class ToolController extends AppController
{
	public function turn_name_to_id($tableName)
	{
		die;
		header("Content-type: text/html; charset=utf-8");
		
		//取出 ID=>NAME的映射
		$classId = 3; //match class
		$sourceTable = 'ouguan';
		$teamList = BakTeamManager::getInstance()->find('list', array());
		
// 		print_r($teamList);exit;
		
		$data = BakMatchManager::getInstance()->turnTable($sourceTable, $teamList, $classId);		
		
	}
}