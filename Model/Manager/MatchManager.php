<?php
namespace Model\Manager;
use MainConfig;
use Model\Manager\CoachManager;
use Model\Core\Match;
use Util\ExecuteTime;

class MatchManager extends DataManager
{
    public $table = "matches";
    
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
//        ExecuteTime::getInstance()->start();
        $ignoreFields = array();
        DBManager::getInstance()->copyTable(MainConfig::PREFIX . 'bak_matches', MainConfig::PREFIX . $this->table, $ignoreFields);
        
//        ExecuteTime::getInstance()->end();
    }
    
    public function watch($id)
    {
        $this->update(array('isWatched'=>1), array('id'=>$id));
    }
}
?>