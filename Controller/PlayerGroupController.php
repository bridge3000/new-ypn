<?php
namespace Controller;

use Controller\AppController;

/**
 * 阵容分组
 *
 * @author qiaoliang
 */
class PlayerGroupController extends AppController 
{
	public $name = 'UclGroup';
	public $layout = "main";
	
	public function add()
	{
		$name = $_POST['name'];
		$teamId = $_POST['team_id'];

		$newPlayerGroup = new \Model\Core\PlayerGroup();
		$newPlayerGroup->name = $name;
		$newPlayerGroup->team_id = $teamId;
		$newPlayerGroup->save();
		
		$this->redirect("/player/chuchang");
	}
}