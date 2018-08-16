<?php
namespace Model\Manager;
use Model\Manager\DataManager;
use Model\Core\News;

class NewsManager extends DataManager
{
    public $table = "news";
    private static $newArr = array();

    public function add($content, $team_id, $nowDate, $imgSrc)
	{
        $newNews = new News();
        $newNews->content = $content;
		$newNews->team_id = $team_id;
		$newNews->PubTime = $nowDate;
		$newNews->ImgSrc = $imgSrc;
		$this->saveModel($newNews);
	}
	
	public function push($content, $team_id, $nowDate, $imgSrc)
	{
        $newNews = array();
        $newNews['content'] = $content;
		$newNews['team_id'] = $team_id;
		$newNews['PubTime'] = $nowDate;
		$newNews['ImgSrc'] = $imgSrc;
        self::$newArr[] = $newNews;
	}
	
	public function insertBatch($keys=array(), $values=array())
	{
		if (!empty(self::$newArr))
		{
			$keys = array_keys(self::$newArr[0]);
			foreach(self::$newArr as $n)
			{
				$v = array();
				foreach($keys as $k)
				{
					$v[] = $n[$k];
				}
				$values[] = $v;
			}
		}
		parent::insertBatch($keys, $values);
	}
	
	public function readAll($teamId)
    {
        $this->query('update ypn_news set isRead=1 where team_id=' . $teamId);
    }
    
    public function getUnreadNews($teamId)
    {
        $newsArr = $this->find('all', array(
           'conditions' => array('team_id'=>$teamId, 'isRead'=>0) 
        ));
        
        return $newsArr;
    }
}