<?php
namespace Model\Manager;
use Model\Core\Coach;

class CoachManager extends DataManager
{
    public function getMyCoach()
    {
        $coach = new Coach();
        $coach->team_id = 4;
        
        return $coach;
    }
}

?>
