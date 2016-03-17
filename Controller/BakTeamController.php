<?php
namespace Controller;
use Model\Manager\DBManager;
use Model\Manager\LeagueManager;
use Model\Manager\BakTeamManager;
use Model\Manager\BakPlayerManager;

class BakTeamController extends AppController
{
    public $name = "BakTeam";
    public $layout = "back";

    public function list_all($leagueId = '')
    {
        $leagues = LeagueManager::getInstance()->find('list', array('fields'=>array('id', 'title')));
        $teams = array();
        if ($leagueId != '')
        {
            $teams = BakTeamManager::getInstance()->find('list', array(
                'conditions' => array('league_id'=>$leagueId),
                'fields' => array('id', 'name')
            ));
        }

        $this->set('teams', $teams);
        $this->set('leagues', $leagues);
        $this->render('list_all');
    }
    
    public function show($teamId)
    {
        $curTeam = BakTeamManager::getInstance()->findById($teamId);
        $players = BakPlayerManager::getInstance()->find('all', array(
            'conditions' => array('team_id'=>$teamId),
            'fields' => array('id', 'name', 'ShirtNo', 'birthday'),
            'order' => array('ShirtNo'=>'asc')
            ));
        
        $this->set('curTeam', $curTeam);
        $this->set('players', $players);
        $this->render('show');
    }
}

?>