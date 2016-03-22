<?php
namespace Model\Manager;
use Model\Manager\DataManager;
use Model\Core\News;

class NewsManager extends DataManager
{
    public $table = "news";
    private static $newArr = array();

    public function add($content, $team_id, $nowDate, $imgSrc, $isRead = 0)
	{
        $newNews = new News();
        $newNews->content = $content;
		$newNews->team_id = $team_id;
		$newNews->PubTime = $nowDate;
		$newNews->ImgSrc = $imgSrc;
        $newNews->isRead = $isRead;
        self::$newArr[] = $newNews;
	}
    
    public function readAll($teamId)
    {
        $this->query('update ypn_news set isRead=1 where team_id=' . $teamId);
    }
    
    public function saveAllData()
    {
        if (!empty(self::$newArr))
        {
            $this->saveMany(self::$newArr);
        }
    }
    
    public function getUnreadNews($teamId)
    {
        $newsArr = $this->find('all', array(
           'conditions' => array('team_id'=>$teamId, 'isRead'=>0) 
        ));
        
        return $newsArr;
    }
}
?>