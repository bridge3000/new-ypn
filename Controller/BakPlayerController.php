<?php
namespace Controller;
use Model\Manager\BakPlayerManager;
use Model\Manager\BakTeamManager;
use Model\Manager\LeagueManager;
use Model\Manager\CountryManager;
use Util\FormHelper;

class BakPlayerController extends AppController
{
    public $name = "BakPlayer";
    public $layout = 'back';
    
    public function show($id)
    {
        $curPlayer = BakPlayerManager::getInstance()->findById($id);
        $leagues = LeagueManager::getInstance()->find('list', array(
            'fields' => array('id', 'title'),
            'order' => array('id'=>'asc')
            ));
        $data = BakTeamManager::getInstance()->find('all', array('fields' => array('id', 'name', 'league_id')));
        $teams = array();
        foreach($data as $d)
        {
            $teams[$d['league_id']][] = $d;
        }
        $this->set('curPlayer', $curPlayer);
        $this->set('leagues', $leagues);
        $this->set('teams', $teams);
        $this->render('show');
    }
    
    public function add()
    {
        $leagues = LeagueManager::getInstance()->find('list', array(
            'fields' => array('id', 'title'),
            'order' => array('id'=>'asc')
            ));
        $data = BakTeamManager::getInstance()->find('all', array('fields' => array('id', 'name', 'league_id')));
        $teams = array();
        foreach($data as $d)
        {
            $teams[$d['league_id']][] = $d;
        }
        
        $trainings = array();
        foreach(\MainConfig::$trainings as $k=>$v)
        {
            $trainings[$k] = $v['title'];
        }
        
        $this->set('leagues', $leagues);
        $this->set('teams', $teams);
        $this->set('trainings', $trainings);
        $this->render('add');
    }
    
    public function save()
    {
        $id = $_POST['id'];
        $teamId = $_POST['team_id'];
        $_POST['sinew'] = $_POST['SinewMax'];
        if ($_POST['team_id'] == 0)
        {
            $_POST['league_id'] = 0;
        }
        
        $country = CountryManager::getInstance()->find('first', array(
            'conditions' => array('title'=>$_POST['country'])
        ));
        $_POST['country_id'] = $country['id'];
        $data = BakPlayerManager::getInstance()->loadData(array($_POST));
        BakPlayerManager::getInstance()->save($data[0]);
        $this->redirect(array('controller'=>'BakTeam', 'action'=>'show', 'params'=>$teamId));
    }
    
    public function search()
    {
        if (!empty($_POST))
        {
            $name = $_POST['name'];
            $birthdate = $_POST['birthdate'];
            $data = BakPlayerManager::getInstance()->search($name, $birthdate);
            
            $this->set('players', $data);
            $this->set('name', $name);
            $this->set('birthdate', $birthdate);
        }
        
        $this->render('search');
    }
}

?>