<?php
namespace Controller;
use Controller\AppController;
use Model\Manager\TeamManager;
use Model\Manager\ElGroupManager;
use MainConfig;

/**
 * Description of ElGroupController
 *
 * @author qiaoliang
 */
class ElGroupController extends AppController 
{
	public $name = 'ElGroup';
	public $layout = "main";
	
	public function index()
	{
		$groups = ElGroupManager::getInstance()->find('all', array(
			'order' => array('score'=>'desc')
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
