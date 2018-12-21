<?php
namespace Controller;

use Controller\AppController;
use Model\Core\Team;
use Model\Core\Player;
use Model\Core\Manager;
use Model\Core\News;
use Model\Core\Match;
use Model\Manager\MatchManager;
use Model\Manager\CoachManager;
use Model\Manager\SettingManager;
use Model\Manager\YpnManager;
use Model\Manager\TeamManager;
use Model\Manager\PlayerManager;
use Model\Manager\NewsManager;
use Model\Manager\FifaDateManager;
use Model\Manager\UclGroupManager;
use Model\Manager\ElgroupManager;
use Model\Core\Uclgroup;
use Model\Core\Elgroup;
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
		$curDate = SettingManager::getInstance()->getNowDate();
		$nowDate = '';

		if (Match::find('count', ['conditions' => ['isPlayed'=>0, 'PlayTime'=>$curDate]]) > 0) //如果前一天有未完成赛事必须先强制打完才能执行new_day下面的逻辑
		{
			$this->redirect('/match/today');
		}
		else
		{
			SettingManager::getInstance()->addDate();
			$nowDate = date('Y-m-d', strtotime($curDate . ' +1 day'));
		}
		
		$isHoliday = YpnManager::getInstance()->checkHoliday($nowDate);
		

		/*如果赛季比赛全完事，则进入新赛季页面*/
		if (Match::find('count', ['conditions' => ['isPlayed'=>0]]) == 0)
		{
			$this->newSeason();
		}
		else
		{
			$allMatchHtml = $this->doDateTask($nowDate, $isHoliday, $myTeamId);

			$this->doControllerFunction(array('controller'=>'player', 'action'=>'pay_birthday'), false); /*过生日的队员发奖金*/

			/*列出近期新闻，如果不采用弹出窗口显示则不用列出*/
			$this->set('news', NewsManager::getInstance()->getUnreadNews($myTeamId));
			NewsManager::getInstance()->readAll($myTeamId);
			
			$this->training($isHoliday, $myTeamId, $nowDate);
			PlayerManager::getInstance()->doNormal(); //球员日常变化
			
			$this->set('allMatchHtml', $allMatchHtml);
			$this->render('new_day');
		}
	}
	
	/**
	 * 周每日工作
	 * @param date $nowDate
	 * @param bool $isHoliday
	 * @param int $myTeamId
	 */
    private function doDateTask($nowDate, $isHoliday, $myTeamId)
    {
		$isTransferDay = YpnManager::getInstance()->checkTransferDay($nowDate);
		$weekday = date("w", strtotime($nowDate));
		$strHtml = '';
		
				
		/*如果是FIFA-DAY的前一天则抽调国家队队员*/
//		$this->inviteFriendMatch($nowDate);
		
		switch (date('m-d', strtotime($nowDate))) {
			case '06-01':
				$this->prepareI18nMatch(); //准备国际比赛上调到国家队的球员
				break;
			case '09-01':
				$strHtml .= TeamController::getInstance()->get_young_players();
				break;
		}
			
		switch ($weekday)
		{
			case 0:
				/*卖出球员*/
				if ($isTransferDay)
				{
					$strHtml .= '正在进行转会交易...<br/>';
					$strHtml .= $this->doControllerFunction(array('controller'=>'team', 'action'=>'sell_players'), false); 
				}
				
				if (!$isHoliday)
				{
                    $strHtml .= $this->doControllerFunction(array('controller'=>'player', 'action'=>'alert_low_loyalty'), false);
				}
				
				break;
			case 1:
				/*续约&卖出球员*/
				if ($isTransferDay)
				{
					$strHtml .= '正在续约球员，请稍候...<br/>';
					$strHtml .= $this->doControllerFunction(array('controller'=>'player', 'action'=>'continue_contract'), false); 
				}

				break;
			case 2:
				/*买进球员*/
				if ($isTransferDay)
				{
					$strHtml .= '正在进行转会交易...<br/>';
					$strHtml .= $this->doControllerFunction(array('controller'=>'team', 'action'=>'buy_players'), false);
				}
				break;
			case 3:	
				$strHtml .= '正在发工资...<br/>';
                $this->doControllerFunction(array('controller'=>'team', 'action'=>'payoff'), false);
				if (!$isHoliday && $isTransferDay)
				{
					$strHtml .= '正在联系友谊赛...<br/>';
                    $strHtml .= $this->doControllerFunction(array('controller'=>'team', 'action'=>'invite_friend_match'), false);
				}
				break;
			case 4:
				$strHtml .= 'ticket incoming...<br/>';
                TeamManager::getInstance()->addOtherLeagueTeamSalary($myTeamId); //非意甲球队每周也有球票收入
				$this->doControllerFunction(array('controller'=>'player', 'action'=>'drink'), false);   //增加球员个人活动的意外
                break;
			case 5:/*周五*/
				if ($isTransferDay)
				{
					$strHtml .= '正在检查合同是否到期，请稍候...<br/>';
					$this->doControllerFunction(array('controller'=>'player', 'action'=>'transfer_free_agent'), false); 
                }
				break;
			case 6:
				if (!$isHoliday)
				{
					$strHtml .= '正在检查训练值增长，请稍候...<br/>';
					PlayerManager::getInstance()->checkTrainingAdd($nowDate);
				}
				break;
		}
		
		return $strHtml;
    }
	
	private function training($isHoliday, $myTeamId, $nowDate)
	{
		$todayUnplayedMatches = Match::find('all', [
			'conditions' => [
				'isPlayed' => 0,
				'PlayTime' => $nowDate
				]
			]);
		
		$todayMatchTeamIds = array();
		foreach($todayUnplayedMatches as $m)
		{
			array_push($todayMatchTeamIds, $m->HostTeam_id, $m->GuestTeam_id);
		}
		
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
		/*如果是世界杯年，球员伤病，状态不清0，日期不跳转，正常+1*/
		$nowDate = date("Y-m-d", strtotime(SettingManager::getInstance()->getNowDate()));
		$thisYear = date('Y', strtotime($nowDate));
	    PlayerManager::getInstance()->query("delete from ypn_news");

        /*当年的FIFA足球先生*/
		$fifaMvp = Player::find('first', [
			'fields' => ['id', 'name', 'total_score/all_matches_count as pingjun'],
			'conditions' => ['all_matches_count >' => 9],
			'order' => ['pingjun' => 'desc'],
			'limit' => 20
		]);
		
		PlayerManager::getInstance()->query("update ypn_players set popular=popular+10 where id=" . $fifaMvp->id);
		$managers = Manager::find('all', array(
			'conditions' => array(
				'team_id <> ' => 0
				)	        	
			)
		);

		foreach ($managers as $manager) {
			NewsManager::getInstance()->add("本年度FIFA足球先生是<font color=green><strong>" . $fifaMvp->name . "</strong></font>", $manager->team_id, $nowDate, "/img/fifa.gif");
		}

        PlayerManager::getInstance()->query("update ypn_players set popular=99 where popular>99");

        echo 'FIFA MVP SUCCES!<br>';flush();
         
        /*更新FIFA-DAY*/
        YpnManager::getInstance()->query('update ypn_fifa_dates set PlayDate= date_add(PlayDate, INTERVAL 1 year)');
		echo 'FIFA-DAY SUCCES!<br>';flush();
         
		 /*设置球员最优位置，老队员准备退役*/
		$allPlayerCount = Player::find('count');
		$perPage = 1000;
		$pageCount = ceil($allPlayerCount / $perPage);
		for($i=1;$i<$pageCount;$i++)
		{
			$players = Player::find('all', [
				'limit' => ($i-1)*$perPage . ',' . $perPage
			]);
			
			$changedPosCount = 0;
			foreach ($players as $thePlayer) 
			{
				if ($thePlayer->position_id == 4)
				{
					$retiredSinew = 70;
				}
				else
				{
					$retiredSinew = 78;
				}

				$isRetired = 0;
				$thePlayerAge = $thePlayer->getAge($nowDate);
				if (( ($thePlayer->SinewMax < $retiredSinew) || $thePlayer->team_id == 0) && ($thePlayerAge > 34))
				{
					$isRetired = 1;
				}
				else if( ($thePlayer->SinewMax < $retiredSinew) || $thePlayerAge > 34)
				{
					$isRetired = mt_rand(0, 1);
				}

				if ($isRetired)
				{
					News::create("<font color=red><strong>" . $thePlayer->name . "</strong></font>感觉自己的年龄增大，体力已经不能胜任高强度的比赛，所以决定退役。", $thePlayer->team_id, $nowDate, $thePlayer->ImgSrc);
					$thePlayer->delete();
				}
				else
				{
					$newPositionId = $thePlayer->getBestPosition();
					if ($thePlayer->position_id <> $newPositionId)
					{
						$thePlayer->position_id = $newPositionId;
						$thePlayer->save();
						$changedPosCount++;
					}
				}
			}
		}
        
        echo 'OldPlayers Retired!<br>';flush();
        echo('<strong><font color=green>' . $changedPosCount . '</font></strong>players position changed');
		echo '球员最优位置设置成功！<br>';flush();
 
		/*生成欧洲超级杯*/
		//欧洲冠军联赛冠军
		$eclFinalMatch = Match::find('first', array(
				'conditions' => array(
					'class_id' => 7
				)
			)
		);
		
		$uclWinTeamId = 0;
		if ($eclFinalMatch->HostGoals > $eclFinalMatch->GuestGoals)
        {
			$uclWinTeamId = $eclFinalMatch->HostTeam_id;
		}
		else
		{
			$uclWinTeamId = $eclFinalMatch->GuestTeam_id;
		}

		$elFinalMatch = Match::find('first', array(
				'conditions' => array(
					'class_id' => 17
				)
			)
		);
        
		/*欧罗巴联赛冠军*/
		if ($elFinalMatch->HostGoals > $elFinalMatch->GuestGoals)
        {
			$elWinTeamId = $elFinalMatch->HostTeam_id;
		}
		else
		{
			$elWinTeamId = $elFinalMatch->GuestTeam_id;
		}
		
        PlayerManager::getInstance()->query("delete from ypn_matches where class_id=18");
        Match::create($elWinTeamId, $uclWinTeamId, ($thisYear-1) . '-8-30', 18, 0);		//底部会把所有match日期增加1年
        echo '超级杯 succes!<br>';flush();
        
        /*生成世俱杯*/
		$fcwc = Match::getById(1954);
		$fcwc->isPlayed = 0;
		$fcwc->HostGoals = 0;
		$fcwc->GuestGoals = 0;
		$fcwc->HostTeam_id = $uclWinTeamId;
		$fcwc->save();
		
		$fcwcOtherMatches = Match::find(['conditions'=> ['class_id'=> [21,22]]]);
		foreach($fcwcOtherMatches as $fcwcOtherMatch)
		{
			$fcwcOtherMatch->delete();
		}
		
		$italySeriaATeams = Team::find('all', array(
				'conditions' => ['league_id' => 1],
				'order' => ['score'=>'desc', 'goals'=>'desc', 'lost'=>'asc', 'id'=>'asc']
			)
		);
		
		$englandSuperTeams = Team::find('all', array(
				'conditions' => ['league_id' => 3],
				'order' => ['score'=>'desc', 'goals'=>'desc', 'lost'=>'asc', 'id'=>'asc']
			)
		);
		
		/*更新欧冠联赛*/
		$uclGroups = Uclgroup::find('all');
		$italyUclDownTeamIds = [];
		$englandUclDownTeamIds = [];
		$uclMap = [];
		foreach($uclGroups as $uclTeam)
		{
			$curTeam = Team::getById($uclTeam->team_id);
			if(in_array($curTeam->league_id, [1,2]))
			{
				$italyUclDownTeamIds[] = $uclTeam->team_id;
			}
			elseif($curTeam->league_id == 3)
			{
				$englandUclDownTeamIds[] = $uclTeam->team_id;
			}
		}
		
		//欧罗巴联赛的旧数据
		$elGroups = Elgroup::find('all');
		$italyElDownTeamIds = [];
		$englandElDownTeamIds = [];

		foreach($elGroups as $elTeam)
		{
			$curTeam = Team::getById($elTeam->team_id);
			if(in_array($curTeam->league_id, [1,2]))
			{
				$italyElDownTeamIds[] = $elTeam->team_id;
			}
			elseif($curTeam->league_id == 3)
			{
				$englandElDownTeamIds[] = $elTeam->team_id;
			}
		}
        
		PlayerManager::getInstance()->query("update ypn_matches set isPlayed=0 where class_id=3");

        /*更新欧冠小组中的意甲球队*/
		$italyUclUpCount = count($italyUclDownTeamIds);
		$italyElUpCount = count($italyElDownTeamIds);

		
		$italyUclUpTeams = array_slice($italySeriaATeams, 0, $italyUclUpCount);
		foreach($italyUclDownTeamIds as $k=>$teamId)
		{
			$uclMap[$teamId] = $italyUclUpTeams[$k]->id;
		}
		
		/*更新欧冠小组中的英超球队*/
		$englandUclUpCount = count($englandUclDownTeamIds);
		$englandElUpCount = count($englandElDownTeamIds);
		$englandUclUpTeams = array_slice($englandSuperTeams, 0, $englandUclUpCount);
		foreach($englandUclDownTeamIds as $k=>$teamId)
		{
			$uclMap[$teamId] = $englandUclUpTeams[$k]->id;
		}
		
		foreach($uclGroups as $uclGroup)
		{
			if(isset($uclMap[$uclGroup->team_id]))
			{
				$uclGroup->team_id = $uclMap[$uclGroup->team_id];
			}
			
			$uclGroup->goal = 0;
			$uclGroup->lost = 0;
			$uclGroup->score = 0;
			$uclGroup->win = 0;
			$uclGroup->lose = 0;
			$uclGroup->draw = 0;
			$uclGroup->save();
		}
		
		$uclTeamMatches = Match::find('all', ['conditions'=>['class_id'=>3]]);
		foreach($uclTeamMatches as $uclTeamMatch)
		{
			if(isset($uclMap[$uclTeamMatch->HostTeam_id]))
			{
				$uclTeamMatch->HostTeam_id = $uclMap[$uclTeamMatch->HostTeam_id];
			}
			elseif(isset($uclMap[$uclTeamMatch->GuestTeam_id]))
			{
				$uclTeamMatch->GuestTeam_id = $uclMap[$uclTeamMatch->GuestTeam_id];
			}
			
			$uclTeamMatch->isPlayed = 0;
			$uclTeamMatch->save();
		}
		

		/*欧冠小组赛基本奖金*/
		foreach ($uclGroups as $uclGroup) 
		{
			$msg = '欧冠联赛小组赛出场费';
			$reward = 380;
			$curTeam = Team::getById($uclGroup->team_id);
			$curTeam->addMoney($reward, $msg, $nowDate);
			$curTeam->save();
			News::create("获得{$msg}{$reward}万欧元", $uclGroup->team_id, $nowDate, '/res/img/EuroChampion.jpg');
		}
	    unset($uclGroups);	
        echo 'ucl updated!<br>';flush();
	        
		PlayerManager::getInstance()->query("delete from ypn_matches where class_id in (4,5,6,7,13,14,15,16,17)"); 
				
		$elMap = [];
		$italyElUpTeams = array_slice($italySeriaATeams, $italyUclUpCount, $italyElUpCount);
		foreach($italyElDownTeamIds as $k=>$teamId)
		{
			$elMap[$teamId] = $italyElUpTeams[$k]->id;
		}
		
		$englandElUpTeams = array_slice($englandSuperTeams, $englandUclUpCount, $englandElUpCount);
		foreach($englandElDownTeamIds as $k=>$teamId)
		{
			$elMap[$teamId] = $englandElUpTeams[$k]->id;
		}
		
		foreach($elGroups as $elGroup)
		{
			if(isset($elMap[$elGroup->team_id]))
			{
				$elGroup->team_id = $elMap[$elGroup->team_id];
			}
			
			$elGroup->goal = 0;
			$elGroup->lost = 0;
			$elGroup->score = 0;
			$elGroup->win = 0;
			$elGroup->lose = 0;
			$elGroup->draw = 0;
			$elGroup->save();
		}
		
		$elTeamMatches = Match::find('all', ['conditions'=>['class_id'=>12]]);
		foreach($elTeamMatches as $elTeamMatch)
		{
			if(isset($elMap[$elTeamMatch->HostTeam_id]))
			{
				$elTeamMatch->HostTeam_id = $elMap[$elTeamMatch->HostTeam_id];
			}
			elseif(isset($elMap[$elTeamMatch->GuestTeam_id]))
			{
				$elTeamMatch->GuestTeam_id = $elMap[$elTeamMatch->GuestTeam_id];
			}
			
			$elTeamMatch->isPlayed = 0;
			$elTeamMatch->save();
		}
		
        echo '欧罗巴联赛数据更新成功!<br>';
		
        /*更新意甲升降级球队*/
		$this->leagueLevelUp($italySeriaATeams, 1, 2, 1, 3);
        echo 'ItalyLeague updated!<br>';
		
		/*更新英超升降级球队*/
		$this->leagueLevelUp($englandSuperTeams, 3, 53, 31, 3);
        echo 'PremierLeague updated!<br>';        
        
        /*赞助费电视转播费*/
        $allTeams = Team::find('all', array('conditions' => array('league_id <>'=>100)));
        foreach ($allTeams as $curTeam) 
        {
        	if (in_array($curTeam->team_id, [2,4,10]) || ($curTeam->league_id == 3) )
        	{
        		$tvFee = 5000;
        	}
        	else
        	{
        		$tvFee = 500;
        	}
			
			$curTeam->addMoney($curTeam->sponsor, '获得赞助费', $nowDate);
			$curTeam->addMoney($tvFee, '获得电视转播费', $nowDate);
			$curTeam->addMoney(-$curTeam->TotalSalary, '发假期工资', $nowDate);
			
			//随机改阵型
			
			
			$curTeam->save();
        }
        
        echo 'team salary updated!<br>';

        PlayerManager::getInstance()->query("update ypn_players set popular=99 where popular>99");	 
        PlayerManager::getInstance()->query("update ypn_players set mind=99 where mind>99");
        PlayerManager::getInstance()->query("update ypn_teams set score=0,goals=0,lost=0,win=0,lose=0,draw=0");
		PlayerManager::getInstance()->query("update ypn_matches set PlayTime=DATE_ADD(PlayTime, INTERVAL 1 YEAR),isPlayed=0,HostGoals=0,GuestGoals=0,HostGoaler_ids='', GuestGoaler_ids='' where class_id not in (25, 20)");
        
        /*判断是否世界杯年，如果是则把小组赛改为今年，isPlayed=1,*/
        if (YpnManager::getInstance()->checkWorldCupDay($nowDate))
		{
			$cha = $thisYear - 2010;
			PlayerManager::getInstance()->query("update ypn_matches set PlayTime=DATE_ADD(PlayTime, INTERVAL " . $cha . " YEAR),isPlayed=0,HostGoals=0,GuestGoals=0,HostGoaler_ids='', GuestGoaler_ids='' where class_id=25");
//		    PlayerManager::getInstance()->query("update ypn_settings set today='" . $thisYear . "-6-1'");
			PlayerManager::getInstance()->query("update ypn_settings set today='" . $thisYear . "-7-1'");
		}
		else
		{
			PlayerManager::getInstance()->query("update ypn_settings set today='" . $thisYear . "-7-1'");
		}
        
        /*删除无用的比赛记录*/
	    PlayerManager::getInstance()->query("delete from ypn_matches where class_id in (8,9,10,21,22,23,24,37)");
	    PlayerManager::getInstance()->query("update ypn_matches set isWatched=0, mvp_player_id=0, replay='' where class_id<>25");
		       
        /*随着时间球员数值的变化*/
        PlayerManager::getInstance()->query("update ypn_players set SinewMax=SinewMax-2,speed=speed-2,ShotPower=ShotPower-2,agility=agility-2,pinqiang=pinqiang-2,scope=scope-2,popular=popular-3 where DATE_ADD(birthday, INTERVAL 30 YEAR)<'" . $nowDate . "'");
        PlayerManager::getInstance()->query("update ypn_players set SinewMax=SinewMax+2,ShotPower=ShotPower+2 where DATE_ADD(birthday, INTERVAL 20 YEAR)>'" . $nowDate . "'");
        PlayerManager::getInstance()->query("update ypn_players set LastSeasonScore=total_score, total_score=0, all_matches_count=0, InjuredDay=0, sinew=SinewMax, mind=mind+2,state=75,goal1Count=0,goal2Count=0,goal3Count=0,penalty1Count=0,penalty2Count=0,penalty3Count=0,Assist1Count=0,Assist2Count=0,Assist3Count=0,Tackle1Count=0,Tackle2Count=0,Tackle3Count=0,YellowCard1Count=0,YellowCard2Count=0,YellowCard3Count=0,RedCard1Count=0,RedCard2Count=0,RedCard3Count=0,punish1Count=0,punish2Count=0");
        
        echo("<div align=center><a href='/ypn/new_day'>恭喜您完成了本赛季的教练工作，开始新的赛季将有新的希望。</a></div>");
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
        ElgroupManager::getInstance()->resetElgroup();
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

	/**
	 * 联赛升降级
	 * @param type $highLevelTeams
	 * @param type $highLevelLeagueId
	 * @param type $lowLevelLeagueId
	 * @param type $matchClassId
	 * @param type $upCount
	 */
	private function leagueLevelUp($highLevelTeams, $highLevelLeagueId, $lowLevelLeagueId, $matchClassId, $upCount)
	{
        $lowLevelTeams = Team::find('all', ['conditions'=>['league_id'=>$lowLevelLeagueId]]);
        shuffle($lowLevelTeams);
		
		$seriaADownTeams = array_slice($highLevelTeams, count($highLevelTeams)-$upCount, $upCount);
		$seriaBUpTeams =  array_slice($lowLevelTeams, 0, $upCount);
		
		$seriaAUpMap = []; //降级TeamId=>升级TeamId
		foreach($seriaADownTeams as $k=>$curTeam)
		{
			$curTeam->league_id = $lowLevelLeagueId;
			$curTeam->popular -= 5;
			$curTeam->save();
			
			$seriaAUpMap[$curTeam->team_id] = $seriaBUpTeams[$k]->team_id;
		}
		
		foreach($seriaBUpTeams as $curTeam)
		{
			$curTeam->league_id = $highLevelLeagueId;
			$curTeam->popular += 5;
			$curTeam->save();
		}
		
		$seriaAMatches = Match::find('all', ['conditions'=>['class_id'=>$matchClassId]]);
		foreach($seriaAMatches as $seriaAMatch)
		{
			if(isset($seriaAUpMap[$seriaAMatch->HostTeam_id]))
			{
				$seriaAMatch->HostTeam_id = $seriaAUpMap[$seriaAMatch->HostTeam_id];
				$seriaAMatch->save();
			}
			elseif(isset($seriaAUpMap[$seriaAMatch->GuestTeam_id]))
			{
				$seriaAMatch->GuestTeam_id = $seriaAUpMap[$seriaAMatch->GuestTeam_id];
				$seriaAMatch->save();
			}
		}
	}
}