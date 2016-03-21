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
    	$conditions = array();
    	$fields = array('id', 'name', 'ImgSrc', 'TotalSalary');
    	$allTeamsData = TeamManager::getInstance()->find('all', compact('conditions', 'fields'));
        $allTeams = TeamManager::getInstance()->loadData($allTeamsData, 'Team');
        for ($i = 0;$i < count($allTeams);$i++)
    	{
            if (is_null($allTeams[$i]->TotalSalary))
    		{
    			$allTeams[$i]->TotalSalary = PlayerManager::getInstance()->calTotalSalary($allTeams[$i]->id);
    		}
            
            $allTeams[$i]->paySalary($nowDate);
    		NewsManager::getInstance()->add('给球员发工资共花费了<font color=red><strong>' . $allTeams[$i]->TotalSalary . '</strong></font>万欧元', $allTeams[$i]->id, $nowDate, $allTeams[$i]->ImgSrc);
    	}
        
        TeamManager::getInstance()->saveMany($allTeams);
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
    
    public function sell_players()
    {    
        $nowDate = SettingManager::getInstance()->getNowDate();
    	PlayerManager::getInstance()->query("update ypn_players set isSelling=0 where team_id not in (select team_id from ypn_managers)");
    	
    	//合同过期的变成自由球员
    	PlayerManager::getInstance()->query("select id,name,contractend,isselling from ypn_players where contractend<'" . $nowDate . "'");

		//俱乐部依赖小于50的卖掉
    	$playersArray = PlayerManager::getInstance()->query("select * from ypn_players where ClubDepending<50 and id not in (select player_id from ypn_future_contracts) and team_id not in (select team_id from ypn_managers) and team_id>0");
        $players = PlayerManager::getInstance()->loadData($playersArray);
        unset($playersArray);
        
    	foreach ($players as $targetPlayer)
    	{
            $sellPrice = $targetPlayer->estimateFee($nowDate);
			$targetPlayer->isSelling = 1;
			$targetPlayer->fee = $sellPrice;
			PlayerManager::getInstance()->save($targetPlayer);
			
    		echo("<font color=blue>" . $targetPlayer->name . "</font>被以<font color=red>" . $sellPrice . "</font>W欧元的价格挂牌出售<br>");flush();
    	}

        $teams = TeamManager::getInstance()->getAllComputerTeams();
    	for ($i = 0;$i < count($teams);$i++)
        {
        	echo("<br/><font color=blue><strong>" . $teams[$i]->name . "</strong></font>正在转会...<br>");

        	/*如果财政赤字则卖出队内最贵的球员*/
            if ($teams[$i]->money < 0)
            {
                $result = $this->sellBestPlayer($teams[$i]->id);
                echo("<font color=blue>" . $result['name'] . "</font>被以<font color=red>" . $result['fee'] . "</font>W欧元卖出了<br>");
            }
            
			$players = PlayerManager::getInstance()->sellUnnecessaryPlayer($teams[$i]->id, $teams[$i]->formattion);
            
            foreach($players as $player)
            {
                echo("<font color=blue>" . $player->name . "</font>被以<font color=red><strong>" . $player->fee . "</strong></font>W欧元的价格挂牌出售<br>");
                flush();
            }
        }
    }
    
    public function buy_players()
    {
    	$nowDate = SettingManager::getInstance()->getNowDate();
    	$allCanBuyPlayers = PlayerManager::getInstance()->query("select * from ypn_players where (isSelling=1 or team_id=0 or DATE_ADD('" . $nowDate . "', INTERVAL 181 DAY)>ContractEnd) and not exists(select player_id from ypn_future_contracts where ypn_future_contracts.player_id=ypn_players.id) order by ContractEnd,fee,isSelling");
    	
		/*获得所有电脑控制的球队*/
	   	$allTeams = TeamManager::getInstance()->getRichComputerTeams();
        
	   	$lastLeagueId = -1;
        
        //get future contract players
        $allFutruePlayers = FutureContractManager::getInstance()->find('all', array('fields'=>array('player_id')));
        foreach($allFutruePlayers as $fPlayer)
        {
            $this->futrueContractPlayers[] = $fPlayer['FutureContract']['player_id'];
        }
        unset($allFutruePlayers);
        
        $allRetiredShirts = RetiredShirtManager::getInstance()->find('all', array(
            'fields' => array('shirt', 'team_id'),
            ));
        
        $allTeamUsedNOs = PlayerManager::getInstance()->getAllTeamUsedNOs($allRetiredShirts);
        
    	for ($i = 0;$i < count($allTeams);$i++) //循环所有computer team
        {
			if ( (($allTeams[$i]->getLeagueId()) == 4) && ($allTeams[$i]->getPlayerCount() > 25) || ($allTeams[$i]->getPlayerCount() > 26) ) continue; //西甲球员上限25人，其余联赛26人
        	if (mt_rand(1, 2) == 2)                continue;
            
            /*如果已经切换league，则排序把本联赛球员放在名单前部*/
        	if ($lastLeagueId <> $allTeams[$i]->getLeagueId())
        	{
        	   	$myLeaguePlayers = array();
        		for ($k = 0;$k < count($allCanBuyPlayers);$k++)
        		{
        			if ($allCanBuyPlayers[$k]['league_id'] == $allTeams[$i]->getLeagueId())
        			{
        				$myLeaguePlayers[] = $allCanBuyPlayers[$k];
        				unset($allCanBuyPlayers[$k]);
        			}
        		}
        		
        		$allCanBuyPlayers = array_merge($myLeaguePlayers, $allCanBuyPlayers);

        		unset($myLeaguePlayers);
        		$lastLeagueId = $allTeams[$i]->getLeagueId();
        	} 	
			
            //buy suitable player
            TeamManager::getInstance()->buySomePlayers($allTeams[$i], $allTeamUsedNOs, $allCanBuyPlayers);
            unset($allTeams[$i]);
        }
        
        /*save ypn_players*/
        echo('正在保存数据...<br/>');flush();
        foreach ($this->allCanBuyPlayers as $sellingPlayer)
        {
        	if (isset($sellingPlayer['isChanged']))
        	{
        	    if ($sellingPlayer['isChanged'])
	        	{
	        		$data = $sellingPlayer;
	        		$Player->save($data);
	        	}
        	}
        }
        echo('转会结束.');
    }
    
    public function invite_friend_match()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		$fifaDates = SettingManager::getInstance()->getFifaDates();
        $myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
               
        //traverse all teams, sum all player count
        $teamPlayerCount = array();
        $allPlayers = PlayerManager::getInstance()->find('all', array(
            'conditions'=>array('league_id<>'=>0),
            'fields'=>array('team_id')
            ));
        
        foreach($allPlayers as $player)
        {
            $teamId = $player['team_id'];
            if (array_key_exists($teamId, $teamPlayerCount))
            {
                $teamPlayerCount[$teamId]++;
            }
            else
            {
                $teamPlayerCount[$teamId] = 1;
            }
        }
        unset($allPlayers);
								
		/*循环主队*/
        $allTeamArray = TeamManager::getInstance()->find('all', array(
            'conditions' => array('league_id <>' => 100), 
            'fields' => array('id', 'name', 'money', 'bill', 'FieldName')
            ));
        
        $allTeams = TeamManager::getInstance()->loadData($allTeamArray);
        unset($allTeamArray);
        shuffle($allTeams);
		for ($i = 0;$i < count($allTeams);$i++)
		{
			if ($allTeams[$i]->money < 0) continue;
			if ($teamPlayerCount[$allTeams[$i]->id] < 15) continue; //如果主队不足15人则无法打比赛
			
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
				if ($inviteTeam->id == $allTeams[$i]->id) continue;
				
				/*如果客队不足15人则无法打比赛*/
                if ($teamPlayerCount[$inviteTeam->id] < 15)                    continue;

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
				$recentMatch = $Match->find('first', compact('conditions', 'contain'));
				
				if (empty($recentMatch))
				{
					if ($myTeamId == $inviteTeam->id)
					{
						/*跟主队约友谊赛并且正是主动权的人，做弹出窗口*/
						echo ("<script>window.showModalDialog('../matches/invite_me/" . $allTeams[$i]->id . "/" . $myTeamId . '/' . $invitePlayTime . "','','dialogHeight:400px;dialogWidth:400px;dialogLeft:200px;dialogTop:200px;');</script>");flush();
					}
					else
					{
                        $inviteTeam->addMoney(5, '邀请友谊赛', $nowDate);
                        TeamManager::getInstance()->save($inviteTeam);
                        
                        $newMatch = new Match();
                        $newMatch->HostTeam_id = $allTeams[$i]->id;
                        $newMatch->GuestTeam_id = $inviteTeam->id;
                        $newMatch->PlayTime = $invitePlayTime;
                        $newMatch->class_id = 24;
                        MatchManager::getInstance()->save();
                        
						echo('<font color=blue><strong>' . $allTeams[$i]->name . '</strong></font>与<font color=blue><strong>' . $inviteTeam->name . '</strong></font>将于'. $invitePlayTime . '在'. $allTeams[$i]->FieldName . '进行了友谊赛<br>');flush();
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
		$this->changeStatus('转会期已经结束了，各个俱乐部正在抽调年轻球员，请稍候...');
        
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
        $nowDate = SettingManager::getInstance()->getNowDate();
        $theCoach = new Coach();
        for ($i = 0;$i < count($allComputerTeams);$i++)
        {
            if ($allComputerTeams[$i]->getPlayerCount() < 22)
            {
                $positionInfo = isset($teamPositionInfos[$allComputerTeams[$i]->getId()]) ? $teamPositionInfos[$allComputerTeams[$i]->getId()] : array();
                $allComputerTeams[$i]->setPositionInfo($positionInfo);
                $theCoach = clone $theCoach;
                $theCoach->setTeam($allComputerTeams[$i]);
                $shirtNos = isset($teamShirtNos[$allComputerTeams[$i]->getId()]) ? $teamShirtNos[$allComputerTeams[$i]->getId()] : array();
                $extractInfo = $theCoach->getYoungPlayers($allRetiredShirts, $allPlayers, $shirtNos); //获取需要抽取的名单信息
                $usedNOs = $extractInfo['used_nos'];
                foreach($extractInfo['positions'] as $positionId => $count) //按position_id遍历
                {
                    $names = PlayerManager::getInstance()->getLeastYoungPlayers($positionId, $count, $firstNames, $familyNames, $countries, $usedNOs, $allComputerTeams[$i]->getLeagueId(), $allComputerTeams[$i]->getId(), $nowDate, $existPlayerNames);
                    foreach($names as $n)
                    {
                        echo ($allComputerTeams[$i]->getName() . "在二线队抽调了<font color=blue><strong>" . $n . "</strong></font>到一线队<br>");
                    }
                    flush();
                }
            }
        }
        
        PlayerManager::getInstance()->saveAllData();
    }
	
	public function ajax_change_attack($attack)
	{
		$myCoach = CoachManager::getInstance()->getMyCoach();
        TeamManager::getInstance()->setAttack($myCoach->team_id, $attack);
		echo $attack;
	}

}