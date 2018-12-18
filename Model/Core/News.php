<?php
namespace Model\Core;

class News extends YpnModel
{
	protected $table = 'news';
	
	public static function create($content, $team_id, $nowDate, $imgSrc)
	{
        $newNews = new static();
        $newNews->content = $content;
		$newNews->team_id = $team_id;
		$newNews->PubTime = $nowDate;
		$newNews->ImgSrc = $imgSrc;
		$newNews->save();
	}
}