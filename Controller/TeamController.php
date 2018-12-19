<?php
namespace Controller;
use Model\Core\Team;
use Model\Core\Match;
use Model\Core\Coach;
use Model\Manager\NewsManager;
use Model\Manager\PlayerManager;
use Model\Manager\TeamManager;
use Model\Manager\SettingManager;
use Model\Manager\CoachManager;
use Model\Manager\MatchManager;
use Model\Manager\FutureContractManager;
use Model\Manager\RetiredShirtManager;
use Model\Manager\FirstNameManager;
use Model\Manager\FamilyNameManager;
use Model\Manager\CountryManager;

class TeamController extends AppController
{
    public $name = "Team";
    public $layout = 'main';
    
    public function payoff()
    {
        $nowDate = SettingManager::getInstance()->getNowDate();
		$allTeams = Team::find('all', ['conditions'=>['league_id <'=>100]]);
		
        for ($i = 0;$i < count($allTeams);$i++)
    	{
            $allTeams[$i]->paySalary($nowDate);
			$allTeams[$i]->save();
    	}
        
		$this->flushNow('完成<br>');
    }
    
    public function list_league_rank($leagueId)
    {
        $teams = TeamManager::getInstance()->find('all', array(
            'conditions' => array('league_id'=>$leagueId),
            'fields' => array('id', 'name', 'win', 'lose', 'draw', 'goals', 'lost', 'draw', 'score', 'goals-lost as jingshengqiu'),
            'order' => array('score'=>'desc','jingshengqiu'=>'desc', 'goals'=>'desc')
        ));
        
        $this->set('teams', $teams);
        $this->render('list_league_rank');
    }
    
	/**
	 * 卖出
	 */
    public function sell_players()
    {
		$this->flushNow("<link type=\"text/css\" rel=\"stylesheet\" href=\"" . \MainConfig::BASE_URL . "res/css/main.css\" />");
        $nowDate = SettingManager::getInstance()->getNowDate();
    	PlayerManager::getInstance()->query("update ypn_players set isSelling=0 where team_id not in (select team_id from ypn_managers)");
    	
    	//合同过期的变成自由球员
    	PlayerManager::getInstance()->query("select id,name,contractend,isselling from ypn_players where contractend<'" . $nowDate . "'");

		//俱乐部依赖小于50的卖掉
    	$playersArray = PlayerManager::getInstance()->query("select * from ypn_players where ClubDepending<50 and contractend>'" . $nowDate . "' and id not in (select player_id from ypn_future_contracts) and team_id not in (select team_id from ypn_managers) and team_id>0");
        $players = PlayerManager::getInstance()->loadData($playersArray);
        unset($playersArray);
    	foreach ($players as $targetPlayer)
    	{
            $sellPrice = $targetPlayer->estimateFee($nowDate);
			$targetPlayer->isSelling = 1;
			$targetPlayer->fee = $sellPrice;
			PlayerManager::getInstance()->saveModel($targetPlayer);
			
    		echo("<span class=\"blue_normal_span\">" . $targetPlayer->name . "</span>被以<span class=\"red_normal_span\">" . $sellPrice . "</span>W欧元的价格挂牌出售<br>");flush();
    	}

        $teams = TeamManager::getInstance()->getAllComputerTeams();
    	for ($i = 0;$i < count($teams);$i++)
        {
        	echo("<br/><span class=\"blue_bold_span\">" . $teams[$i]->name . "</span>正在转会...<br>");

        	/*如果财政赤字则卖出队内最贵的球员*/
            if ($teams[$i]->money < 0)
            {
                $result = PlayerManager::getInstance()->sellBestPlayer($teams[$i]->id);
                $this->flushNow("<span class=\"blue_normal_span\">" . $result['name'] . "</span>被以<span class=\"red_normal_span\">" . $result['fee'] . "</span>W欧元卖出了<br>");
            }
            
			if ($teams[$i]->player_count > 33) //球员太多 需要减肥
			{
				$players = PlayerManager::getInstance()->sellUnnecessaryPlayer($teams[$i]->id, $teams[$i]->formattion);
				foreach($players as $player)
				{
					if($player->isSelling == 1)
					{
						$this->flushNow("<span class=\"blue_normal_span\">" . $player->name . "</span>被以<span class=\"red_bold_span\">" . $player->fee . "</span>W欧元的价格挂牌出售<br>");
					}
				}
			}
        }
		
		echo '<br/><a href="/ypn/new_day">New Day</a>';
    }
    
    public function buy_players()
    {
		$this->flushCss();
    	$nowDate = SettingManager::getInstance()->getNowDate();
    	$allCanBuyPlayers = PlayerManager::getInstance()->query("select * from ypn_players where (isSelling=1 or team_id=0 or DATE_ADD('" . $nowDate . "', INTERVAL 181 DAY)>ContractEnd) and not exists(select player_id from ypn_future_contracts where ypn_future_contracts.player_id=ypn_players.id) order by ContractEnd,fee,isSelling");
	   	$computerLeagueTeams = TeamManager::getInstance()->getComputerLeagueTeams();
	   	$lastLeagueId = -1;
		$futurePlayerIds = FutureContractManager::getInstance()->getAllPlayerIds();
        $allRetiredShirts = RetiredShirtManager::getInstance()->find('all', array('fields' => array('shirt', 'team_id')));
        $allTeamUsedNOs = PlayerManager::getInstance()->getAllTeamUsedNOs($allRetiredShirts);
		$allTeamPositionCount = PlayerManager::getInstance()->groupAllPositionByTeamId();

    	for ($i = 0;$i < count($computerLeagueTeams);$i++) //循环所有computer team
        {
			if ( (($computerLeagueTeams[$i]->getLeagueId()) == 4) && ($computerLeagueTeams[$i]->getPlayerCount() > 25) || ($computerLeagueTeams[$i]->getPlayerCount() > 26) ) continue; //西甲球员上限25人，其余联赛26人
            
            /*如果已经切换league，则排序把本联赛球员放在名单前部*/
        	if ($lastLeagueId != $computerLeagueTeams[$i]->getLeagueId())
        	{
				$allCanBuyPlayers = PlayerManager::getInstance()->sortByMyLeague($allCanBuyPlayers, $computerLeagueTeams[$i]->getLeagueId());
        		$lastLeagueId = $computerLeagueTeams[$i]->getLeagueId();
        	} 	
			
			$this->flushNow("<br><span class=\"blue_bold_span\">" . $computerLeagueTeams[$i]->name . "</span>正在转会<br>");
            $this->buySomePlayers($computerLeagueTeams[$i], $allTeamUsedNOs, $allCanBuyPlayers, $futurePlayerIds, $allTeamPositionCount, $computerLeagueTeams);
        }
        
        /*save ypn_players*/
		$this->flushNow('正在保存数据...<br/>');
		$teamSaveData = array();
		foreach($computerLeagueTeams as $team)
		{
			if(isset($team->isChanged))
			{
//				$teamSaveData[] = array('id'=>$team->id, 'bills'=>$team->bills, 'money'=>$team->money);
				unset($team->isChanged);
				$team->save();
			}
		}
		
//		TeamManager::getInstance()->update_batch($teamSaveData);

		foreach ($allCanBuyPlayers as $sellingPlayer)
        {
        	if (isset($sellingPlayer['isChanged']))
        	{
				unset($sellingPlayer['isChanged']);
				PlayerManager::getInstance()->saveModel($sellingPlayer);
        	}
        }
        echo('转会结束.');
    }
	
	/**
	 * NPC Team购买球员
	 * @param type $curTeam
	 * @param type $allTeamUsedNOs
	 * @param type $allCanBuyPlayers
	 * @param type $futurePlayerIds 已经在未来合同的集合
	 * @param type $allTeamPositionCount
	 * @param type $computerLeagueTeams
	 */
	private function buySomePlayers(&$curTeam, $allTeamUsedNOs, &$allCanBuyPlayers, &$futurePlayerIds, $allTeamPositionCount, &$computerLeagueTeams)
    {
        $usedNOs = array_key_exists($curTeam->id, $allTeamUsedNOs) ? $allTeamUsedNOs[$curTeam->id] : array(); //已使用的号码

		$myPlayerPoses = isset($allTeamPositionCount[$curTeam->id]) ? $allTeamPositionCount[$curTeam->id] : array();
        foreach($curTeam->getNeedPoses() as $positionId=>$minCount) //遍历每个位置，对比标准配置数和现有数量，缺少的buy-in
        {
            $posCount = array_key_exists($positionId, $myPlayerPoses) ? $myPlayerPoses[$positionId] : 0;
            if ($posCount < $minCount)
            {
				$newNO = $this->buySuitablePlayer($curTeam, $positionId, $usedNOs, $allCanBuyPlayers, $futurePlayerIds, $computerLeagueTeams);
				if ($newNO != null)
				{
					$usedNOs[] = $newNO;
				}
			}
        }
    }

    /**
     * 购买一名特定位置的球员
     * @param Team $buyTeam
     * @param int $position_id
     * @param array $usedNOs
     * @param array[array] $allCanBuyPlayers
     * @return type
     */
    private function buySuitablePlayer(&$buyTeam, $position_id, $usedNOs, &$allCanBuyPlayers, &$futurePlayerIds, &$computerLeagueTeams)
    {
        $newSalary = 0;
        $playerNO = 0;
		$nowDate = SettingManager::getInstance()->getNowDate();
		$newsMsg = '';
		$transferSucess = false;
		$playerEstimateFee = 0;
		$myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;

        foreach ($allCanBuyPlayers as $i=>&$curPlayerArr) //traverse allplayers to transfer
        {
			$agreeNewSalary = mt_rand(0, 1);
            if (in_array($curPlayerArr['id'], $futurePlayerIds) || ($curPlayerArr['team_id'] == $buyTeam->id) || ($curPlayerArr['position_id'] != $position_id) || isset($curPlayerArr['isChanged']) || $agreeNewSalary) 
					continue;

			$curPlayer = PlayerManager::getInstance()->loadOne($curPlayerArr);
			$newSalary = $curPlayer->getExpectedSalary($nowDate);
							
			if ($curPlayer->team_id == 0) //free
			{
				$transferSucess = true;
				$this->flushNow("<span class=\"green_bold_span\">" . $curPlayerArr['name'] . "</span>自由转会去了" . $buyTeam->name . "<br>");
			}
			else if ( ($buyTeam->money > $curPlayerArr['fee']) && $curPlayerArr['isSelling']) //normal
			{
				$playerEstimateFee = $curPlayer->estimateFee($nowDate);
				if ($curPlayer->fee > $playerEstimateFee)
				{
					$newsMsg = $buyTeam->name . "希望通过<span class=\"red_bold_span\">" . $playerEstimateFee . "</span>万欧元的价格买进<span class=\"blue_bold_span\">" . $curPlayer->name . "</span>";
				}
				else if (mt_rand(1, 2) == 1)
				{
					$newsMsg = "<span class=\"green_bold_span\">" . $curPlayer->name . "</span>已经被" . $buyTeam->name . "成功引进";
					$transferSucess = true;
					$this->flushNow("<span class=\"green_bold_span\">" . $curPlayer->name . "</span>以<span class=\"red_bold_span\">" . $curPlayer->fee . "</span>万欧元去了" . $buyTeam->name . "<br>");
				}
			}
			elseif ((date("Y-m-d", strtotime("$nowDate + 181 day")) > $curPlayerArr['ContractEnd']) && ($curPlayerArr['loyalty'] < 85)) /*last 6 month，忠诚度小于85的会自由转会*/
			{
				FutureContractManager::getInstance()->saveModel(array(
					'player_id' => $curPlayer->id,
					'NewContractEnd' => date("Y", strtotime("$nowDate + " . mt_rand(1, 6) . " year")) . "-6-30",
					'NewTeam_id' => $buyTeam->id,
					'NewSalary' => $newSalary,
					'OldContractEnd' => $curPlayer->ContractEnd
				));
				
				$futurePlayerIds[] = $curPlayer->id;
				
				$info = "<span class=\"green_bold_span\">" . $curPlayer->name . "</span>将在6个月内自由转会加盟<span class=\"blue_normal_span\">" . $buyTeam->name . "</span>";
				$this->flushNow($info . "<br>");  
			}

			if ($newsMsg && ($curPlayer->team_id == $myTeamId) )
			{
				NewsManager::getInstance()->add($newsMsg, $buyTeam->id, $nowDate, $curPlayer->ImgSrc);
			}

			if ($transferSucess)
			{
				$buyTeam->player_count += 1;
				$buyTeam->TotalSalary += $newSalary;
				$buyTeam->isChanged = TRUE;

				if ($curPlayer->team_id > 0) //not free transfer
				{
					$buyTeam->addMoney(-$curPlayer->fee, '买进球员' . $curPlayer->name, $nowDate);

					foreach($computerLeagueTeams as &$t) //get sell team
					{
						if ($t->id == $curPlayer->team_id)
						{
							$t->isChanged = TRUE;
							$t->player_count -= 1;
							$t->TotalSalary -= $newSalary;
							$t->addMoney(-$curPlayer->fee, '卖出球员' . $curPlayer->name, $nowDate);
							break;
						}
					}
				}
				
				$playerNO = $curPlayer->getNewShirtNo($usedNOs);

				if ($allCanBuyPlayers[$i]['league_id'] == $buyTeam->league_id)
				{
					$allCanBuyPlayers[$i]['cooperate'] = 90;
				}
				else
				{
					$allCanBuyPlayers[$i]['cooperate'] = 80;
					$allCanBuyPlayers[$i]['league_id'] = $buyTeam->league_id;
				}

				$allCanBuyPlayers[$i]['team_id'] = $buyTeam->id;
				$allCanBuyPlayers[$i]['ClubDepending'] = 80;
				$allCanBuyPlayers[$i]['loyalty'] = 80;
				$allCanBuyPlayers[$i]['salary'] = $newSalary;
				$allCanBuyPlayers[$i]['ShirtNo'] = $playerNO;
				$allCanBuyPlayers[$i]['ContractBegin'] = $nowDate;
				$allCanBuyPlayers[$i]['ContractEnd'] = date('Y', strtotime($nowDate))+mt_rand(1, 5) . "-6-30";
				$allCanBuyPlayers[$i]['isSelling'] = 0;
				$allCanBuyPlayers[$i]['isChanged'] = true;
				return $playerNO;
			}
        }
    }
    
    public function invite_friend_match()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		$fifaDates = SettingManager::getInstance()->getFifaDates();
        $myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
               
		/*循环主队*/
        $allTeamArray = TeamManager::getInstance()->find('all', array(
            'conditions' => array('league_id <>' => 100), 
            'fields' => array('id', 'name', 'money', 'bills', 'FieldName', 'player_count', 'ImgSrc')
            ));
        
        $allTeams = TeamManager::getInstance()->loadData($allTeamArray);
        unset($allTeamArray);
        shuffle($allTeams);
		for ($i = 0;$i < count($allTeams);$i++)
		{
			if ( ($allTeams[$i]->money < 0) || ($allTeams[$i]->player_count < 15) ) continue;
			
			$invitePlayTime = date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($nowDate))  , date("d", strtotime($nowDate))+mt_rand(1, 7), date("Y", strtotime($nowDate))));
			
			/*如果随机的比赛日选在了FIFA-DAY上，则取消*/
            $onFifaDay = false;
			for ($j = 0;$j < count($fifaDates);$j++)
			{
				if ($fifaDates[$j] == date('Y-m-d', strtotime($invitePlayTime)))
				{
					$onFifaDay = true;
					break;
				}
			} 			
			
			if ($onFifaDay) continue;
			
			$conditions = array(
				'PlayTime >' => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($invitePlayTime))  , date("d", strtotime($invitePlayTime))-4, date("Y", strtotime($invitePlayTime)))),
				'PlayTime <' => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($invitePlayTime))  , date("d", strtotime($invitePlayTime))+4, date("Y", strtotime($invitePlayTime)))),
				'or' => array( 
					'HostTeam_id' => $allTeams[$i]->id,
					'GuestTeam_id' => $allTeams[$i]->id,
				),				
			);
			$contain = array();
			$recentMatch = MatchManager::getInstance()->find('first', compact('conditions', 'contain'));
			
			if (!empty($recentMatch)) continue;
			
			if ($allTeams[$i]->id != $myTeamId)
			{
				/*随机选择客队*/
				$inviteTeam = $allTeams[mt_rand(0, count($allTeams)-1)];
				if ( ($inviteTeam->id == $allTeams[$i]->id) || ($inviteTeam->player_count < 15) ) continue;
				
				/*如果近期客队没有比赛，则友谊赛预约成功*/
				$conditions = array(
					'PlayTime >' => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($invitePlayTime))  , date("d", strtotime($invitePlayTime))-4, date("Y", strtotime($invitePlayTime)))),
					'PlayTime <' => date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($invitePlayTime))  , date("d", strtotime($invitePlayTime))+4, date("Y", strtotime($invitePlayTime)))),
					'or' => array( 
						'HostTeam_id' => $inviteTeam->id,
						'GuestTeam_id' => $inviteTeam->id,
					),				
				);
				$contain = array();
				$recentMatch = MatchManager::getInstance()->find('first', compact('conditions', 'contain'));
				
				if (empty($recentMatch))
				{

					$inviteTeam->addMoney(5, '邀请友谊赛', $nowDate);
					TeamManager::getInstance()->saveModel($inviteTeam);

					$newMatch = new Match();
					$newMatch->HostTeam_id = $allTeams[$i]->id;
					$newMatch->GuestTeam_id = $inviteTeam->id;
					$newMatch->PlayTime = $invitePlayTime;
					$newMatch->class_id = 24;
					MatchManager::getInstance()->saveModel($newMatch);

					$this->flushNow('<span class="blue_bold_span">' . $allTeams[$i]->name . '</span>与<span class="blue_bold_span">' . $inviteTeam->name . '</span>将于'. $invitePlayTime . '在'. $allTeams[$i]->FieldName . '进行了友谊赛<br>');
					
					if ($myTeamId == $inviteTeam->id) //是主队
					{
						NewsManager::getInstance()->add($allTeams[$i]->name . "邀请了友谊赛", $inviteTeam->id, $nowDate, $allTeams[$i]->ImgSrc);
					}
				}
			}
		}
	}
    
    public function edit()
    {
        $myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeam = TeamManager::getInstance()->findById($myCoach->team_id);
        $myPlayers = PlayerManager::getInstance()->find('list', array(
            'conditions' => array('team_id'=>$myTeam['id']),
            'fields' => array('id', 'name'),
            'order' => array('ShirtNo'=>'asc')
        ));
        $this->set('myTeam', $myTeam);
        $this->set('myPlayers', $myPlayers);
        $this->render('edit');
    }
    
    public function change_kicker($kickerType, $kickerId)
    {
        $myCoach = CoachManager::getInstance()->getMyCoach();
        TeamManager::getInstance()->update(array($kickerType=>$kickerId), array('id'=>$myCoach->team_id));
    }
    
    public function change_attack($attack)
    {
        $myCoach = CoachManager::getInstance()->getMyCoach();
        TeamManager::getInstance()->update(array('attack'=>$attack), array('id'=>$myCoach->team_id));
    }
    
    public function get_young_players()
    {
		ini_set('memory_limit','512M');
		$this->flushCss();
		$this->flushNow('转会期已经结束了，各个俱乐部正在抽调年轻球员，请稍候...<br>');
        
		$nowDate = SettingManager::getInstance()->getNowDate();
        $allPlayers = PlayerManager::getInstance()->getAllPlayers();
        $teamPositionInfos = array();
        $teamShirtNos = array();
        foreach($allPlayers as $player)
        {
            if (isset($teamPositionInfos[$player->getTeamId()][$player->getPositionId()]))
            {
                $teamPositionInfos[$player->getTeamId()][$player->getPositionId()]++;
            }
            else
            {
                $teamPositionInfos[$player->getTeamId()][$player->getPositionId()] = 1;
            }
            
            if (isset($teamShirtNos[$player->getTeamId()]))
            {
                $teamShirtNos[$player->getTeamId()][] = $player->getShirtNo();
            }
            else
            {
                $teamShirtNos[$player->getTeamId()] = array($player->getShirtNo());
            }
        }
        
        $existPlayerNames = array();
        foreach($allPlayers as $player)
        {
            $existPlayerNames[] = $player->getName();
        }
		
        $allRetiredShirts = RetiredShirtManager::getInstance()->find('all', array('fields' => array('shirt', 'team_id')));
        $allComputerTeams = TeamManager::getInstance()->getAllComputerTeams();
        $lastPlayerId = PlayerManager::getInstance()->getLastPlayerId();
        $firstNames = FirstNameManager::getInstance()->getFirstNames();
        $familyNames = FamilyNameManager::getInstance()->getFamilyNames();
        $countries = CountryManager::getInstance()->find('all');
        $theCoach = new Coach();
        foreach ($allComputerTeams as &$curTeam)
        {
            if ($curTeam->getPlayerCount() < 22)
            {
                $positionInfo = isset($teamPositionInfos[$curTeam->getId()]) ? $teamPositionInfos[$curTeam->getId()] : array();
                $curTeam->setPositionInfo($positionInfo);
                $theCoach = clone $theCoach;
                $theCoach->setTeam($curTeam);
                $shirtNos = isset($teamShirtNos[$curTeam->getId()]) ? $teamShirtNos[$curTeam->getId()] : array();
                $extractInfo = $theCoach->getYoungPlayers($allRetiredShirts, $allPlayers, $shirtNos); //获取需要抽取的名单信息
                $usedNOs = $extractInfo['used_nos'];
                foreach($extractInfo['positions'] as $positionId => $count) //按position_id遍历
                {
                    $names = PlayerManager::getInstance()->getLeastYoungPlayers($positionId, $count, $firstNames, $familyNames, $countries, $usedNOs, $curTeam->getLeagueId(), $curTeam->getId(), $nowDate, $existPlayerNames);
                    foreach($names as $newPlayerName)
                    {
                        $this->flushNow("在二线队抽调了<span=\"blue_bold_span\">" . $curTeam->getName() . "</span>在二线队抽调了<span=\"green_bold_span\">" . $newPlayerName . "</span>到一线队<br>");
	
						$curTeam->addMoney(-5, '抽调新队员', $nowDate);
						$curTeam->player_count++;
						$curTeam->isChanged = TRUE;
                    }
                }
            }
        }
        
        PlayerManager::getInstance()->saveAllData();
		foreach($allComputerTeams as $curTeam)
		{
			if(isset($curTeam->isChanged))
			{
				unset($curTeam->isChanged);
				TeamManager::getInstance()->saveModel($curTeam);
			}
		}
    }
	
	public function ajax_change_attack($attack)
	{
		$myCoach = CoachManager::getInstance()->getMyCoach();
        TeamManager::getInstance()->setAttack($myCoach->team_id, $attack);
		echo $attack;
	}

	public function ajax_give_birthday_subsidy($playerId)
	{
		$money = 1;
		$nowDate = SettingManager::getInstance()->getNowDate();
		$myCoach = CoachManager::getInstance()->getMyCoach();
		$curTeam = TeamManager::getInstance()->findById($myCoach->team_id);
		$curPlayer = PlayerManager::getInstance()->findById($playerId);
		TeamManager::getInstance()->changeMoney($myCoach->team_id, 2, 1, $nowDate, "给予生日{$curPlayer['name']}贺礼");
	}
	
	public function bill_list()
	{
		$myCoach = CoachManager::getInstance()->getMyCoach();
		$curTeam = TeamManager::getInstance()->findById($myCoach->team_id);
		$bills = json_decode($curTeam['bills'], TRUE);
		$bills = is_array($bills) ? $bills : array();
//		print_r($bills);exit;
		$this->set('bills', $bills);
		$this->render('bill_list');
	}
	
	public function ajax_change_auto_format()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		$myTeamId = CoachManager::getInstance()->getMyCoach()->team_id;
		$isAutoFormat = $_POST['auto_format'];
		
		TeamManager::getInstance()->update(array('is_auto_format'=>$isAutoFormat), array('id'=>$myTeamId));
	}
}