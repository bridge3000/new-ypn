<?php
namespace Controller;
use Controller\AppController;
use Model\Manager\NewsManager;
use Model\Manager\CoachManager;

/**
 * 
 *
 * @author qiaoliang
 */
class NewsController extends AppController {
	var $name = 'News';
		
	public function index($curPage=1)
	{
		$this->layout = 'main';
		$perPage = 20;
		
		$myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
		$news = NewsManager::getInstance()->find('all', array(
			'conditions' => array('team_id'=>$myTeamId),
			'fields' => array('id', 'PubTime', 'content', 'ImgSrc'),
			'limit' => array(($curPage-1)*$perPage, $perPage),
			'order' => array('id'=>'desc'),
		));
		
		$this->set('news', $news);
		$this->render('index');
	}
}
