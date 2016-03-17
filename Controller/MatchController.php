<?php
namespace Controller;
use Controller\AppController;
use Model\Core\Player;
use Model\Manager\MatchManager;
use Model\Manager\TeamManager;
use Model\Manager\SettingManager;
use Model\Manager\PlayerManager;
use Model\Manager\CoachManager;

class MatchController extends AppController 
{
    public $name = "Match";
    public $layout = "main";
    private $isWatch = 0;
    
    public function today()
    {
        $matches = MatchManager::getInstance()->today();
        $allTeams = TeamManager::getInstance()->find('list', array('fields'=>array('id', 'name')));
        $this->set('matches', $matches);
        $this->set('allTeams', $allTeams);
        self::render("today");
    }
    
    public function all()
    {
        $myCoach = CoachManager::getInstance()->getMyCoach();
        $matches = MatchManager::getInstance()->getMyAllMatches($myCoach->team_id);
        $allTeams = TeamManager::getInstance()->find('list', array('fields'=>array('id', 'name')));
        
        $this->set('matches', $matches);
        $this->set('allTeams', $allTeams);
        self::render("all");
    }
    
    public function play($nowDate)
    {
        $nowDate = SettingManager::getInstance()->getNowDate();
        
        $todayMatches = MatchManager::getInstance()->getTodayMatches($nowDate, 0);
        
        $teamIds = array();
        foreach($todayMatches as $match)
        {
            $teamIds[] = $match->HostTeam_id;
            $teamIds[] = $match->GuestTeam_id;
        }
        $matchPlayers = PlayerManager::getInstance()->getHealthyPlayers($teamIds);
        foreach($matchPlayers as $k=>$v)
        {
            if (in_array($v->condition_id, array(1, 2)))
            {
                $matchPlayers[$k]->condition_id = 3;
            }
        }
        $matchTeams = TeamManager::getInstance()->getTeams($teamIds);
        
        $teamPlayers = array();
        foreach($matchPlayers as $player)
        {
            $teamId = $player->team_id;
            $teamPlayers[$teamId][] = $player;
        }
        
        //play
        $playedMatchClasses = array();
        foreach ($todayMatches as $match)
        {
            $hostPlayers = PlayerManager::getInstance()->setShoufa($teamPlayers[$match->HostTeam_id], $match->class_id, $matchTeams[$match->HostTeam_id]->formattion);
            $strHtml = '<div style="float:left">';
            $strHtml .= $this->generateZhenrongHtml($hostPlayers);
            $strHtml .= '</div>';
            $this->flushNow($strHtml);
            
            $guestPlayers = PlayerManager::getInstance()->setShoufa($teamPlayers[$match->GuestTeam_id], $match->class_id, $matchTeams[$match->GuestTeam_id]->formattion);
            $strHtml = '<div style="float:right">';
            $strHtml .= $this->generateZhenrongHtml($guestPlayers);
            $strHtml .= '</div><div style="clear:both"></div>';
            $this->flushNow($strHtml);
        
            $this->start($match, $hostPlayers, $guestPlayers, $matchTeams[$match->HostTeam_id], $matchTeams[$match->GuestTeam_id]);
            $match->isPlayed = 1;
            if (!in_array($match->class_id, $playedMatchClasses, true))
            {
                $playedMatchClasses[] = $match->class_id;
            }
        }
        
        //save
        MatchManager::getInstance()->saveMany($todayMatches);
		
		/*体力为0的变成伤员*/
        PlayerManager::getInstance()->update(array("condition_id"=>"4", 'InjuredDay'=>6), array('sinew <' => 0));
    }
    
    private function start(&$curMatch, &$hostPlayers, &$guestPlayers, &$hostTeam, &$guestTeam)
    {
        $this->isWatch = $curMatch->isWatched;
        $assaultCount = ($hostTeam->attack + $guestTeam->attack) / 15;
		for ($i = 0; $i < $assaultCount; $i++)
		{
			if ($i == $assaultCount - 1)
			{
				$this->lastAttack = true;
			}
			$this->assault($curMatch, $hostPlayers, $guestPlayers, $hostTeam, $guestTeam);
		}
        $this->flushNow('the match is over.<br/>');
        
        $this->onMatchEnd($curMatch, $hostTeam, $guestTeam);
        
        TeamManager::getInstance()->saveMatchInfo($hostTeam);
        TeamManager::getInstance()->saveMatchInfo($guestTeam);
    }
    
    private function onMatchEnd(&$curMatch, &$hostTeam, &$guestTeam)
    {
        if (in_array($curMatch->class_id, array(1, 31)))
        {
            $hostTeam->goals += $curMatch->HostGoals;
            $hostTeam->lost += $curMatch->GuestGoals;
            $guestTeam->goals += $curMatch->GuestGoals;
            $guestTeam->lost += $curMatch->HostGoals;
            
            if ($curMatch->HostGoals > $curMatch->GuestGoals)
            {
                $hostTeam->score += 3;
                $hostTeam->win++;
                $guestTeam->lose++;
            }
            else if ($curMatch->HostGoals < $curMatch->GuestGoals)
            {
                $guestTeam->score += 3;
                $hostTeam->lose++;
                $guestTeam->win++;
            }
            else
            {
                $hostTeam->score += 1;
                $guestTeam->score += 1;
                $hostTeam->draw++;
                $guestTeam->draw++;
            }
        }
    }
    
    private function assault(&$curMatch, &$hostPlayers, &$guestPlayers, $hostTeam, $guestTeam)
    {
        $strDir = array('1'=>'left side', '2'=>'middle', '3'=>'right side');
        $attackDir = mt_rand(1, 3);
        $attackPlayers = array();
        $defensePlayers = array();
        $attackTeam = array();
        $defenseTeam = array();
        $needTurn = false;
        if ($curMatch->getFaqiuquan())
        {
            $attackPlayers = $hostPlayers;
            $defensePlayers = $guestPlayers;
            $attackTeam = $hostTeam;
            $defenseTeam = $guestTeam;
        }
        else
        {
            $attackPlayers = $guestPlayers;
            $defensePlayers = $hostPlayers;
            $attackTeam = $guestTeam;
            $defenseTeam = $hostTeam;
        }
        
        $this->flushNow('<br/>' . $attackTeam->name . " attack from " . $strDir[$attackDir] . '，');
        $collisionResult = PlayerManager::getInstance()->collision($attackDir, $attackPlayers['shoufa'], $defensePlayers['shoufa']);
        if ($collisionResult['result'])
        {
            $this->flushNow($attackPlayers['shoufa'][$collisionResult['attackerIndex']]->name .  'break succes，');
            switch (mt_rand(1, 2)) 
            {
                case 1: //pass
                    $this->flushNow($attackPlayers['shoufa'][$collisionResult['attackerIndex']]->name .  'pass bal，');
                    $shotResult = PlayerManager::getInstance()->shot($collisionResult['attackerIndex'], $attackPlayers, $defensePlayers, $attackDir);
                    $this->flushNow($attackPlayers['shoufa'][$shotResult['shoterIndex']]->name . ' shot,');
                    
                    switch ($shotResult['result']) 
                    {
                        case 1:
                            $this->flushNow(' goal<br/>');
                            $curMatch->saveGoal();
                            $needTurn = true;
                            break;
                        case 2:
                            $this->flushNow($defensePlayers['shoufa'][$shotResult['goalkeeperIndex']]->name . ' saved<br/>');
                            $this->flushNow('corner,');
                            $needTurn = $this->corner($attackPlayers, $defensePlayers, $attackTeam->CornerKicker_id, $curMatch);
                            break;
                        case 3:
                            $this->flushNow($defensePlayers['shoufa'][$shotResult['goalkeeperIndex']]->name . ' saved<br/>');
                            $this->flushNow('fan ji');
                            break;
                    }
                    break;
                case 2: //foul
                    $this->flushNow($defensePlayers['shoufa'][$collisionResult['defenserIndex']]->name . ' foul,');
                    break;
            } 
        }
        else
        {
            $this->flushNow($defensePlayers['shoufa'][$collisionResult['defenserIndex']]->name . 'defense succes,');
        }
        
        if ($curMatch->getFaqiuquan())
        {
            $hostPlayers = $attackPlayers;
            $guestPlayers = $defensePlayers;
        }
        else
        {
            $guestPlayers = $attackPlayers;
            $hostPlayers = $defensePlayers;
        }
        
        if ($needTurn)
        {
            $curMatch->turnFaqiuquan();
        }
    }
    
    private function corner(&$attackPlayers, &$defensePlayers, $cornerKickerId, &$curMatch)
    {
        $needTurn = false;
        $cornerKickerIndex = PlayerManager::getInstance()->getCornerKickerIndex($attackPlayers['shoufa'], $cornerKickerId);
        $this->flushNow($attackPlayers['shoufa'][$cornerKickerIndex]->name . ' kick corner，');
        $cornerResult = PlayerManager::getInstance()->qiangdian($attackPlayers['shoufa'], $defensePlayers['shoufa'], $cornerKickerId, mt_rand(1, 4));
        switch ($cornerResult['result']) 
        {
            case 1:
                $this->flushNow($attackPlayers['shoufa'][$cornerResult['headerIndex']]->name . ' touqiugongmen，goal.');
                $curMatch->saveGoal();
                $needTurn = true;
                break;
            case 2:
                $this->flushNow($defensePlayers['shoufa'][$cornerResult['headerIndex']]->name . ' pohuai，');
                $needTurn = true;
                break;
            case 3:
                $this->flushNow($attackPlayers['shoufa'][$cornerResult['headerIndex']]->name . ' touqiugongmen，' . $defensePlayers['shoufa'][$cornerResult['goalkeeperIndex']]->name . ' pu chu le.');
                $needTurn = true;
                break;
            case 4:
                $this->flushNow("nobody get the position, men qiu");
                $needTurn = true;
                break;
        }
        
        return $needTurn;
    }
    
    private function generateZhenrongHtml($players)
    {
        $str = "<table>";

        foreach($players['shoufa'] as $player)
        {
            $str .= '<tr><td>' . $player->ShirtNo . "</td><td>" . $player->name . "</td><td>" . $player->condition_id . '</td></tr>';
        }

        $str .= '</table>';
        
        return $str;
    }
    
    public function watch($id)
    {
        MatchManager::getInstance()->watch($id);
        echo 1;
    }
    
    protected function flushNow($str)
    {
        if ($this->isWatch)
        {
            parent::flushNow($str);
        }
    }
}