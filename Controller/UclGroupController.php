<?php
namespace Controller;
use Controller\AppController;
use Model\Manager\TeamManager;
use Model\Manager\UclGroupManager;
use MainConfig;

/**
 * Description of UclGroupController
 *
 * @author qiaoliang
 */
class UclGroupController extends AppController 
{
	public $name = 'UclGroup';
	public $layout = "main";
	
	public function index()
	{
		$groups = UclGroupManager::getInstance()->find('all', array(
			'order' => array('GroupName'=>'asc', 'score'=>'desc')
		));
		$groupTeamIds = array();
		foreach($groups as $g)
		{
			$groupTeamIds[] = $g['team_id'];
		}
		
		$teams = TeamManager::getInstance()->find('all', array(
			'options' => array('id'=>$groupTeamIds),
			'fields' => array('id', 'name'),
		));
		
		$teamList = array();
		foreach($teams as $t)
		{
			$teamList[$t['id']] = $t['name'];
		}
		
		$groupData = array();
		foreach($groups as &$g)
		{
			$g['team_name'] = $teamList[$g['team_id']];
			$groupData[$g['GroupName']][] = $g;
		}
		
		$this->set('groups', $groupData);
		$this->render('index');
	}
}
