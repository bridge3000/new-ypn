<?php
namespace Controller;
use MainConfig;
use Controller\AppController;
use Model\Core\Player;
use Model\Core\News;
use Model\Core\Match;
use Model\Core\Team;
use Model\Manager\MatchManager;
use Model\Manager\TeamManager;
use Model\Manager\SettingManager;
use Model\Manager\PlayerManager;
use Model\Manager\CoachManager;
use Model\Manager\NewsManager;
use Model\Manager\YpnManager;
use Model\Manager\UclGroupManager;
use Model\Manager\ElGroupManager;

class MatchController extends AppController 
{
    public $name = "Match";
    public $layout = "main";
    private $isWatch = 0;
	private $replay = '';
    
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
		
        $todayMatchTeamIds = array();
		$matchClassIds = array();
        foreach($todayMatches as $curMatch)
        {
            $todayMatchTeamIds[] = $curMatch->HostTeam_id;
            $todayMatchTeamIds[] = $curMatch->GuestTeam_id;
			if(!in_array($curMatch->class_id, $matchClassIds))
			{
				$matchClassIds[] = $curMatch->class_id;
			}
        }
        $matchPlayers = PlayerManager::getInstance()->getHealthyPlayers($todayMatchTeamIds);
        $matchTeams = TeamManager::getInstance()->getTeams($todayMatchTeamIds);
        
        $teamPlayers = array();
        foreach($matchPlayers as $player)
        {
            $teamId = $player->team_id;
            $teamPlayers[$teamId][] = $player;
        }
		
		$allMatchHtml = '';
        
        //play
        foreach ($todayMatches as $curMatch)
        {
			$this->replay = '';
			$curMatch->hostTeam = $matchTeams[$curMatch->HostTeam_id];
			$curMatch->guestTeam = $matchTeams[$curMatch->GuestTeam_id];
			$curMatch->hostPlayers['shoufa'] = [];
			$curMatch->guestPlayers['shoufa'] = [];
			
			if(!isset($teamPlayers[$curMatch->HostTeam_id]))
			{
				echo $curMatch->hostTeam->name.' 没有球员 无法比赛';
				$curMatch->GuestGoals = 2;
				$this->onMatchEnd($curMatch);
				continue;
			}
			elseif(!isset($teamPlayers[$curMatch->GuestTeam_id]))
			{
				echo $curMatch->guestTeam->name.' 没有球员 无法比赛';
				$curMatch->HostGoals = 2;
				$this->onMatchEnd($curMatch);
				continue;
			}
			
			$this->isWatch = $curMatch->isWatched;
            $curMatch->hostPlayers = PlayerManager::getInstance()->setShoufa($teamPlayers[$curMatch->HostTeam_id], $curMatch->class_id, $curMatch->hostTeam->formattion, $curMatch->hostTeam->is_auto_format);
            $strHtml = '<div class="shoufa_div">';
            $strHtml .= $this->generateZhenrongHtml($curMatch->hostPlayers, $curMatch->hostTeam);
            $strHtml .= '</div>';
            $this->flushMatch($strHtml);
            
            $curMatch->guestPlayers = PlayerManager::getInstance()->setShoufa($teamPlayers[$curMatch->GuestTeam_id], $curMatch->class_id, $curMatch->guestTeam->formattion, $curMatch->guestTeam->is_auto_format);
            $strHtml = '<div class="shoufa_div">';
            $strHtml .= $this->generateZhenrongHtml($curMatch->guestPlayers, $curMatch->guestTeam);
            $strHtml .= '</div><div style="clear:both"></div>';
            $this->flushMatch($strHtml);
			
			PlayerManager::getInstance()->clearPunish(array($curMatch->HostTeam_id,$curMatch->GuestTeam_id), $curMatch->class_id);
			
            $this->start($curMatch);
			
			if($curMatch->isWatched)
			{
				$allMatchHtml .= $this->replay.'<hr>';
			}
			
			$allMatchHtml .= $this->onMatchEnd($curMatch);
        }
        
        PlayerManager::getInstance()->update(array("condition_id"=>"4", 'InjuredDay'=>6), array('sinew <' => 0)); //体力为0的变成伤员

		$this->set('allMatchHtml', $allMatchHtml);
		$this->render('play');
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
		$this->flushMatch('the match is over, ' . $curMatch->hostTeam->name . $curMatch->HostGoals . ":" . $curMatch->GuestGoals . $curMatch->guestTeam->name . "<br>");
    }
	
	private function onMatchEnd($curMatch)
    {
		$html = '';
		$curMatch->isPlayed = 1;

		$mvpPlayer = NULL;
		$maxScore = 0;
		foreach($curMatch->hostPlayers['shoufa'] as &$p)
		{
			$p->sinew -= 30;
			if($p->cooperate < 100)
			{
				$p->cooperate += 2;
			}
			if($p->cooperate > 100)
			{
				$p->cooperate = 100;
			}
			
			if($p->score > $maxScore)
			{
				$mvpPlayer = $p;
			}
		}
		$mvpPlayer->total_score += 1;
		
		foreach($curMatch->guestPlayers['shoufa'] as &$p)
		{
			$p->sinew -= 30;
			if($p->cooperate < 100)
			{
				$p->cooperate += 2;
			}
			if($p->cooperate > 100)
			{
				$p->cooperate = 100;
			}
			
			if($p->score > $maxScore)
			{
				$mvpPlayer = $p;
			}
		}
		
		$html .= "本场比赛的MVP是{$mvpPlayer->name}";
		
		//player需要更新的属性需要在下面的函数中写入列表
		PlayerManager::getInstance()->saveMatchResult($curMatch->hostPlayers['shoufa'], $curMatch->guestPlayers['shoufa']);
		
		$result = 0; //1host win 2guestwin 3draw 
		if ($curMatch->HostGoals > $curMatch->GuestGoals)
		{
			$result = 1;
		}
		else if ($curMatch->HostGoals < $curMatch->GuestGoals)
		{
			$result = 2;
		}
		else
		{
			$result = 3;
		}
			
        if (in_array($curMatch->class_id, array(1, 31))) //league
        {
            $curMatch->hostTeam->goals += $curMatch->HostGoals;
            $curMatch->hostTeam->lost += $curMatch->GuestGoals;
            $curMatch->guestTeam->goals += $curMatch->GuestGoals;
            $curMatch->guestTeam->lost += $curMatch->HostGoals;
            
            if ($result == 1)
            {
                $curMatch->hostTeam->score += 3;
                $curMatch->hostTeam->win++;
                $curMatch->guestTeam->lose++;
            }
            else if ($result == 2)
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
			
			$curMatch->hostTeam->save();
			$curMatch->guestTeam->save();
        }
		else if ($curMatch->class_id == 3) //ucl
		{
			UclGroupManager::getInstance()->saveResult($curMatch->hostTeam->id, $curMatch->guestTeam->id, $result);
		}
		else if ($curMatch->class_id == 12) //el
		{
			ElGroupManager::getInstance()->saveResult($curMatch->hostTeam->id, $curMatch->guestTeam->id, $result);
		}
		
		unset($curMatch->hostTeam);
		unset($curMatch->guestTeam);
		unset($curMatch->hostPlayers);
		unset($curMatch->guestPlayers);
		
		$curMatch->mvp_player_id = $mvpPlayer->id;
		$curMatch->replay = $this->replay;
		$curMatch->save();
		
		$count = MatchManager::getInstance()->find('count', array(
			'conditions' => array(
				'class_id'=>$curMatch->class_id,
				'isPlayed'=>0
				)
		));
		
		if($count == 0)
		{
			switch ($curMatch->class_id) 
			{
				case 1: //league over
				case 31:
					$html .= $this->onLeagueEnd($curMatch->class_id);
					break;
				case 3: //ucl
					$html .= $this->onUclTeamEnd();
					break;
				case 4: //欧冠16进8
					$html .= $this->onUclRoundOf16End();
					break;
				case 5: //
					$html .= $this->onUclQuarterFinalsEnd();
					break;
				case 6: //
					$html .= $this->onUclSemiFinalsEnd();
					break;
				case 7: //
					$html .= $this->onUclFinalEnd($curMatch);
					break;
				case 12: //el
					$html .= $this->onElTeamEnd();
					break;
				case 36: //亚冠半决
					$html .= $this->onAfcHalfEnd();
					break;
				case 37: 
					$html .= $this->onAfcFinalEnd();
					break;
				case 20: //世俱半决
					$html .= $this->onFcwcHalfEnd();
					break;
				case 22: //世俱决
					$html .= $this->onFcwcFinalEnd();
					break;
			}
		}
		
		return $html;
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
			
			if(mt_rand(1,5) == 1) //free
			{
				$freeResult = PlayerManager::getInstance()->free($attackPlayers['shoufa'], $defensePlayers['shoufa'], $attackTeam->FreeKicker_id, $curMatch->class_id);
				$this->flushMatch($freeResult['free_kicker']->name . '任意球射门');
				if($freeResult['result'] == 1)
				{
					$this->flushMatch('goal<br>');
					$this->goal($curMatch);
				}
				else
				{
					$this->flushMatch($freeResult['goal_keeper']->name . '扑救成功<br>');
				}
				$needTurn = TRUE;
			}
			else if(mt_rand(1,10) == 1) //penalty
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
	
	private function onLeagueEnd($classId)
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		$match2leagueMap = array(1=>1, 31=>3);
		$leagueId = $match2leagueMap[$classId];
		$mvpPlayer = PlayerManager::getInstance()->find('first', array(
			'conditions' => array('league_id'=>$leagueId),
			'order' => array('total_score'=>'desc'),
			'fields' => array('id', 'name', 'ImgSrc')
		));
		
		$leagueTeams = TeamManager::getInstance()->find('all', array(
			'conditions' => array('league_id'=>$leagueId),
			'fields' => array('id', 'name'),
			'order' => array('score'=>'desc')
		));
		
		foreach($leagueTeams as $t)
		{
			$msg = 'champion' . $leagueTeams[0]['name'] . '，mvp' . $mvpPlayer['name'];
			News::create($msg, $t['id'], $nowDate, $mvpPlayer['ImgSrc']);
		}
	}
	
	private function onUclTeamEnd()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		$nextYear = date('Y', strtotime($nowDate)) + 1;
		$groups = array('a'=>0, 'b'=>1, 'c'=>2, 'd'=>3, 'e'=>4, 'f'=>5, 'g'=>6, 'h'=>7);
		$uclGroupTeams = UclGroupManager::getInstance()->find('all', array(
				'order' => array('score'=>'desc')
			)				
		);
		$alTeamIds = array();
		$successTeamIds = array();
		foreach($uclGroupTeams as $u)
		{
			$alTeamIds[$groups[$u['GroupName']]][] = $u['team_id'];
		}
		
		$reward = 350;
		$nextMatchClassId = 4;
		$matchPairs = array();
		for($i=0;$i<8;$i+=2)
		{
			$matchPairs[] = array(array($alTeamIds[$i][0], $alTeamIds[($i+1)][1]), array($alTeamIds[$i][1], $alTeamIds[($i+1)][0])); //两match一组，同日进行
			array_push($successTeamIds, $alTeamIds[$i][0], $alTeamIds[($i+1)][1], $alTeamIds[$i][1], $alTeamIds[($i+1)][0]);
		}
		
		foreach($matchPairs as $k=>$mp)
		{
			$playDate1 = $nextYear . '-' . MainConfig::$uclPlayoffDates[8][$k][0];
			$playDate2 = $nextYear . '-' . MainConfig::$uclPlayoffDates[8][$k][1];
			
			$hostTeamId = $mp[0][0];
			$guestTeamId = $mp[0][1];
			MatchManager::getInstance()->push($hostTeamId, $guestTeamId, $nextMatchClassId, $playDate1);
			MatchManager::getInstance()->push($guestTeamId, $hostTeamId, $nextMatchClassId, $playDate2);
			
			$hostTeamId = $mp[1][0];
			$guestTeamId = $mp[1][1];
			MatchManager::getInstance()->push($hostTeamId, $guestTeamId, $nextMatchClassId, $playDate1);
			MatchManager::getInstance()->push($guestTeamId, $hostTeamId, $nextMatchClassId, $playDate2);
		}
		
		MatchManager::getInstance()->insertBatch();
		
		//reward and news
		$msg = '晋级16，prize=' . $reward . 'W';
		
		foreach($successTeamIds as $teamId)
		{
			NewsManager::getInstance()->push($msg, $teamId, $nowDate, '/res/img/EuroChampion.jpg');
		}
		
		$successTeamArr = TeamManager::getInstance()->find('all', array(
			'conditions' => array('id'=>$successTeamIds),
			'fields' => array('id', 'money', 'bills')
		));
		
		$successTeams = TeamManager::getInstance()->loadData($successTeamArr);
		foreach($successTeams as $t)
		{
			$t->addMoney($reward, "欧冠晋级16强", $nowDate);
		}
		TeamManager::getInstance()->saveMany($successTeams);
	}
	
	private function onUclEighthFinalEnd()
	{
		$finalTimes = array('11-19', '11-26');
		$nowDate = SettingManager::getInstance()->getNowDate();
		$thisYear = date('Y', strtotime($nowDate));
		$forthFinalClassId = 5;
		$matches = MatchManager::getInstance()->find('all', array(
			'conditions' => array('class_id'=>4)
		));
		
		$winTeamIds = array(); //8team
		foreach($matches as $m1)
		{
			if(in_array($m1['HostTeam_id'], $winTeamIds) || in_array($m1['GuestTeam_id'], $winTeamIds))
			{
				continue;
			}
			
			foreach($matches as $m2)
			{
				if($m2['GuestTeam_id'] == $m1['HostTeam_id'])
				{
					$winTeamIds[] = MatchManager::getInstance()->diff($m1, $m2);
					break;
				}
			}
		}
		
		$winTeamCount = count($winTeamIds);
		for($i=0;$i<$winTeamCount;$i+=2)
		{
			MatchManager::getInstance()->push($winTeamIds[$i], $winTeamIds[$i+1], $forthFinalClassId, $thisYear . '-' . MainConfig::$uclPlayoffDates[4][floor($i/4)][0]);
			MatchManager::getInstance()->push($winTeamIds[$i+1], $winTeamIds[$i], $forthFinalClassId, $thisYear . '-' . MainConfig::$uclPlayoffDates[4][floor($i/4)][1]);
		}
		
		MatchManager::getInstance()->insertBatch();
		
		$reward = 390;
		$moneyMsg = "欧冠晋级16强";
		TeamManager::getInstance()->addMoneyBatch($winTeamIds, $reward, $moneyMsg, $nowDate);
		
		$newsMsg = '晋级欧冠四分之一决赛了';
		foreach($winTeamIds as $teamId)
		{
			NewsManager::getInstance()->push($newsMsg, $teamId, $nowDate, '/res/img/EuroChampion.jpg');
		}
	}
	
	private function onAfcHalfEnd()
	{
		$finalTimes = array('11-19', '11-26');
		$nowDate = SettingManager::getInstance()->getNowDate();
		$thisYear = date('Y', strtotime($nowDate));
		$finalClassId = 37;
		$matches = MatchManager::getInstance()->find('all', array(
			'conditions' => array('class_id'=>36)
		));
		
		$winTeams = array();
		foreach($matches as $m1)
		{
			if(in_array($m1['HostTeam_id'], $winTeams) || in_array($m1['GuestTeam_id'], $winTeams))
			{
				continue;
			}
			
			foreach($matches as $m2)
			{
				if($m2['GuestTeam_id'] == $m1['HostTeam_id'])
				{
					$winTeams[] = MatchManager::getInstance()->diff($m1, $m2);
					break;
				}
			}
		}
		
		MatchManager::getInstance()->push($winTeams[0], $winTeams[1], $finalClassId, $thisYear . '-' . $finalTimes[0]);
		MatchManager::getInstance()->push($winTeams[1], $winTeams[0], $finalClassId, $thisYear . '-' . $finalTimes[1]);
		MatchManager::getInstance()->insertBatch();
		
		$msg = '晋级亚冠决赛了';
		
		foreach($winTeams as $teamId)
		{
			NewsManager::getInstance()->push($msg, $teamId, $nowDate, '/res/img/afc.jpg');
		}
	}
	
	private function onAfcFinalEnd()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		$matches = MatchManager::getInstance()->find('all', array(
			'conditions' => array('class_id'=>37)
		));
		
		$winReward = 300;
		$loseReward = 150;
		
		$winTeamId = MatchManager::getInstance()->diff($matches[0], $matches[1]);
		$loseTeamId = ($matches[0]['HostTeam_id'] == $winTeamId) ? $matches[0]['GuestTeam_id'] : $matches[0]['HostTeam_id'];
		
		$winTeamArr = TeamManager::getInstance()->findById($winTeamId, array(
			'fields' => array('id', 'money', 'bills', 'popular')
		));
		$winTeam = TeamManager::getInstance()->loadOne($winTeamArr);
		$winTeam->popular += 2;
		$winTeam->addMoney($winReward, '亚冠冠军奖金', $nowDate);
		TeamManager::getInstance()->saveModel($winTeam);
		
		$loseTeamArr = TeamManager::getInstance()->findById($loseTeamId, array(
			'fields' => array('id', 'money', 'bills', 'popular')
		));
		$loseTeam = TeamManager::getInstance()->loadOne($loseTeamArr);
		$loseTeam->popular += 1;
		$loseTeam->addMoney($loseReward, '亚冠亚军奖金', $nowDate);
		TeamManager::getInstance()->saveModel($loseTeam);
		
		NewsManager::getInstance()->add('亚冠联赛冠军', $winTeamId, $nowDate, '/res/img/afc.jpg');
		NewsManager::getInstance()->add('亚冠联赛亚军', $loseTeamId, $nowDate, '/res/img/afc.jpg');
	}
	
	private function onFcwcHalfEnd()
	{
		$finalTime = '12-20'; //final和third final都是同一天
		$nowDate = SettingManager::getInstance()->getNowDate();
		$thisYear = date('Y', strtotime($nowDate));
		$finalClassId = 22;
		$thirdFinalClassId = 21;
		$matches = MatchManager::getInstance()->find('all', array(
			'conditions' => array('class_id'=>20)
		));
		
		$winTeams = array();
		$loseTeams = array();
		foreach($matches as $m1)
		{
			if($m1['HostGoals'] > $m1['GuestGoals'])
			{
				$winTeams[] = $m1['HostTeam_id'];
				$loseTeams[] = $m1['GuestTeam_id'];
			}
			else
			{
				$winTeams[] = $m1['GuestTeam_id'];
				$loseTeams[] = $m1['HostTeam_id'];
			}
		}
		
		MatchManager::getInstance()->push($winTeams[0], $winTeams[1], $finalClassId, $thisYear . '-' . $finalTime);
		MatchManager::getInstance()->push($loseTeams[0], $loseTeams[1], $thirdFinalClassId, $thisYear . '-' . $finalTime);
		MatchManager::getInstance()->insertBatch();
		
		$msg = '晋级世俱杯决赛了';
		foreach($winTeams as $teamId)
		{
			NewsManager::getInstance()->push($msg, $teamId, $nowDate, '/res/img/afc.jpg');
		}
	}
	
	private function onFcwcFinalEnd()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		$matches = MatchManager::getInstance()->find('all', array(
			'conditions' => array('class_id'=>22)
		));
		
		$winReward = 300;
		$loseReward = 150;
		
		if($matches[0]['HostGoals'] > $matches[0]['GuestGoals'])
		{
			$winTeamId = $matches[0]['HostTeam_id'];
			$loseTeamId = $matches[0]['GuestTeam_id'];
		}
		else
		{
			$winTeamId = $matches[0]['GuestTeam_id'];
			$loseTeamId = $matches[0]['HostTeam_id'];
		}
		
		$winTeamArr = TeamManager::getInstance()->findById($winTeamId, array(
			'fields' => array('id', 'money', 'bills', 'popular')
		));
		$winTeam = TeamManager::getInstance()->loadOne($winTeamArr);
		$winTeam->popular += 5;
		$winTeam->addMoney($winReward, '世俱杯冠军奖金', $nowDate);
		TeamManager::getInstance()->saveModel($winTeam);
		
		$loseTeamArr = TeamManager::getInstance()->findById($loseTeamId, array(
			'fields' => array('id', 'money', 'bills', 'popular')
		));
		$loseTeam = TeamManager::getInstance()->loadOne($loseTeamArr);
		$loseTeam->popular += 1;
		$loseTeam->addMoney($loseReward, '世俱杯亚军奖金', $nowDate);
		TeamManager::getInstance()->saveModel($loseTeam);
		
		NewsManager::getInstance()->add('世俱杯冠军', $winTeamId, $nowDate, '/res/img/fifa.gif');
		NewsManager::getInstance()->add('世俱杯亚军', $loseTeamId, $nowDate, '/res/img/fifa.gif');
	}
	
	private function onElTeamEnd()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		$nextYear = date('Y', strtotime($nowDate)) + 1;
		$groups = array('a'=>0, 'b'=>1, 'c'=>2, 'd'=>3, 'e'=>4, 'f'=>5, 'g'=>6, 'h'=>7, 'i'=>8, 'j'=>9, 'k'=>10, 'l'=>11);
		$elGroupTeams = ElGroupManager::getInstance()->find('all', array(
			'order' => array('score'=>'desc')
		));
		$alTeamIds = array();
		$successTeamIds = array();
		foreach($elGroupTeams as $u)
		{
			$alTeamIds[$groups[$u['GroupName']]][] = $u['team_id']; //按组为键，把teamid存入数组
		}
		
		$reward = 35;
		$nextMatchClassId = 13;
		
		foreach($alTeamIds as $groupTeamIds)
		{
			$successTeamIds[] = $groupTeamIds[0];
			$successTeamIds[] = $groupTeamIds[1];
		}
		
		//ucl 的第三
		$uclThirdTeamIds = UclGroupManager::getInstance()->getThirdTeamIds();
		$successTeamIds = array_merge($successTeamIds, $uclThirdTeamIds);
		$successTeamCount = count($successTeamIds);
		
		for($i=0;$i<$successTeamCount;$i+=2)
		{
			$playDate1 = $nextYear . '-' . MainConfig::$elPlayoffDates[16][floor($i*2/$successTeamCount)][0];
			$playDate2 = $nextYear . '-' . MainConfig::$elPlayoffDates[16][floor($i*2/$successTeamCount)][1];
			
			$hostTeamId = $successTeamIds[$i];
			$guestTeamId = $successTeamIds[$i+1];
			MatchManager::getInstance()->push($hostTeamId, $guestTeamId, $nextMatchClassId, $playDate1);
			MatchManager::getInstance()->push($guestTeamId, $hostTeamId, $nextMatchClassId, $playDate2);
		}
		
		MatchManager::getInstance()->insertBatch();
		
		//reward and news
		$msg = '欧联杯晋级32，prize=' . $reward . 'W';
		
		foreach($successTeamIds as $teamId)
		{
			NewsManager::getInstance()->push($msg, $teamId, $nowDate, '/res/img/EuroChampion.jpg');
		}
		
		$successTeamArr = TeamManager::getInstance()->find('all', array(
			'conditions' => array('id'=>$successTeamIds),
			'fields' => array('id', 'money', 'bills')
		));
		
		$successTeams = TeamManager::getInstance()->loadData($successTeamArr);
		foreach($successTeams as $t)
		{
			$t->addMoney($reward, "欧联杯晋级32强", $nowDate);
		}
		TeamManager::getInstance()->saveMany($successTeams);
	}
    
    private function corner(&$attackPlayers, &$defensePlayers, $cornerKickerId, &$curMatch)
    {
        $needTurn = false;
        $cornerKickerIndex = PlayerManager::getInstance()->getCornerKickerIndex($attackPlayers['shoufa'], $cornerKickerId);
        $this->flushMatch($attackPlayers['shoufa'][$cornerKickerIndex]->name . ' kick corner，');
        $cornerResult = PlayerManager::getInstance()->qiangdian($attackPlayers['shoufa'], $defensePlayers['shoufa'], $attackPlayers['shoufa'][$cornerKickerIndex]->id, mt_rand(1, 4));
        switch ($cornerResult['result']) 
        {
            case 1:
                $this->flushMatch($attackPlayers['shoufa'][$cornerResult['headerIndex']]->name . ' touqiugongmen，goal.');
                $this->goal($curMatch);
                $needTurn = true;
                break;
            case 2:
                $this->flushMatch($attackPlayers['shoufa'][$cornerResult['headerIndex']]->name . ' touqiugongmen，' . $defensePlayers['shoufa'][$cornerResult['goalkeeperIndex']]->name . ' pu chu le.');
                $needTurn = true;
                break;
			case 3:
                $this->flushMatch($defensePlayers['shoufa'][$cornerResult['headerIndex']]->name . ' pohuai，');
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
//            $this->flushNow($str);
        }
		
		$this->replay .= $str;
    }
	
	public function ajax_get_my_next()
	{
		$myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
		
		$nowDate = SettingManager::getInstance()->getNowdate();
		$weekarray = array("日","一","二","三","四","五","六"); //先定义一个数组
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
				MatchManager::getInstance()->saveModel($newMatch, 'insert');
				$result = 0;
			}
			else
			{
				$result = -1;
			}
		}
		
		echo json_encode(array('result'=>$result));
	}
	
	private function onUclRoundOf16End()
	{
		$curClassId = 4;
		$nextClassId = 5;

		$html = $this->generateNextLevelMatch($curClassId, $nextClassId, MainConfig::$uclPlayoffDates[4]);
		return $html;
	}
		
	private function onUclQuarterFinalsEnd()
	{
		$curClassId = 5;
		$nextClassId = 6;

		$html = $this->generateNextLevelMatch($curClassId, $nextClassId, MainConfig::$uclPlayoffDates['half']);
		return $html;
	}
	
	private function onUclSemiFinalsEnd()
	{
		$curClassId = 6;
		$nextClassId = 7;

		$html = $this->generateNextLevelMatch($curClassId, $nextClassId, MainConfig::$uclPlayoffDates['final']);
		return $html;
	}
	
	private function onUclFinalEnd($curMatch)
	{
		$html = '';
		$winnerTeamId = 0;
		if($curMatch->HostGoals > $curMatch->GuestGoals)
		{
			$winnerTeamId = $curMatch->HostTeam_id;
		}
		else
		{
			$winnerTeamId = $curMatch->GuestTeam_id;
		}
		$winTeam = Team::getById($winnerTeamId);
		
		$html .= '<div class="alert alert-danger" role="alert">' . $winTeam->name . '获得了欧洲冠军联赛的冠军</div>';
		return $html;
	}
	
	private function generateNextLevelMatch($curClassId, $nextClassId, $playDates)
	{
		$html = '';
		$nowDate = SettingManager::getInstance()->getNowDate();
		$year = date('Y', strtotime($nowDate));
		$winnerTeams = [];
		$matches = Match::find('all', ['conditions'=>['class_id'=>$curClassId]]);
		
		$html .= '<table class="table table-striped"><caption>' . MainConfig::$matchClasses[$curClassId] . '</caption>';
		foreach($matches as $curMatch)
		{
			if(!isset($curMatch->has_turned))
			{
				for($i=0;$i<count($matches);$i++) //查找第二回合的比分
				{
					if($curMatch->HostTeam_id == $matches[$i]->GuestTeam_id)
					{
						$hostTeam = Team::getById($curMatch->HostTeam_id);
						$guestTeam = Team::getById($curMatch->GuestTeam_id);
			
						$nextMatch = $matches[$i];
						if ( ($curMatch->HostGoals+$nextMatch->GuestGoals) > ($curMatch->GuestGoals+$nextMatch->HostGoals))
						{
							$winnerTeams[] = $hostTeam;
						}
						elseif ( ($curMatch->HostGoals+$nextMatch->GuestGoals) < ($curMatch->GuestGoals+$nextMatch->HostGoals))
						{
							$winnerTeams[] = $guestTeam;
						}
						else
						{
							if($curMatch->GuestGoals > $nextMatch->GuestGoals)
							{
								$winnerTeams[] = $guestTeam;
							}
							else
							{
								$winnerTeams[] = $hostTeam;
							}
						}
						
						$curMatch->has_turned = 1;
						$nextMatch->has_turned = 1;
						
						$html .= '<tr><td>' . $hostTeam->name . '</td><td>' . $curMatch->HostGoals  . ' : ' . $curMatch->GuestGoals . '</td><td>' . $guestTeam->name . '</td></tr>';
						$html .= '<tr><td>' . $guestTeam->name . '</td><td>' . $nextMatch->HostGoals  . ' : ' . $nextMatch->GuestGoals . '</td><td>' . $hostTeam->name . '</td></tr>';
					}
				}
			}
		}
		$html .= '</table><hr />';
		
		shuffle($winnerTeams);
		$winerCnt = count($winnerTeams);
		
		$html .= '<table class="table table-striped"><caption>' . MainConfig::$matchClasses[$nextClassId] . '</caption>';
		for($i=0;$i<$winerCnt/2;$i++)
		{
			$hostTeam = $winnerTeams[$i];
			$guestTeam = $winnerTeams[$winerCnt-1-$i];
			
			$firstMatch = new Match();
			$firstMatch->HostTeam_id = $hostTeam->id;
			$firstMatch->GuestTeam_id = $guestTeam->id;
			$firstMatch->class_id = $nextClassId;
			$firstMatch->PlayTime = $year . '-' . $playDates[0];
			$firstMatch->host_team_id = isset($playDates[1]) ? $hostTeam->id : 0;
			$firstMatch->save();
			
			if(isset($playDates[1]))
			{
				$secondMatch = new Match();
				$secondMatch->HostTeam_id = $guestTeam->id;
				$secondMatch->GuestTeam_id = $hostTeam->id;
				$secondMatch->class_id = $nextClassId;
				$secondMatch->PlayTime = $year . '-' . $playDates[1];
				$secondMatch->host_team_id = $guestTeam->id;
				$secondMatch->save();
			}
			
			$html .= '<tr><td>' . $hostTeam->name . '</td><td> VS </td><td>' . $guestTeam->name . '</td></tr>';
		}
		$html .= '</table>';
	
		return $html;
	}
}