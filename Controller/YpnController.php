<?php
namespace Controller;

use Controller\AppController;
use Model\Manager\MatchManager;
use Model\Manager\CoachManager;
use Model\Manager\SettingManager;
use Model\Manager\YpnManager;
use Model\Manager\TeamManager;
use Model\Manager\PlayerManager;
use Model\Manager\NewsManager;
use Model\Manager\FifaDateManager;
use Model\Manager\UclGroupManager;
use Model\Manager\ElGroupManager;
use MainConfig;

class YpnController extends AppController 
{
	var $name = 'Ypn';
	public $layout = "main";
	
	public function new_day()
	{
		header("content-type:text/html; charset=utf-8");
        
        $myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
		$nowDate = SettingManager::getInstance()->getNowDate();
		
		$thisYear = date('Y', strtotime($nowDate));
		$weekday = date("w", strtotime($nowDate));
		$isHoliday = YpnManager::getInstance()->checkHoliday($nowDate);
		
		if ($nowDate == ($thisYear . '-06-01'))
		{
			$this->prepareI18nMatch(); //准备国际比赛上调到国家队的球员
		}
		
		$allUnplayedMatches = MatchManager::getInstance()->find('all', array(
				'conditions' => array('isPlayed' => 0),
				'contain' => array()
			)
		);
		
		$todayMatchTeamIds = $this->getTodayMatchTeamIds($allUnplayedMatches, $nowDate);
		if (!empty($todayMatchTeamIds)) //开始今日比赛
		{
			$this->redirect('/match/play');
		}

		$this->checkTransferOverDayAndDo($nowDate, $thisYear); //如果刚刚过转会期人还没有招满

		/*如果赛季比赛全完事，则进入新赛季页面*/
		if (empty($allUnplayedMatches))
		{
			$this->newSeason();
		}
		else
		{
			/*如果是FIFA-DAY的前一天则抽调国家队队员*/
			$this->inviteFriendMatch($nowDate);

			SettingManager::getInstance()->addDate();

			$isTransferDay = YpnManager::getInstance()->checkTransferDay($nowDate);
			$this->doWeekdayTask($weekday, $isTransferDay, $isHoliday, $myTeamId);

			$this->oldRedirect(array('controller'=>'player', 'action'=>'pay_birthday'), false); /*过生日的队员发奖金*/

			/*列出近期新闻，如果不采用弹出窗口显示则不用列出*/
			$this->set('news', NewsManager::getInstance()->getUnreadNews($myTeamId));
			NewsManager::getInstance()->readAll($myTeamId);
			
			$this->training($isHoliday, $todayMatchTeamIds, $myTeamId, $nowDate);
			PlayerManager::getInstance()->doNormal(); //球员日常变化

			$this->render('new_day');
		}
	}
	
	private function training($isHoliday, $todayMatchTeamIds, $myTeamId, $nowDate)
	{
		$allTeamIds = TeamManager::getInstance()->getAllTeamIds();
		$noMatchTeamIds = array_diff($allTeamIds, $todayMatchTeamIds); //今日没有比赛的球队ID
		
		if (!$isHoliday) //不是假期要训练
		{
			$myInjuredPlayers = PlayerManager::getInstance()->train($noMatchTeamIds, $myTeamId);
            foreach ($myInjuredPlayers as $mip)
            {
                NewsManager::getInstance()->add("<font color=red>" . $mip['name'] . "</font>在训练中受伤，需要休息" . $mip['InjuredDay'] . "天。", $mip['team_id'], $nowDate, $mip['ImgSrc']);	
            }
		}
		else //假期检测是否世界大赛期间
		{
			if (YpnManager::getInstance()->checkWorldCupDay($nowDate))
			{
				$wcTeams = PlayerManager::getInstance()->query('select team_id from ypn_worldcup_groups');
				for ($i = 0;$i < count($wcTeams);$i++)
				{
					$allTeamIds[$i] = $wcTeams[$i]['worldcupgroups']['team_id'];
				}
				unset($wcTeams);

				$myInjuredPlayers = PlayerManager::getInstance()->train($noMatchTeamIds, $myTeamId);
			}
			else if (YpnManager::getInstance()->checkEuroCupDay($nowDate))
			{
				$wcTeams = PlayerManager::getInstance()->query('select team_id from ypn_eurocup_groups');
				for ($i = 0;$i < count($wcTeams);$i++)
				{
					$allTeamIds[$i] = $wcTeams[$i]['ypn_eurocup_groups']['team_id'];
				}
				unset($wcTeams);

				PlayerManager::getInstance()->training($noMatchTeamIds, $myTeamId);
			}
		}
	}
	
	/**
	 * 如果已达到世界大赛开始日，则开始抽调国家队队员
	 */
	private function prepareI18nMatch()
	{
		if (YpnManager::getInstance()->checkWorldCupDay())
		{
			$targetCountries = YpnManager::getInstance()->query('select * from countries where title in (select name from ypn_teams where id in(select team_id from ypn_worldcup_groups))');
			foreach($targetCountries as $tc)
			{
				$targetCountry['Country'] = $tc['countries'];
				$this->Country->uploadPlayers($targetCountry);
			}
		}
		else if (YpnManager::getInstance()->checkEuroCupDay())
		{
			$targetCountries = YpnManager::getInstance()->query('select * from countries where title in (select name from ypn_teams where id in(select team_id from euro_cup_groups))');
			foreach($targetCountries as $tc)
			{
				$targetCountry['Country'] = $tc['countries'];
				$this->Country->uploadPlayers($targetCountry);
			}
		}
	}
	
	private function getTodayMatchTeamIds($allUnplayedMatches, $nowDate)
	{
		$todayMatchTeamIds = array();
		foreach($allUnplayedMatches as $m)
		{
			if ($m['PlayTime'] == $nowDate)
			{
				array_push($todayMatchTeamIds, $m['HostTeam_id'], $m['GuestTeam_id']);
			}
		}
		return $todayMatchTeamIds;
	}
	
	/**
	 * 检测夏窗关闭抽调新人
	 * @param type $nowDate
	 * @param type $thisYear
	 */
	private function checkTransferOverDayAndDo($nowDate, $thisYear)
	{
		if ($nowDate == $thisYear . "-09-01")
		{
		    TeamController::getInstance()->get_young_players();
			PlayerManager::getInstance()->query('update ypn_players set popular=popular-10 where team_id=0');
			PlayerManager::getInstance()->query('update ypn_players set popular=10 where popular<10');
		}
	}
	
	private function inviteFriendMatch($nowDate)
	{
		$fifaDates = SettingManager::getInstance()->getFifaDates();
        $tomorrow = date('Y-m-d', strtotime("$nowDate +1 day"));
        if (in_array($tomorrow, $fifaDates, true))
        {
            $this->Country->inviteFriendMatch();
        }
	}
	
	/**
	 * 整理本赛季数据，开始下个赛季
	 */
	private function newSeason()
	{
                //重算工资总量
        
        
        
		/*如果是世界杯年，球员伤病，状态不清0，日期不跳转，正常+1*/
		$nowDate = date("Y-m-d", strtotime(YpnManager::getInstance()->getNowDate()));
		$thisYear = date('Y', strtotime($nowDate));
	    $this->Match->query("delete from ypn_news");
		
	    $this->Match->query("delete from ypn_matches where class_id in (20, 21, 22, 23, 24, 37)");
	    $this->Match->query("update ypn_matches set isWatched=0, MvpPlayer_id=0, replay='' where class_id<>25");
	    
        $conditions = array('league_id' => '1');
        $order = array('Team.score' => 'desc', 'goals' => 'desc', 'lost' => 'asc');
        $limit = 20;
        $contain = array();
        $seriATeams = TeamManager::getInstance()->find('all', compact('conditions', 'order', 'limit', 'contain'));

        $conditions = array('league_id' => '3');
        $order = array('Team.score' => 'desc', 'goals' => 'desc', 'lost' => 'asc');
        $limit = 20;
        $contain = array();
        $plTeams = TeamManager::getInstance()->find('all', compact('conditions', 'order', 'limit', 'contain'));

        /*当年的FIFA足球先生*/
        $mvps = PlayerManager::getInstance()->query('SELECT `Player`.`id`, `Player`.`name`, total_score/all_matches_count as pingjun FROM `ypn_players` AS `Player`   WHERE `all_matches_count` > 9   ORDER BY `pingjun` desc  LIMIT 1 ');
        if ($mvps != null)
        {
            $mvp = $mvps[0];
            $this->Match->query("update ypn_players set popular=popular+10 where id=" . $mvp['id']);
            
            $managers = $this->Manager->find('all', array(
                'conditions' => array(
                    'team_id <> ' => 0
                    )	        	
                )
            );

            foreach ($managers as $manager) {
                NewsManager::getInstance()->add("本年度FIFA足球先生是<font color=green><strong>" . $mvp['name'] . "</strong></font>", $manager['Manager']['team_id'], $nowDate, "/img/fifa.gif");
            }
        }

        $this->Match->query("update ypn_players set popular=99 where popular>99");

        echo 'FIFA MVP SUCCES!<br>';flush();
         
        /*更新FIFA-DAY*/
        YpnManager::getInstance()->query('update ypn_fifa_dates set PlayDate= date_add(PlayDate, INTERVAL 1 year)');
		echo 'FIFA-DAY SUCCES!<br>';flush();
         
		 /*设置球员最优位置，老队员准备退役*/
		$allPlayers = PlayerManager::getInstance()->find('all', array('contain' => array()));
        $changedPosCount = 0;
        foreach ($allPlayers as $thePlayer) 
        {
        	if ($thePlayer['position_id'] == 4)
        	{
        		$retiredSinew = 70;
        	}
        	else
        	{
        		$retiredSinew = 78;
        	}
        	
            $isRetired = false;
        	$thePlayer['age'] = PlayerManager::getInstance()->getAge($thePlayer);
        	if (($thePlayer['SinewMax'] < $retiredSinew || $thePlayer['team_id'] == 0) && $thePlayer['age'] > 34)
        	{
                $isRetired = true;
        	}
        	else if($thePlayer['SinewMax'] < $retiredSinew || $thePlayer['age'] > 34)
        	{
        		if (PlayerManager::getInstance()->getRandom(1, 2) == 1)
        		{
                    $isRetired = true;
        		}
        	}
            
            if ($isRetired)
            {
                NewsManager::getInstance()->add("<font color=red><strong>" . $thePlayer["Player"]["name"] . "</strong></font>感觉自己的年龄增大，体力已经不能胜任高强度的比赛，所以决定退役。", $thePlayer["Player"]["team_id"], $nowDate, $thePlayer['ImgSrc']);
                $this->Match->query("delete from ypn_players where id=" . $thePlayer['id']);
            }
            else
            {
                $newPosition_id = PlayerManager::getInstance()->getBestPosition($thePlayer);
                if ($thePlayer['position_id'] <> $newPosition_id)
                {
                    $thePlayer['position_id'] = $newPosition_id;
                    PlayerManager::getInstance()->saveModel($thePlayer);
                    $changedPosCount++;
                }
            }
        }
        
        unset($allPlayers);
        echo 'OldPlayers Retired!<br>';flush();
        echo('<strong><font color=green>' . $changedPosCount . '</font></strong>players position changed');
		echo '球员最优位置设置成功！<br>';flush();
        
        /*随着时间球员数值的变化*/
        $this->Match->query("update ypn_players set SinewMax=SinewMax-2,speed=speed-2,ShotPower=ShotPower-2,agility=agility-2,pinqiang=pinqiang-2,scope=scope-2,popular=popular-3 where DATE_ADD(birthday, INTERVAL 30 YEAR)<'" . $nowDate . "'");
        $this->Match->query("update ypn_players set SinewMax=SinewMax+2,ShotPower=ShotPower+2 where DATE_ADD(birthday, INTERVAL 20 YEAR)>'" . $nowDate . "'");
        $this->Match->query("update ypn_players set LastSeasonScore=ScoreAll, ScoreAll=0, all_matches_count=0, InjuredDay=0, sinew=SinewMax, mind=mind+2,state=75,goal1Count=0,goal2Count=0,goal3Count=0,penalty1Count=0,penalty2Count=0,penalty3Count=0,Assist1Count=0,Assist2Count=0,Assist3Count=0,Tackle1Count=0,Tackle2Count=0,Tackle3Count=0,YellowCard1Count=0,YellowCard2Count=0,YellowCard3Count=0,RedCard1Count=0,RedCard2Count=0,RedCard3Count=0,punish1Count=0,punish2Count=0");

		/*生成欧洲超级杯*/
		//欧洲冠军联赛冠军
		$euroLeague = $this->Match->find('first', array(
				'conditions' => array(
					'class_id' => 7
				)
			)
		);
		
		if ($euroLeague["Match"]["HostGoals"] > $euroLeague["Match"]["GuestGoals"])
        {
			$winTeam = $euroLeague["HostTeam"];
		}
		else
		{
			$winTeam = $euroLeague["GuestTeam"];
		}

		$euroMember = $this->Match->find('first', array(
				'conditions' => array(
					'class_id' => 17
				)
			)
		);
        
		/*欧洲联赛冠军*/
		if ($euroMember["Match"]["HostGoals"] > $euroMember["Match"]["GuestGoals"])
        {
			$memberWinTeam = $euroMember["Match"]["HostTeam_id"];
		}
		else
		{
			$memberWinTeam = $euroMember["Match"]["GuestTeam_id"];
		}
        $this->Match->query("delete from ypn_matches where class_id=18");
        $this->Match->createNew($memberWinTeam, $winTeam['id'], ($thisYear-1) . '-8-30', 18, 0);		
        echo '超级杯 succes!<br>';flush();
        
        /*生成世俱杯*/
        $this->loadModel('FIFAClubWorldCup');
        $this->FIFAClubWorldCup->generateHalfFinal($winTeam['id'], $thisYear);
        
		/*更新欧冠联赛*/
        $this->Match->query("delete from ypn_matches where class_id in (4,5,6,7)");  

        /*更新欧冠小组中的意甲球队*/
        $shengji = TeamManager::getInstance()->find('all', array(
				'conditions' => array(
					'league_id' => 1
				),
				'order' => array('Team.score desc', 'goals desc', 'lost asc', 'Team.id asc'),
				'limit' => 3
			)
		);
		$jiangji = $this->Uclgroup->find('all', array(
				'conditions' => array(
				'league_id in (0,1,2)'
				),
				'limit' => 3
			)
		);
		
        $this->Match->query("update ypn_matches set isPlayed=0 where class_id=3");

        for ($i = 0; $i < 3; $i++)
        {
            $this->Match->query("update ypn_matches set isPlayed=1,HostTeam_id=" . $shengji[$i]['Team']['id'] . " where isPlayed=0 and class_id=3 and HostTeam_id=" . $jiangji[$i]['Team']['id']);
            $this->Match->query("update ypn_matches set isPlayed=1,GuestTeam_id=" . $shengji[$i]['Team']['id'] . " where isPlayed=0 and class_id=3 and GuestTeam_id=" . $jiangji[$i]['Team']['id']);
            $this->Match->query("update ypn_uclgroups set isTurn=1,team_id=" . $shengji[$i]['Team']['id'] . " where isTurn=0 and team_id=" . $jiangji[$i]['Team']['id']);
        }
        $this->Match->query("update ypn_uclgroups set isTurn=0,goal=0,lost=0,score=0,win=0,lose=0,draw=0");
		
		/*更新欧冠小组中的英超球队*/
        $shengji = TeamManager::getInstance()->find('all', array(
				'conditions' => array(
					'league_id' => 3
				),
				'order' => array('Team.score desc', 'goals desc', 'lost asc', 'Team.id asc'),
				'limit' => 4
			)
		);
		$jiangji = $this->Uclgroup->find('all', array(
				'conditions' => array(
				'league_id in (3,53)'
				),
				'limit' => 4
			)
		);
		
        $this->Match->query("update ypn_matches set isPlayed=0 where class_id=3");

        for ($i = 0; $i < 4; $i++)
        {
            $this->Match->query("update ypn_matches set isPlayed=1,HostTeam_id=" . $shengji[$i]['Team']['id'] . " where isPlayed=0 and class_id=3 and HostTeam_id=" . $jiangji[$i]['Team']['id']);
            $this->Match->query("update ypn_matches set isPlayed=1,GuestTeam_id=" . $shengji[$i]['Team']['id'] . " where isPlayed=0 and class_id=3 and GuestTeam_id=" . $jiangji[$i]['Team']['id']);
            $this->Match->query("update ypn_uclgroups set isTurn=1,team_id=" . $shengji[$i]['Team']['id'] . " where isTurn=0 and team_id=" . $jiangji[$i]['Team']['id']);
        }
        $this->Match->query("update ypn_uclgroups set isTurn=0,goal=0,lost=0,score=0,win=0,lose=0,draw=0");		

		/*欧冠小组赛基本奖金*/
        $Uclgroups = $this->Uclgroup->find('all', array());
		foreach ($Uclgroups as $Uclgroup) 
		{
			TeamManager::getInstance()->writeJournal($Uclgroup["Uclgroup"]["team_id"], 1, 380, '欧洲冠军联赛小组赛出场费');
			NewsManager::getInstance()->add('获得欧冠联赛小组赛奖金380W欧元', $Uclgroup["Uclgroup"]["team_id"], $nowDate, '/img/EuroChampion.jpg');
		}
	    unset($Uclgroups);	
        echo 'ucl updated!<br>';flush();
	        
        /*生成意大利超级杯*/
        $this->Match->query("delete from ypn_matches where class_id=11");
        
        $leagueWinner = TeamManager::getInstance()->find('first', array(
				'conditions' => array(
				'league_id' => 1
				),
				'order' => array(
				'Team.score' => 'desc',
				'goals' => 'desc',
				'lost' => 'asc'
				)
			)
		);

		$cupMatch = $this->Match->find('first', array(
				'conditions' => array(
				'class_id' => '10'
				)
			)
		);
		
        if ($leagueWinner['Team']['id'] == $cupMatch['Match']["HostTeam_id"])
        {
            $cupWinner = $cupMatch['GuestTeam'];
        }
        else if ($leagueWinner['Team']['id'] == $cupMatch['Match']["GuestTeam_id"])
        {
            $cupWinner = $cupMatch['HostTeam'];
        }
        else
        {
                if ($cupMatch['Match']['HostGoals'] > $cupMatch['Match']['GuestGoals'])
                {
                    $cupWinner = $cupMatch['HostTeam'];
                }
                else
                {
                    $cupWinner = $cupMatch['GuestTeam'];
                }
        }

        $this->Match->createNew($leagueWinner['Team']['id'], $cupWinner['id'], ($thisYear-1) . '-8-22', 11, 0);
        $imgSrc = "/img/honour/ItalySuperCup.jpg";
        $msg = "意甲冠军<font color=green>" . $leagueWinner['Team']['name'] . "</font>和杯赛冠军<font color=green>" . $cupWinner['name'] . "</font>" . $thisYear . "年8月19日争夺亚平宁半岛的王中王";
        
        echo 'Italy SuperCup created!<br>';

		foreach ($seriATeams as $teams) 
		{
			NewsManager::getInstance()->add($msg, $teams['Team']['id'], $nowDate, $imgSrc);
		}
		
        $this->Match->query("delete from ypn_matches where class_id in (13,14,15,16,17)");

        $i = 1;
        $cupWinnerRank = -1;
        
		foreach ($seriATeams as $teams) 
		{
			if ($teams['Team']['id'] == $cupWinner['id'])
			{
				$cupWinnerRank = $i;
			}
			$i++;
		}
	        
        /*更新意甲球队的联盟杯数据*/
		$i = 0;
		$memberUpTeams = array();
		foreach ($seriATeams as $teams) 
		{
			if ($i >= 3)
			{
				$memberUpTeams[$i-3] = $teams['Team']['id'];
			}
			$i++;
			if ($i == 7)
			{
				break;
			}
		}

        if ($cupWinnerRank == -1 || $cupWinnerRank > 8)
        {
            $memberUpTeams[3] = $cupWinner['id'];
        }

        $i = 0;
        $memberMatches = $this->Match->find('all', array(
				'conditions' => array(
				'class_id' => '12'
				),
				'contain' => array('HostTeam', 'GuestTeam')
			)
		);

		$memberDownTeams = array();
       	foreach ($memberMatches as $memberMatches) 
       	{
       		if ($memberMatches['HostTeam']['league_id'] == 1 ||$memberMatches['HostTeam']['league_id'] == 2)
       		{
       			$memberDownTeams[$i] = $memberMatches['HostTeam']['id'];
       			$i++;
       			if ($i == 4)
       			{
       				break;
       			}
       		}
            if ($memberMatches['GuestTeam']['league_id'] == 1 ||$memberMatches['GuestTeam']['league_id'] == 2)
       		{
       			$memberDownTeams[$i] = $memberMatches['GuestTeam']['id'];
       			$i++;
       		 	if ($i == 4)
       			{
       				break;
       			}
       		}
       	}

		$this->Match->query("update ypn_elgroups set isTurn=0,goal=0,lost=0,score=0,win=0,lose=0,draw=0");
        for ($k = 0; $k < count($memberDownTeams); $k++)
        {
            $this->Match->query("update ypn_matches set isPlayed=0,hostTeam_id=" . $memberUpTeams[$k] . "  where hostTeam_id=" . $memberDownTeams[$k] . " and class_id=12 and isPlayed=1");
            $this->Match->query("update ypn_matches set isPlayed=0,guestTeam_id=" . $memberUpTeams[$k] . "  where guestTeam_id=" . $memberDownTeams[$k] . " and class_id=12 and isPlayed=1");
            $this->Match->query("update ypn_elgroups set isTurn=1,team_id=" . $memberUpTeams[$k] . " where isTurn=0 and team_id=" . $memberDownTeams[$k]);
        }
        echo '意甲联盟杯数据更新成功!<br>';
		
		/*更新英超球队的联盟杯数据*/
        $ycEuropaLeagueCount = 3;
		$memberUpTeams = array();		
		$i = 0;
		foreach ($plTeams as $teams) 		
		{
			if ($i > 3)
			{
				$memberUpTeams[] = $teams['Team']['id'];
			}
			
			if ($i == 6) break;
            $i++;
		}

        $memberMatches = $this->Match->find('all', array(
				'conditions' => array(
				'class_id' => '12'
				),
				'contain' => array('HostTeam', 'GuestTeam')
			)
		);

		$memberDownTeams = array();
        $i = 0;
       	foreach ($memberMatches as $memberMatches) 
       	{
       		if ( in_array($memberMatches['HostTeam']['league_id'], array(3, 53)) && !in_array($memberMatches['HostTeam']['id'], $memberDownTeams))
       		{
       			$memberDownTeams[] = $memberMatches['HostTeam']['id'];
       			$i++;
       			if ($i == $ycEuropaLeagueCount) break;
       		}
       	}

		$this->Match->query("update ypn_elgroups set isTurn=0,goal=0,lost=0,score=0,win=0,lose=0,draw=0");
        for ($k = 0; $k < $ycEuropaLeagueCount; $k++)
        {
            $this->Match->query("update ypn_matches set isPlayed=0,hostTeam_id=" . $memberUpTeams[$k] . "  where hostTeam_id=" . $memberDownTeams[$k] . " and class_id=12 and isPlayed=1");
            $this->Match->query("update ypn_matches set isPlayed=0,guestTeam_id=" . $memberUpTeams[$k] . "  where guestTeam_id=" . $memberDownTeams[$k] . " and class_id=12 and isPlayed=1");
            $this->Match->query("update ypn_elgroups set isTurn=1,team_id=" . $memberUpTeams[$k] . " where isTurn=0 and team_id=" . $memberDownTeams[$k]);
        }
        echo '英超球队的联盟杯数据更新成功!<br>';

        /*更新意甲升降级球队*/
        $italySecondTeams = TeamManager::getInstance()->find('all', array(
	        	'conditions' => array('league_id' => 2),
                'contain' => array()
        	)
        );
        shuffle($italySecondTeams);
        $seriAShengji = array($italySecondTeams[0], $italySecondTeams[1], $italySecondTeams[2]);
        $seriAJiangji = TeamManager::getInstance()->find('all', array(
	        	'conditions' => array('league_id' => 1),
                'contain' => array(),
	        	'order' => array('score asc', 'goals asc', 'Team.id'),
	        	'limit' => 3
        	)
        );

        for ($i = 0; $i <= 2; $i++)
        {
            $this->Match->query("update ypn_matches set HostTeam_id=" . $seriAShengji[$i]['Team']['id'] . " where class_id=1 and HostTeam_id=" . $seriAJiangji[$i]['Team']['id']);
            $this->Match->query("update ypn_matches set GuestTeam_id=" . $seriAShengji[$i]['Team']['id'] . " where class_id=1 and GuestTeam_id=" . $seriAJiangji[$i]['Team']['id']);
            $this->Match->query("update ypn_teams set league_id=1 where id=" . $seriAShengji[$i]['Team']['id']);
            $this->Match->query("update ypn_teams set league_id=2 where id=" . $seriAJiangji[$i]['Team']['id']);
        }
        echo 'ItalyLeague updated!<br>';
		
		/*更新英超升降级球队*/
       $yingguan  = TeamManager::getInstance()->find('all', array(
	        	'conditions' => array('league_id' => 53),
                'contain' => array()
        	)
        );
        shuffle($yingguan);
         
        $plShengji = array($yingguan[0], $yingguan[1], $yingguan[2]);
        $plJiangji = TeamManager::getInstance()->find('all', array(
	        	'conditions' => array(
	        	'league_id' => 3
	        	),
	        	'order' => array('score asc', 'goals asc', 'Team.id'),
                'contain' => array(),
	        	'limit' => 3
        	)
        );

        for ($i = 0; $i <= 2; $i++)
        {
            $this->Match->query("update ypn_matches set HostTeam_id=" . $plShengji[$i]['Team']['id'] . " where class_id=31 and HostTeam_id=" . $plJiangji[$i]['Team']['id']);
            $this->Match->query("update ypn_matches set GuestTeam_id=" . $plShengji[$i]['Team']['id'] . " where class_id=31 and GuestTeam_id=" . $plJiangji[$i]['Team']['id']);
            $this->Match->query("update ypn_teams set league_id=3 where id=" . $plShengji[$i]['Team']['id']);
            $this->Match->query("update ypn_teams set league_id=53 where id=" . $plJiangji[$i]['Team']['id']);
        }
        echo 'PremierLeague updated!<br>';        
        
        /*降级球队人气的变化*/
        $this->Match->query("update ypn_teams set popular=popular-2 where League_Id=2");
        $this->Match->query("update ypn_players set popular=popular-3 where Team_Id in (select id from ypn_teams where League_Id=2)");
	        
        /*删除无用的比赛记录*/
        $this->Match->query("delete from ypn_matches where class_id in(8,9,10)");

        /*生成意大利杯赛事*/
        $i = 0;
        $bStr = array();
        foreach ($seriATeams as $seriATeams) 
        {
        	$bhost[$i] = $seriATeams['Team']['id'];
        	$bStr[] = $bhost[$i];
        	$i++;
        	
        	if ($i == 8) break;
        }
        
        if (mt_rand(1, 2) == 1)
        {
        	$orderType = 'asc';
        }
        else
        {
        	$orderType = 'desc';
        }

        $this->Match->query("delete from ypn_matches where class_id=2");
        
        $conditions = array('league_id'=>array(1,2),  'not'=>array('id'=> $bStr));
        $order = array('id' => $orderType);
	    $limit = 8;
	    $contain = array();
        $cupAfter8Teams = TeamManager::getInstance()->find('all', compact('conditions', 'order', 'limit', 'contain')); 
        
        for ($i = 0; $i < 8; $i++)
        {
        	switch ($i) 
        	{
        		case 0:
        		case 1:	
        			$playTime = $thisYear . "-1-13";
        			break;
        		case 2:
        		case 3:	
        		case 4:
        			$playTime = $thisYear . "-1-14";
        			break;
          		case 5:	
        		case 6:
        			$playTime = $thisYear . "-1-15";
        			break;      		
        		case 7:
        			$playTime = ($thisYear-1) . "-12-17";
        			break;
        		break;
        	}
            $this->Match->query("insert into ypn_matches (HostTeam_id,GuestTeam_id,class_id,PlayTime) values(" . $bhost[$i] . "," . $cupAfter8Teams[$i]['Team']['id'] . ",2,'" . $playTime . "')");
        }
        
        echo "italy Cup updated!<br>";

        $isYpnTeam = false;
        if ($winTeam['league_id'] == 1)
        {
        	$isYpnTeam = true;
        }

        $this->Match->query("update ypn_matches set playtime='" . ($thisYear - 1) . "-12-21' where playtime='" . ($thisYear) . "-2-13' and class_id=1");
        
        if ($isYpnTeam)
        {
            $this->Match->query("update ypn_matches set playtime='" . ($thisYear) . "-2-13' where playtime='" . ($thisYear - 1) . "-12-21' and class_id=1 and (hostTeam_id=" . $winTeam['id'] . " or guestTeam_id=" . $winTeam['id'] . ")");
        }
        
        /*赞助费电视转播费*/
        $allTeams = TeamManager::getInstance()->find('all', array('contain' => array()));
        foreach ($allTeams as $allTeams) 
        {
        	if ($allTeams['Team']['id'] == 2 || $allTeams['Team']['id'] == 4 || $allTeams['Team']['id'] == 10)
        	{
        		$tvFee = 5000;
        	}
        	else
        	{
        		$tvFee = 500;
        	}
            NewsManager::getInstance()->add('俱乐部获得赞助费<font color=green><strong>' . $allTeams['Team']['sponsor'] . '</strong></font>W欧元', $allTeams['Team']['id'], $nowDate, $allTeams['Team']['ImgSrc']);
        	NewsManager::getInstance()->add('俱乐部获得电视转播费<font color=green><strong>' . $tvFee . '</strong></font>W欧元', $allTeams['Team']['id'], $nowDate, $allTeams['Team']['ImgSrc']);
        	TeamManager::getInstance()->writeJournal($allTeams['Team']['id'], 1, $allTeams['Team']['sponsor'], '赞助费');
        	TeamManager::getInstance()->writeJournal($allTeams['Team']['id'], 1, $tvFee, '电视转播费');
        
        	/*发假期工资*/ 
        	$gongzi = PlayerManager::getInstance()->query("select sum(salary) as AllMoney from ypn_players where Team_Id=" . $allTeams['Team']['id']);
        	NewsManager::getInstance()->add("俱乐部给球员发假期工资共花费<font color=red><strong>" . round(($gongzi[0][0]['AllMoney'] * 5), 2) . "</strong></font>W欧元", $allTeams['Team']['id'], $nowDate, $allTeams['Team']['ImgSrc']);
            TeamManager::getInstance()->writeJournal($allTeams['Team']['id'], 2, $gongzi[0][0]['AllMoney'] * 5, '给球员发假期工资');
        }
        
        echo 'team salary updated!<br>';

        $this->Match->query("update ypn_players set popular=99 where popular>99");	 
        $this->Match->query("update ypn_players set mind=99 where mind>99");
        $this->Match->query("update ypn_teams set score=0,goals=0,lost=0,win=0,lose=0,draw=0");
        $this->Match->query("update ypn_matches set hostTeam_id=" . $winTeam['id'] . " where guestTeam_id=178 and class_id=20");
		$this->Match->query("update ypn_matches set PlayTime=DATE_ADD(PlayTime, INTERVAL 1 YEAR),isPlayed=0,HostGoals=0,GuestGoals=0,HostGoaler_ids='', GuestGoaler_ids='' where class_id not in (25, 20)");
        $this->Match->query("update ypn_matches set isWatched=1 where hostTeam_id=" . $this->Session->read('Auth.User.team_id') . " or guestTeam_id=" . $this->Session->read('Auth.User.team_id'));
        
        /*判断是否世界杯年，如果是则把小组赛改为今年，isPlayed=1,*/
        if (YpnManager::getInstance()->checkWorldCupDay())
		{
			$cha = $thisYear - 2010;
			$this->Match->query("update ypn_matches set PlayTime=DATE_ADD(PlayTime, INTERVAL " . $cha . " YEAR),isPlayed=0,HostGoals=0,GuestGoals=0,HostGoaler_ids='', GuestGoaler_ids='' where class_id=25");
		    $this->Match->query("update ypn_settings set today='" . $thisYear . "-6-1'");
		}
		else
		{
			$this->Match->query("update ypn_settings set today='" . $thisYear . "-7-1'");
		}
        
        NewsManager::getInstance()->saveAllData();
        
        /*reset all team random formattions*/
        $allTeams = TeamManager::getInstance()->find('all', array(
            'contain' => array()
        ));
        $this->loadModel("Formattion");
        $formattions = $this->Formattion->find('all');
        TeamManager::getInstance()->resetAllFormattion($allTeams, $formattions);
        TeamManager::getInstance()->saveMany($allTeams);
        
        echo("<div align=center><a href=newday>恭喜您完成了本赛季的教练工作，开始新的赛季将有新的希望。</a></div>");
		echo("<script>$(parent.frames['left'].document).find('#cover').hide();");exit;
	}
    
    private function resetTotalSalaryAndPlayerCount()
    {
        $this->flushNow('reset total salary<br>');
		TeamManager::getInstance()->saveMany(PlayerManager::getInstance()->resetTotalSalaryAndPlayerCount());
    }
    
	/**
	 * 刷新所有游戏数据，重新play
	 */
	public function new_game()
	{
        header("content-type:text/html; charset=utf-8");
        $this->autoRender = false;
        $this->flushNow('start new game<br>');
                
		YpnManager::getInstance()->query('TRUNCATE TABLE ypn_news;'); 
		YpnManager::getInstance()->query('TRUNCATE TABLE ypn_fifa_dates;');
		YpnManager::getInstance()->query('TRUNCATE TABLE ypn_teams');
		YpnManager::getInstance()->query('TRUNCATE TABLE ypn_matches');
		YpnManager::getInstance()->query('TRUNCATE TABLE ypn_honours');
		YpnManager::getInstance()->query('TRUNCATE TABLE ypn_players');
				
		YpnManager::getInstance()->query("update ypn_settings set today='2018-7-2'"); //更新现在日期
		
        FifaDateManager::getInstance()->updateFifaDate();
		
        MatchManager::getInstance()->resetMatches();
        $this->flushNow('比赛更新完成！<br>');
		
        TeamManager::getInstance()->resetTeams();
		$this->flushNow('球队更新完成！<br>');
		
		/*更新欧冠分组*/
        UclGroupManager::getInstance()->resetUclGroup();
		$this->flushNow('欧冠分组更新完成！<br>');
		
		/*更新欧联分组*/
        ElGroupManager::getInstance()->resetElGroup();
		$this->flushNow('欧联分组更新完成！<br>');
		
#        YpnManager::getInstance()->resetWorldcup();
#        YpnManager::getInstance()->resetEuroCup();
        
        PlayerManager::getInstance()->resetPlayers();
        $this->flushNow('players更新完成！<br>');
		
#        YpnManager::getInstance()->resetPlayerUpload();
        
        $this->resetTotalSalaryAndPlayerCount();
		
		$this->flushNow('all complete');
	}	
	
	public function help()
	{
		$this->layout = 'main';
	}
    
    public function country2Club()
    {
        YpnManager::getInstance()->country2Club();
    }
    
	/**
	 * 周每日工作
	 * @param type $weekday
	 * @param type $isTransferDay
	 * @param type $isHoliday
	 */
    private function doWeekdayTask($weekday, $isTransferDay, $isHoliday, $myTeamId)
    {
//		var_dump($weekday, $isTransferDay);exit;
		$nowDate = SettingManager::getInstance()->getNowDate();
		switch ($weekday)
		{
			case 0:
				/*卖出球员*/
				if ($isTransferDay)
				{
					$this->flushNow('正在进行转会交易...');
                    $this->oldRedirect(array('controller'=>'team', 'action'=>'sell_players'), false);
				}
				
				if (!$isHoliday)
				{
                    $this->oldRedirect(array('controller'=>'player', 'action'=>'alert_low_loyalty'), false);
				}
				
				break;
			case 1:
				/*续约&卖出球员*/
				if ($isTransferDay)
				{
					$this->flushNow('正在续约球员，请稍候...');
                    $this->oldRedirect(array('controller'=>'player', 'action'=>'continue_contract'), false);
				}

				break;
			case 2:
				/*买进球员*/
				if ($isTransferDay)
				{
					$this->flushNow('正在进行转会交易...');
                    $this->oldRedirect(array('controller'=>'team', 'action'=>'buy_players'), false);
				}
				break;
			case 3:	
				$this->flushNow('正在发工资...');
                $this->oldRedirect(array('controller'=>'team', 'action'=>'payoff'), false);
				if (!$isHoliday && $isTransferDay)
				{
					$this->flushNow('正在联系友谊赛...<br/>');
                    $this->oldRedirect(array('controller'=>'team', 'action'=>'invite_friend_match'), false);
				}
				break;
			case 4:
				$this->flushNow('ticket incoming...<br/>');
                TeamManager::getInstance()->addOtherLeagueTeamSalary($myTeamId); //非意甲球队每周也有球票收入
				$this->oldRedirect(array('controller'=>'player', 'action'=>'drink'), false);   //增加球员个人活动的意外
                break;
			case 5:/*周五*/
				if ($isTransferDay)
				{
					$this->flushNow('正在检查合同是否到期，请稍候...');
                    $this->oldRedirect(array('controller'=>'player', 'action'=>'transfer_free_agent'), false);
                }
				break;
			case 6:
				if (!$isHoliday)
				{
					$this->flushNow('正在检查训练值增长，请稍候...');
					PlayerManager::getInstance()->checkTrainingAdd($nowDate);
				}
				break;
		}
    }
	
	protected function getRedisInstance()
	{
		$redis = new \Redis();
		$redis->connect(\MainConfig::REDIS_HOST, \MainConfig::REDIS_PORT);
		return $redis;
	}
    
	public function test()
	{
		$redis = $this->getRedisInstance();
		$redis->rpush('ypn_tasks', date('Y-m-d H:i:s'));
		
		exit("ok\n");
	}
	
	/**
	 * 异步任务 cli方式
	 */
	public function run_task()
	{
		$redis = $this->getRedisInstance();
		
		while(1)
		{
			$content = $redis->lpop('ypn_tasks');
			if($content)
			{
				echo $content . "\n";	
			}
		}
	}

}