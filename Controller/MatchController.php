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
        $allTeams = TeamManager::getInstance()->find('list', array('fields'=>array('id', 'name')));
		
		$matches = Match::findArray("all", array(
            'conditions' => array(
                'or' => array('HostTeam_id' => $myCoach->team_id, 'GuestTeam_id' => $myCoach->team_id),
                ),
            'fields' => array('id', 'PlayTime', 'HostTeam_id', 'GuestTeam_id', 'isPlayed', 'isWatched', 'class_id', 'HostGoals', 'GuestGoals', 'HostGoaler_ids', 'GuestGoaler_ids'),
            'order'=> array('PlayTime' => 'asc')
            ));
		
		$matchData = [];
		foreach($matches as $match)
		{
			$match['host_team_name'] = $allTeams[$match['HostTeam_id']];
			$match['guest_team_name'] = $allTeams[$match['GuestTeam_id']];
			$matchData[strtotime($match['PlayTime'])] = $match;
		}
		
		$fifaDates = \Model\Core\FifaDate::find('all');
		foreach($fifaDates as $fifaDate)
		{
			$matchData[strtotime($fifaDate->PlayDate)] = [
				'id' => 0,
				'class_id' => 23,
				'PlayTime' => $fifaDate->PlayDate,
				'isPlayed' => 0,
				'isWatched' => 0,
				'host_team_name' => '',
				'guest_team_name' => '',
				] ;
		}
		
		ksort($matchData);
        
        $this->set('matches', $matchData);
        self::render("all");
    }
    
    public function play()
    {
		$nowDate = SettingManager::getInstance()->getNowDate();
        $todayMatches = MatchManager::getInstance()->getTodayMatches($nowDate, 0);
		if(empty($todayMatches))
		{
			$this->redirect('/ypn/new_day');
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
				$allMatchHtml .= $curMatch->hostTeam->name.' 没有球员 无法比赛<br/>';
				$curMatch->GuestGoals = 2;
				$this->onMatchEnd($curMatch, $nowDate);
				continue;
			}
			elseif(!isset($teamPlayers[$curMatch->GuestTeam_id]))
			{
				$allMatchHtml .= $curMatch->guestTeam->name.' 没有球员 无法比赛<br/>';
				$curMatch->HostGoals = 2;
				$this->onMatchEnd($curMatch, $nowDate);
				continue;
			}
			
			$this->isWatch = $curMatch->isWatched;
            $curMatch->hostPlayers = PlayerManager::getInstance()->setShoufa($teamPlayers[$curMatch->HostTeam_id], $curMatch, $curMatch->hostTeam->formattion, $curMatch->hostTeam->is_auto_format);
            $allMatchHtml .= '<div class="shoufa_div">';
            $allMatchHtml .= $this->generateZhenrongHtml($curMatch->hostPlayers, $curMatch->hostTeam);
            $allMatchHtml .= '</div>';
            
            $curMatch->guestPlayers = PlayerManager::getInstance()->setShoufa($teamPlayers[$curMatch->GuestTeam_id], $curMatch, $curMatch->guestTeam->formattion, $curMatch->guestTeam->is_auto_format);
            $allMatchHtml .= '<div class="shoufa_div">';
            $allMatchHtml .= $this->generateZhenrongHtml($curMatch->guestPlayers, $curMatch->guestTeam);
            $allMatchHtml .= '</div><div style="clear:both"></div>';
			
			PlayerManager::getInstance()->clearPunish(array($curMatch->HostTeam_id,$curMatch->GuestTeam_id), $curMatch->class_id);
			
            $allMatchHtml .= $this->start($curMatch);
			
			if($curMatch->isWatched)
			{
				$allMatchHtml .= $this->replay;
			}
			
			$allMatchHtml .= $this->onMatchEnd($curMatch, $nowDate);
        }
        
        PlayerManager::getInstance()->update(array("condition_id"=>"4", 'InjuredDay'=>6), array('sinew <' => 0)); //体力为0的变成伤员

		$this->set('allMatchHtml', $allMatchHtml);
		$this->render('play');
    }
    
    private function start(&$curMatch)
    {
		$strHtml = '';
        $assaultCount = ($curMatch->hostTeam->attack + $curMatch->guestTeam->attack) / 15;
		$perMinutes = ceil(90 / $assaultCount);
		for ($i = 0; $i < $assaultCount; $i++)
		{
			if ($i == $assaultCount - 1)
			{
				$this->lastAttack = true;
			}
			$strHtml .= $this->assault($curMatch, ($i+1)*$perMinutes);
		}
		return $strHtml;
    }
	
	private function onMatchEnd($curMatch, $nowDate)
    {
		$strHtml = '<br/>全场比赛结束, 比分是 ' . $curMatch->hostTeam->name . $curMatch->HostGoals . ":" . $curMatch->GuestGoals . $curMatch->guestTeam->name;
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
				$maxScore = $p->score;
				$mvpPlayer = $p;
			}
		}

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
				$maxScore = $p->score;
				$mvpPlayer = $p;
			}
		}
		
		if($mvpPlayer)
		{
			$mvpPlayer->total_score += 1;
			$strHtml .= ", 本场比赛的MVP是{$mvpPlayer->name}";
			$curMatch->mvp_player_id = $mvpPlayer->id;
		}
		$strHtml .= "<hr>";
		
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
				
		if($curMatch->is_host_park)
		{
			$ticketIncoming = ($curMatch->hostTeam->TicketPrice * $curMatch->hostTeam->seats * $curMatch->hostTeam->popular / 100) / 10000;
			$curMatch->hostTeam->addMoney($ticketIncoming, '票房收入', $nowDate);
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
			
			$curMatch->guestTeam->save();
        }
		elseif($curMatch->class_id == 3) //ucl
		{
			UclGroupManager::getInstance()->saveResult($curMatch->hostTeam->id, $curMatch->guestTeam->id, $result);
		}
		elseif($curMatch->class_id == 12) //el
		{
			ElGroupManager::getInstance()->saveResult($curMatch->hostTeam->id, $curMatch->guestTeam->id, $result);
		}
		elseif($curMatch->class_id == 23) //country friend match
		{
			$this->returnToClub($curMatch->hostTeam);
			$this->returnToClub($curMatch->guestTeam);
		}
		
		$curMatch->hostTeam->save();
		
		unset($curMatch->hostTeam);
		unset($curMatch->guestTeam);
		unset($curMatch->hostPlayers);
		unset($curMatch->guestPlayers);
		
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
					$strHtml .= $this->onLeagueEnd($curMatch->class_id);
					break;
				case 3: //ucl
					$strHtml .= $this->onUclTeamEnd();
					break;
				case 4: //欧冠16进8
					$strHtml .= $this->onUclRoundOf16End();
					break;
				case 5: //
					$strHtml .= $this->onUclQuarterFinalsEnd();
					break;
				case 6: //
					$strHtml .= $this->onUclSemiFinalsEnd();
					break;
				case 7: //
					$strHtml .= $this->onFinalEnd($curMatch);
					break;
				case 12: //el
					$strHtml .= $this->onElTeamEnd();
					break;
				case 13: //el 32to16
					$strHtml .= $this->generateNextLevelMatch(13, 14, MainConfig::$elPlayoffDates[14]);
					break;
				case 14: //el 16to8
					$strHtml .= $this->generateNextLevelMatch(14, 15, MainConfig::$elPlayoffDates[15]);
					break;
				case 15: //el 8to4
					$strHtml .= $this->generateNextLevelMatch(15, 16, MainConfig::$elPlayoffDates[16]);
					break;
				case 16: //el quarter to final
					$strHtml .= $this->generateNextLevelMatch(16, 17, MainConfig::$elPlayoffDates[17]);
					break;
				case 17: //
					$strHtml .= $this->onFinalEnd($curMatch);
					break;
				case 20: //世俱半决
					$strHtml .= $this->onFcwcHalfEnd();
					break;
				case 22: //世俱决
					$strHtml .= $this->onFcwcFinalEnd();
					break;
				case 36: //亚冠半决
					$strHtml .= $this->onAfcHalfEnd();
					break;
				case 37: 
					$strHtml .= $this->onAfcFinalEnd();
					break;
			}
		}
		
		return $strHtml;
    }
    
    private function assault(&$curMatch, $minutes)
    {
		$strHtml = '';
        $strDir = array('1'=>'左路', '2'=>'中路', '3'=>'右路');
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
		
		$strHtml .= "<br/><span class=\"bg-info\">{$minutes}分钟</span>, {$attackTeam->getRndName()} 在{$strDir[$attackDir]}进攻，";
        $collisionResult = PlayerManager::getInstance()->collision($attackDir, $attackPlayers['shoufa'], $defensePlayers['shoufa'], $curMatch->class_id);
		$passer = $attackPlayers['shoufa'][$collisionResult['attackerIndex']];
		$tackler = $defensePlayers['shoufa'][$collisionResult['defenserIndex']];
		
        if ($collisionResult['result'] == 1) //形成射门
        {
            $strHtml .= $attackPlayers['shoufa'][$collisionResult['attackerIndex']]->getRndName() .  '突破成功后传球，';
			$shotResult = PlayerManager::getInstance()->shot($collisionResult['attackerIndex'], $attackPlayers, $defensePlayers, $attackDir, $curMatch->class_id);
			$strHtml .= $attackPlayers['shoufa'][$shotResult['shoterIndex']]->getRndName() . '射门,';

			switch ($shotResult['result']) 
			{
				case 1:
					$strHtml .= '球进了<br/>';
					$strHtml .= $this->goal($curMatch);
					$attackPlayers['shoufa'][$collisionResult['attackerIndex']]->addAssist($curMatch->class_id);
					$needTurn = TRUE;
					break;
				case 2:
					$strHtml .= $defensePlayers['shoufa'][$shotResult['goalkeeperIndex']]->getRndName() . '扑救成功<br/>';
					$strHtml .= "{$attackTeam->getRndName()}获得角球,";
					$strHtml .= $this->corner($attackPlayers, $defensePlayers, $attackTeam->CornerKicker_id, $curMatch, $needTurn);
					break;
				case 3:
					$strHtml .= $defensePlayers['shoufa'][$shotResult['goalkeeperIndex']]->getRndName() . '扑救成功<br/>';
					$strHtml .= '发动反击,';
					$needTurn = TRUE;
					break;
            } 
        }
		else if ($collisionResult['result'] == 2) //防守方犯规
		{
			$strHtml .= $defensePlayers['shoufa'][$collisionResult['defenserIndex']]->getRndName() . '犯规,';
			$foulResult = $defensePlayers['shoufa'][$collisionResult['defenserIndex']]->foul($curMatch->class_id);
			
			$injuredResult = mt_rand(1,10);
			$injuredDay = mt_rand(1, 20);
			if($injuredResult < 4) //进攻球员受伤
			{
				$passer->onInjured($injuredDay);
				unset($passer->score);
				unset($passer->yellow_today);
				$passer->save();
				$strHtml .= "{$passer->name}被换下场，需要休养{$injuredDay}天，";
				$strHtml .= $this->substitution($attackPlayers, $passer->position_id);
				array_splice($attackPlayers['shoufa'], $collisionResult['attackerIndex'], 1);
			}
			elseif($injuredResult == 5) //防守方受伤
			{
				$tackler->onInjured($injuredDay);
				unset($tackler->score);
				unset($tackler->yellow_today);
				$tackler->save();
				$strHtml .= "{$tackler->name}被换下场，需要休养{$injuredDay}天，";
				$strHtml .= $this->substitution($defensePlayers, $tackler->position_id);
				array_splice($defensePlayers['shoufa'], $collisionResult['defenserIndex'], 1);
			}

			if($foulResult == 1)
			{
				$strHtml .= '领到一张黄牌<br>';
			}
			else if($foulResult == 2)
			{
				$strHtml .= '积累两张黄牌,被罚下场<br>';
				unset($defensePlayers['shoufa'][$collisionResult['defenserIndex']]);
			}
			else if($foulResult == 3)
			{
				$strHtml .= '领到一张红牌,直接罚下<br>';
				unset($defensePlayers['shoufa'][$collisionResult['defenserIndex']]);
			}
			
			if(mt_rand(1,5) == 1) //free
			{
				$freeResult = PlayerManager::getInstance()->free($attackPlayers['shoufa'], $defensePlayers['shoufa'], $attackTeam->FreeKicker_id, $curMatch->class_id);
				$strHtml .= $freeResult['free_kicker']->getRndName() . '任意球射门';
				if($freeResult['result'] == 1)
				{
					$strHtml .= '球进了!<br>';
					$strHtml .= $this->goal($curMatch);
				}
				else
				{
					if(mt_rand(0,1))
					{
						$strHtml .= $freeResult['goal_keeper']->getRndName() . '扑救成功<br>发动反击,';
						$needTurn = TRUE;
					}
					else
					{
						$strHtml .= $attackTeam->getRndName() . '获得角球,' . $this->corner($attackPlayers, $defensePlayers, $attackTeam->CornerKicker_id, $curMatch, $needTurn);
					}
				}
			}
			else if(mt_rand(1,10) == 1) //penalty
			{
				$penaltyResult = PlayerManager::getInstance()->penalty($attackPlayers['shoufa'], $defensePlayers['shoufa'], $attackTeam->PenaltyKicker_id, $curMatch->class_id);
				$strHtml .= $penaltyResult['penalty_kicker']->getRndName() . '主罚点球,';
				if($penaltyResult['result'] == 1)
				{
					$strHtml .= '球进了!<br>';
					$strHtml .= $this->goal($curMatch);
				}
				else
				{
					$strHtml .= $penaltyResult['goal_keeper']->getRndName() . '扑救成功!<br>';
				}
				$needTurn = TRUE;
			}
		}
        else
        {
            $strHtml .= $defensePlayers['shoufa'][$collisionResult['defenserIndex']]->getRndName() . 'defense succes,';
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
		
		return $strHtml;
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
		$msg = '欧冠晋级16强，奖金' . $reward . '万欧元';
		
		foreach($successTeamIds as $teamId)
		{
			News::create($msg, $teamId, $nowDate, '/res/img/EuroChampion.jpg');
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
			News::create($newsMsg, $teamId, $nowDate, '/res/img/EuroChampion.jpg');
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
			News::create($msg, $teamId, $nowDate, '/res/img/afc.jpg');
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
			News::create($msg, $teamId, $nowDate, '/res/img/afc.jpg');
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
			News::create($msg, $teamId, $nowDate, '/res/img/EuroChampion.jpg');
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
    
    private function corner(&$attackPlayers, &$defensePlayers, $cornerKickerId, &$curMatch, &$needTurn)
    {
		$strHtml = '';
        $cornerKickerIndex = PlayerManager::getInstance()->getCornerKickerIndex($attackPlayers['shoufa'], $cornerKickerId);
		$cornerDir = array_rand(Match::$cornerPositions);
        $strHtml .= $attackPlayers['shoufa'][$cornerKickerIndex]->name . '主罚角球，皮球飞到'.Match::$cornerPositions[$cornerDir].',';
        $cornerData = PlayerManager::getInstance()->qiangdian($attackPlayers['shoufa'], $defensePlayers['shoufa'], $attackPlayers['shoufa'][$cornerKickerIndex]->id, $cornerDir, $curMatch->class_id);
		$header = $cornerData['header'];
		$goalkeeper = $cornerData['goalkeeper'];
		$cornerResult = $cornerData['result'];
		if($cornerResult == 1)
		{
			$strHtml .= $header->getRndName() . $header->getRndHeadStyle() . '，球进了.';
			$strHtml .= $this->goal($curMatch);
			$needTurn = true;
		}
		elseif($cornerResult == 2) //守门员扑出
		{
			$strHtml .= $header->getRndName() . $header->getRndHeadStyle() . '，' . $goalkeeper->name . '扑出了,';
			if(mt_rand(0,1))
			{
				$strHtml .= '皮球滚出底线.<br/>再次获得角球,';
				$strHtml .= $this->corner($attackPlayers, $defensePlayers, $cornerKickerId, $curMatch, $needTurn);
			}
			else
			{
				$strHtml .= '皮球飞出禁区,开始反击<br/>';
				$needTurn = true;
			}
		}
		elseif($cornerResult == 3) //防守队员扑出
		{
			$strHtml .= $header->getRndName() . '头球解围，';
			if(mt_rand(0,1))
			{
				$strHtml .= '皮球滚出底线.<br/>再次获得角球,';
				$strHtml .= $this->corner($attackPlayers, $defensePlayers, $cornerKickerId, $curMatch, $needTurn);
			}
			else
			{
				$strHtml .= '皮球飞出禁区,开始反击<br/>';
				$needTurn = true;
			}
		}
		elseif($cornerResult == 4)
		{
			$strHtml .= "没人抢到点, 门球.";
			$needTurn = true;
		}
        
		return $strHtml;
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
		$strHtml = '';
		$curMatch->saveGoal();
		$strHtml .= '<span class="bg-danger">' . $curMatch->hostTeam->name . $curMatch->HostGoals . "</span>:<span class=\"bg-success\">" . $curMatch->GuestGoals . $curMatch->guestTeam->name . "</span><br>";
		return $strHtml;
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
	
	private function onFinalEnd($curMatch)
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
		
		$html .= '<div class="alert alert-danger" role="alert">' . $winTeam->name . '获得了' . MainConfig::$matchClasses[$curMatch->class_id] . '的冠军</div>';
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
			$firstMatch->is_host_park = isset($playDates[1]) ? 1 : 0;
			$firstMatch->save();
			
			if(isset($playDates[1]))
			{
				$secondMatch = new Match();
				$secondMatch->HostTeam_id = $guestTeam->id;
				$secondMatch->GuestTeam_id = $hostTeam->id;
				$secondMatch->class_id = $nextClassId;
				$secondMatch->PlayTime = $year . '-' . $playDates[1];
				$secondMatch->is_host_park = 1;
				$secondMatch->save();
			}
			
			$html .= '<tr><td>' . $hostTeam->name . '</td><td> VS </td><td>' . $guestTeam->name . '</td></tr>';
		}
		$html .= '</table>';
	
		return $html;
	}
	
	/**
	 * 换人
	 * @param type $players 本队大名单所有球员
	 * @param type $positionId
	 * @return string
	 */
	private function substitution($players, $positionId)
	{
		$strHtml = '';
		$newPlayer = NULL;
		foreach($players['bandeng'] as $player) //找相同位置的替补队员，找到就完美解决
		{
			if($player->position_id == $positionId)
			{
				$newPlayer = $player;
			}
		}
		
		if(!$newPlayer)
		{
			if($positionId != 4) //非守门员的场上位置
			{
				foreach($players['bandeng'] as $player) //首先找不是守门员的替补队员
				{
					if($player->position_id != 4)
					{
						$newPlayer = $player;
					}
				}
				
				if(!$newPlayer) //还没找到就找所有
				{
					foreach($players['bandeng'] as $player)
					{
						$newPlayer = $player;
					}
				}
			}
			else //如果替补席没有守门员
			{
				foreach($players['bandeng'] as $player)
				{
					$newPlayer = $player;
				}
			}
		}
		
		if($newPlayer)
		{
			$players['shoufa'][] = $newPlayer;
			$strHtml .= "{$newPlayer->name}被换上场<br/>";
		}
		else
		{
			$strHtml .= "替补席无人可换";
		}
		return $strHtml;
	}
}