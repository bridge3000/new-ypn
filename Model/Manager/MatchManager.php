<?php
namespace Model\Manager;
use MainConfig;
use Model\Core\Match;

class MatchManager extends DataManager
{
    public $table = "matches";
	static $matches = array();
    
    public function today()
    {
        $nowDate = SettingManager::getInstance()->getNowDate();
        
        $data = $this->find("all", array(
            'conditions' => array(
                'PlayTime' => $nowDate
                ),
            'fields'=>array('id', 'PlayTime', 'HostTeam_id', 'GuestTeam_id', 'isPlayed', 'HostGoals', 'GuestGoals', 'class_id', 'HostGoaler_ids', 'GuestGoaler_ids', 'isWatched'),
            ));
        return $data;
    }
    
    public function getMyAllMatches($myTeamId)
    {
        $data = $this->find("all", array(
            'conditions' => array(
                'or' => array('HostTeam_id' => $myTeamId, 'GuestTeam_id' => $myTeamId),
                'isPlayed' => 0
                ),
            'fields' => array('id', 'PlayTime', 'HostTeam_id', 'GuestTeam_id', 'isPlayed', 'isWatched', 'class_id', 'HostGoals', 'GuestGoals', 'HostGoaler_ids', 'GuestGoaler_ids'),
            'order'=> array('PlayTime' => 'asc')
            ));
        return $data;
    }
    
    public function getNextUnplayedMatch($myTeamId)
    {
        $data = $this->find('first', array(
           'conditions' => array(
               'or'=>array('HostTeam_id'=>$myTeamId, 'GuestTeam_id'=>$myTeamId),
               'isPlayed'=>0
               ) ,
            'fields' => array('HostTeam_id', 'GuestTeam_id', 'PlayTime'),
            'order' => array('PlayTime'=>'asc')
        ));
        
        return "next match played:" . $data['PlayTime'];
    }
    
    public function getTodayMatches($nowDate, $isPlayed = -1)
    {
        $conditions = array('PlayTime' => $nowDate, 'isPlayed' => 0);
        
        if ($isPlayed != -1)
        {
            $conditions['isPlayed'] = $isPlayed;
        }
        
        $todayMatches = MatchManager::getInstance()->find('all', array(
            'conditions' => $conditions
        ));

        $matches = array();
        foreach($todayMatches as $match)
        {
            $newMatch = new Match();
            
            foreach($match as $k=>$v)
            {
                $newMatch->$k = $v;
            }
            $matches[] = $newMatch;
        }
        
        return $matches;
    }

    public function resetMatches()
    {
        $ignoreFields = array();
        DBManager::getInstance()->copyTable(MainConfig::PREFIX . 'bak_matches', MainConfig::PREFIX . $this->table, $ignoreFields);
    }
    
    public function watch($id)
    {
        $this->update(array('isWatched'=>1), array('id'=>$id));
    }
	
	public function watchByDay($playDate)
	{
		$this->update(array('isWatched'=>1), array('PlayTime'=>$playDate));
	}
	
	public function push($hostTeamId, $guestTeamId, $classId, $playDate)
	{
		self::$matches[] = array('HostTeam_id'=>$hostTeamId, 'GuestTeam_id'=>$guestTeamId, 'class_id'=>$classId, 'PlayTime'=>$playDate);
	}
	
	public function insertBatch($keys=array(), $values=array())
	{
		if (!empty(self::$matches))
		{
			$keys = array_keys(self::$matches[0]);
			foreach(self::$matches as $n)
			{
				$v = array();
				foreach($keys as $k)
				{
					$v[] = "'" . $n[$k] . "'";
				}
				$values[] = $v;
			}
		}
		parent::insertBatch($keys, $values);
	}
	
	/**
	 * 主客场比赛对比
	 * @param array $match1
	 * @param array $match2
	 * @return int
	 */
	public function diff($match1, $match2)
	{
		$winTeamId = 0;
		if( ($match1['HostGoals']+$match2['GuestGoals']) > ($match1['GuestGoals']+$match2['HostGoals']) )
		{
			$winTeamId = $match1['HostTeam_id'];
		}
		else if( ($match1['HostGoals']+$match2['GuestGoals']) < ($match1['GuestGoals']+$match2['HostGoals']) )
		{
			$winTeamId = $match1['GuestTeam_id'];
		}
		else
		{
			if($match1['GuestGoals'] > $match2['GuestGoals'])
			{
				$winTeamId = $match1['HostTeam_id'];
			}
			else
			{
				$winTeamId = $match1['GuestTeam_id'];
			}
		}
		
		return $winTeamId;
	}
}