<?php
namespace Controller;
use MainConfig;
use Controller\AppController;
use Model\Core\Player;
use Model\Manager\MatchManager;
use Model\Manager\TeamManager;
use Model\Manager\SettingManager;
use Model\Manager\PlayerManager;
use Model\Manager\CoachManager;
use Model\Manager\NewsManager;
use Model\Manager\YpnManager;

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
    
    public function play()
    {
		$nowDate = SettingManager::getInstance()->getNowDate();
        $todayMatches = MatchManager::getInstance()->getTodayMatches($nowDate, 0);
		if(empty($todayMatches))
		{
			header("location:".MainConfig::BASE_URL.'ypn/new_day');
			exit;
		}
		else
		{
			$this->flushCss();
		}
		
        $teamIds = array();
        foreach($todayMatches as $curMatch)
        {
            $teamIds[] = $curMatch->HostTeam_id;
            $teamIds[] = $curMatch->GuestTeam_id;
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
        foreach ($todayMatches as $curMatch)
        {
			$curMatch->hostTeam = $matchTeams[$curMatch->HostTeam_id];
			$curMatch->guestTeam = $matchTeams[$curMatch->GuestTeam_id];
			
			$this->isWatch = $curMatch->isWatched;
            $curMatch->hostPlayers = PlayerManager::getInstance()->setShoufa($teamPlayers[$curMatch->HostTeam_id], $curMatch->class_id, $curMatch->hostTeam->formattion);
            $strHtml = '<div class="shoufa_div">';
            $strHtml .= $this->generateZhenrongHtml($curMatch->hostPlayers, $curMatch->hostTeam);
            $strHtml .= '</div>';
            $this->flushMatch($strHtml);
            
            $curMatch->guestPlayers = PlayerManager::getInstance()->setShoufa($teamPlayers[$curMatch->GuestTeam_id], $curMatch->class_id, $curMatch->guestTeam->formattion);
            $strHtml = '<div class="shoufa_div">';
            $strHtml .= $this->generateZhenrongHtml($curMatch->guestPlayers, $curMatch->guestTeam);
            $strHtml .= '</div><div style="clear:both"></div>';
            $this->flushMatch($strHtml);
			
            $this->start($curMatch);
            $curMatch->isPlayed = 1;
            if (!in_array($curMatch->class_id, $playedMatchClasses, true))
            {
                $playedMatchClasses[] = $curMatch->class_id;
            }
			
			PlayerManager::getInstance()->saveMatchResult($curMatch->hostPlayers['shoufa'], $curMatch->guestPlayers['shoufa']);
			
			unset($curMatch->hostTeam);
			unset($curMatch->guestTeam);
			unset($curMatch->hostPlayers);
			unset($curMatch->guestPlayers);
        }
        
        //save
        MatchManager::getInstance()->saveMany($todayMatches);
		
		/*体力为0的变成伤员*/
        PlayerManager::getInstance()->update(array("condition_id"=>"4", 'InjuredDay'=>6), array('sinew <' => 0));
		
		$this->flushNow("<a href=\"" . MainConfig::BASE_URL . 'match/today' . "\">today match</a>");
    }
    
    private function start(&$curMatch)
    {
        $assaultCount = ($curMatch->hostTeam->attack + $curMatch->guestTeam->attack) / 15;
		for ($i = 0; $i < $assaultCount; $i++)
		{
			if ($i == $assaultCount - 1)
			{
				$this->lastAttack = true;
			}
			$this->assault($curMatch);
		}
        $this->flushMatch('the match is over.<br/>');
		$this->flushMatch($curMatch->hostTeam->name . $curMatch->HostGoals . ":" . $curMatch->GuestGoals . $curMatch->guestTeam->name . "<br>");
        
        $this->onMatchEnd($curMatch);
        
        TeamManager::getInstance()->saveMatchInfo($curMatch->hostTeam);
        TeamManager::getInstance()->saveMatchInfo($curMatch->guestTeam);
    }
    
    private function assault(&$curMatch)
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
            $attackPlayers = $curMatch->hostPlayers;
            $defensePlayers = $curMatch->guestPlayers;
            $attackTeam = $curMatch->hostTeam;
            $defenseTeam = $curMatch->guestTeam;
        }
        else
        {
            $attackPlayers = $curMatch->guestPlayers;
            $defensePlayers = $curMatch->hostPlayers;
            $attackTeam = $curMatch->guestTeam;
            $defenseTeam = $curMatch->hostTeam;
        }
        
        $this->flushMatch('<br/>' . $attackTeam->name . "进攻" . $strDir[$attackDir] . '，');
        $collisionResult = PlayerManager::getInstance()->collision($attackDir, $attackPlayers['shoufa'], $defensePlayers['shoufa'], $curMatch->class_id);
        if ($collisionResult['result'] == 1)
        {
            $this->flushMatch($attackPlayers['shoufa'][$collisionResult['attackerIndex']]->name .  '突破成功后传球，');
			$shotResult = PlayerManager::getInstance()->shot($collisionResult['attackerIndex'], $attackPlayers, $defensePlayers, $attackDir, $curMatch->class_id);
			$this->flushMatch($attackPlayers['shoufa'][$shotResult['shoterIndex']]->name . '射门,');

			switch ($shotResult['result']) 
			{
				case 1:
					$this->flushMatch('球进了<br/>');
					$this->goal($curMatch);
					$attackPlayers['shoufa'][$collisionResult['attackerIndex']]->addAssist($curMatch->class_id);
					$needTurn = TRUE;
					break;
				case 2:
					$this->flushMatch($defensePlayers['shoufa'][$shotResult['goalkeeperIndex']]->name . '扑救成功<br/>');
					$this->flushMatch('角球,');
					$needTurn = $this->corner($attackPlayers, $defensePlayers, $attackTeam->CornerKicker_id, $curMatch);
					break;
				case 3:
					$this->flushMatch($defensePlayers['shoufa'][$shotResult['goalkeeperIndex']]->name . '扑救成功<br/>');
					$this->flushMatch('发动反击');
					$needTurn = TRUE;
					break;
            } 
        }
		else if ($collisionResult['result'] == 2)
		{
			$this->flushMatch($defensePlayers['shoufa'][$collisionResult['defenserIndex']]->name . '犯规,');
			$foulResult = $defensePlayers['shoufa'][$collisionResult['defenserIndex']]->foul($curMatch->class_id);
			if($foulResult == 1)
			{
				$this->flushMatch('get yellow card<br>');
			}
			else if($foulResult == 2)
			{
				$this->flushMatch('get 2 yellow card,out<br>');
				unset($defensePlayers['shoufa'][$collisionResult['defenserIndex']]);
			}
			else if($foulResult == 3)
			{
				$this->flushMatch('get red card, out<br>');
				unset($defensePlayers['shoufa'][$collisionResult['defenserIndex']]);
			}
			
			if(mt_rand(1,20) == 1)
			{
				$penaltyResult = PlayerManager::getInstance()->penalty($attackPlayers['shoufa'], $defensePlayers['shoufa'], $attackTeam->PenaltyKicker_id, $curMatch->class_id);
				$this->flushMatch($penaltyResult['penalty_kicker']->name . 'shot');
				if($penaltyResult['result'] == 1)
				{
					$this->flushMatch('goal<br>');
					$this->goal($curMatch);
				}
				else
				{
					$this->flushMatch($penaltyResult['goal_keeper']->name . 'saved<br>');
				}
				$needTurn = TRUE;
			}
		}
        else
        {
            $this->flushMatch($defensePlayers['shoufa'][$collisionResult['defenserIndex']]->name . 'defense succes,');
			$needTurn = TRUE;
        }
        
        if ($curMatch->getFaqiuquan())
        {
            $curMatch->hostPlayers = $attackPlayers;
            $curMatch->guestPlayers = $defensePlayers;
        }
        else
        {
            $curMatch->guestPlayers = $attackPlayers;
            $curMatch->hostPlayers = $defensePlayers;
        }
        
        if ($needTurn)
        {
            $curMatch->turnFaqiuquan();
        }
    }
	
	private function onMatchEnd(&$curMatch)
    {
        if (in_array($curMatch->class_id, array(1, 31)))
        {
            $curMatch->hostTeam->goals += $curMatch->HostGoals;
            $curMatch->hostTeam->lost += $curMatch->GuestGoals;
            $curMatch->guestTeam->goals += $curMatch->GuestGoals;
            $curMatch->guestTeam->lost += $curMatch->HostGoals;
            
            if ($curMatch->HostGoals > $curMatch->GuestGoals)
            {
                $curMatch->hostTeam->score += 3;
                $curMatch->hostTeam->win++;
                $curMatch->guestTeam->lose++;
            }
            else if ($curMatch->HostGoals < $curMatch->GuestGoals)
            {
                $curMatch->guestTeam->score += 3;
                $curMatch->hostTeam->lose++;
                $curMatch->guestTeam->win++;
            }
            else
            {
                $curMatch->hostTeam->score += 1;
                $curMatch->guestTeam->score += 1;
                $curMatch->hostTeam->draw++;
                $curMatch->guestTeam->draw++;
            }
        }
    }
    
    private function corner(&$attackPlayers, &$defensePlayers, $cornerKickerId, &$curMatch)
    {
        $needTurn = false;
        $cornerKickerIndex = PlayerManager::getInstance()->getCornerKickerIndex($attackPlayers['shoufa'], $cornerKickerId);
        $this->flushMatch($attackPlayers['shoufa'][$cornerKickerIndex]->name . ' kick corner，');
        $cornerResult = PlayerManager::getInstance()->qiangdian($attackPlayers['shoufa'], $defensePlayers['shoufa'], $cornerKickerId, mt_rand(1, 4));
        switch ($cornerResult['result']) 
        {
            case 1:
                $this->flushMatch($attackPlayers['shoufa'][$cornerResult['headerIndex']]->name . ' touqiugongmen，goal.');
                $this->goal($curMatch);
                $needTurn = true;
                break;
            case 2:
                $this->flushMatch($defensePlayers['shoufa'][$cornerResult['headerIndex']]->name . ' pohuai，');
                $needTurn = true;
                break;
            case 3:
                $this->flushMatch($attackPlayers['shoufa'][$cornerResult['headerIndex']]->name . ' touqiugongmen，' . $defensePlayers['shoufa'][$cornerResult['goalkeeperIndex']]->name . ' pu chu le.');
                $needTurn = true;
                break;
            case 4:
                $this->flushMatch("nobody get the position, men qiu");
                $needTurn = true;
                break;
        }
        
        return $needTurn;
    }
    
    private function generateZhenrongHtml($players, $curTeam)
    {
        $str = "<div class='title'>" . $curTeam->name . " </div><table class='tb_style_1'>";

        foreach($players['shoufa'] as $player)
        {
            $str .= '<tr><td>' . $player->ShirtNo . "</td><td>" . $player->name . "</td><td>" . MainConfig::$positions[$player->position_id] . '</td></tr>';
        }

        $str .= '</table>';
        
        return $str;
    }
	
	private function goal($curMatch)
	{
		$curMatch->saveGoal();
		$this->flushMatch($curMatch->hostTeam->name . $curMatch->HostGoals . ":" . $curMatch->GuestGoals . $curMatch->guestTeam->name . "<br>");
	}
    
    public function watch($id)
    {
        MatchManager::getInstance()->watch($id);
        echo 1;
    }
	
	public function watch_today()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		MatchManager::getInstance()->watchByDay($nowDate);
		header("location:" . MainConfig::BASE_URL . "match/today");
	}
    
    protected function flushMatch($str)
    {
        if ($this->isWatch)
        {
            $this->flushNow($str);
        }
    }
	
	public function ajax_get_my_next()
	{
		$myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
		
		$nowDate = SettingManager::getInstance()->getNowdate();
		$weekarray=array("日","一","二","三","四","五","六"); //先定义一个数组
		$weekDay = "星期".$weekarray[date("w", strtotime($nowDate))];
		
		$data = MatchManager::getInstance()->getNextUnplayedMatch($myTeamId);
        
        $msg = 'today: ' . $nowDate . $weekDay .  ', ' . $data;
		echo $msg;
	}
	
	public function friend_matches()
	{
		$myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
		$nowDate = SettingManager::getInstance()->getNowDate();
		$teamList = TeamManager::getInstance()->find('list', array(
			'conditions' => array('league_id <>'=>100),
			'fields' => array('id', 'name'),
		));
		
		$friendMatches = MatchManager::getInstance()->find('all', array(
			'conditions' => array(
				'class_id'=>24,
				'or' => array('HostTeam_id'=>$myTeamId, 'GuestTeam_id'=>$myTeamId)
				),
			'order' => array('PlayTime' => 'asc')
			
		));
	
		$this->set('nowDate', $nowDate);
		$this->set('myTeamId', $myTeamId);
		$this->set('friendMatches', $friendMatches);
		$this->set("teamList", $teamList);
		$this->render("friend_matches");
	}
	
	public function ajax_invite_friend_match()
	{
		$myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
		$guestTeamId = $_POST['guest_team_id'];
		$playDate = $_POST['play_date'];
		$nowDate = SettingManager::getInstance()->getNowDate();
		$result = 0;
		
		if(YpnManager::getInstance()->checkHoliday($nowDate))
		{
			$result = -2;
		}
		else
		{
			$guestPlayerCount = PlayerManager::getInstance()->find('count', array(
				'conditions' => array('team_id'=>$guestTeamId)
			));

			if($guestPlayerCount > 11)
			{
				$newMatch['HostTeam_id'] = $myTeamId;
				$newMatch['GuestTeam_id'] = $guestTeamId;
				$newMatch['PlayTime'] = $playDate;
				$newMatch['class_id'] = 24;
				MatchManager::getInstance()->save($newMatch, 'insert');
				$result = 0;
			}
			else
			{
				$result = -1;
			}
		}
		
		echo json_encode(array('result'=>$result));
	}
}