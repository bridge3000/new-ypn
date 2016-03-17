<?php
namespace Model\Core;

class Coach 
{
    public $id;
    public $team_id;
    public $name;
    private $team;
    
    public function setTeam(Team $team)
    {
        $this->team = $team;
    }
    
    public function getTeam()
    {
        return $this->team;
    }
    
    /**
     * 获得需要抽取的球员位置和数量等信息
     * @param type $allRetiredShirts
     * @param type $allPlayers
     * @return array
     */
    public function getYoungPlayers($allRetiredShirts, $allPlayers, $usedNOs)
    {
        $needPositions = array(
            array('position_id'=>9, 'count'=>2),
            array('position_id'=>10, 'count'=>2),
            array('position_id'=>13, 'count'=>2),
            array('position_id'=>14, 'count'=>2),
            array('position_id'=>4, 'count'=>3),
            array('position_id'=>2, 'count'=>2),
            array('position_id'=>3, 'count'=>2),
        );
        
        switch ($this->team->getFormattion()) 
        {
            case "3-5-2":
                $needPositions[] = array('position_id'=>3, 'count'=>2);
                $needPositions[] = array('position_id'=>8, 'count'=>2);
                $needPositions[] = array('position_id'=>1, 'count'=>4);
                break;
            case "5-3-2":
                $needPositions[] = array('position_id'=>3, 'count'=>4);
                $needPositions[] = array('position_id'=>1, 'count'=>4);
                break;
            case "3-4-3":
                $needPositions[] = array('position_id'=>2, 'count'=>2);
                $needPositions[] = array('position_id'=>5, 'count'=>2);
                $needPositions[] = array('position_id'=>6, 'count'=>2);
                $needPositions[] = array('position_id'=>7, 'count'=>2);
                break;
            case "4-3-3":
                $needPositions[] = array('position_id'=>3, 'count'=>2);
                $needPositions[] = array('position_id'=>5, 'count'=>2);
                $needPositions[] = array('position_id'=>6, 'count'=>2);
                $needPositions[] = array('position_id'=>7, 'count'=>2);
                break;
            case "4-5-1":
                $needPositions[] = array('position_id'=>3, 'count'=>2);
                $needPositions[] = array('position_id'=>2, 'count'=>2);
                $needPositions[] = array('position_id'=>8, 'count'=>2);
                $needPositions[] = array('position_id'=>7, 'count'=>2);
                break;
            case "圣诞树":
                $needPositions[] = array('position_id'=>3, 'count'=>2);
                $needPositions[] = array('position_id'=>8, 'count'=>4);
                $needPositions[] = array('position_id'=>7, 'count'=>2);
                break;            
            default:/*默认的都算442*/
                $needPositions[] = array('position_id'=>3, 'count'=>2);
                $needPositions[] = array('position_id'=>8, 'count'=>2);
                $needPositions[] = array('position_id'=>1, 'count'=>4);
                break;
        }
        
        $teamId = $this->team->getId();
        
        foreach($allRetiredShirts as $rs)
        {
            if ($rs['team_id'] == $teamId)
            {
                $usedNOs[] = $rs['shirt'];
            }
        }
        
        $positionData = $this->team->getPositionInfo();
        
        $extractInfo = array('positions'=>array());
        foreach($needPositions as $np)
        {
            $curPosCount = array_key_exists($np['position_id'], $positionData) ? $positionData[$np['position_id']] : 0;
            if ($np['count'] > $curPosCount)
            {
                $extractInfo['positions'][$np['position_id']] = $np['count'] - $curPosCount;
            }
            
            $extractInfo['used_nos'] = $usedNOs;
        }
        
        return $extractInfo;
    }
    
}