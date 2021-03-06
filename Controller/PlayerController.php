<?php
namespace Controller;

use MainConfig;
use Model\Manager\PlayerManager;
use Model\Manager\FutureContractManager;
use Model\Manager\SettingManager;
use Model\Manager\RetiredShirtManager;
use Model\Manager\CoachManager;
use Model\Manager\TeamManager;
use Model\Manager\MatchManager;
use Model\Manager\NewsManager;
use Model\Manager\YpnManager;
use Model\Core\Player;
use Model\Core\Team;
use Model\Core\News;
use Model\Core\Match;
use Model\Core\PlayerCollect;
use Model\Core\FutureContract;

class PlayerController extends AppController
{
    public $name = 'Player';
	public $layout = "main";
    
    public function transfer_free_agent()
    {
		$strHtml = '';
        $nowDate = SettingManager::getInstance()->getNowDate();
        PlayerManager::getInstance()->query("update ypn_players set team_id=0,ContractBegin=null,ContractEnd=null where ContractEnd<'" . $nowDate . "'");

		TeamManager::getInstance()->saveMany(PlayerManager::getInstance()->resettotal_salaryAndPlayerCount());
		
		/*签的未来合同已经到了 执行转会*/
		$futureContracts = FutureContract::find('all', array('conditions' => array('OldContractEnd <' => $nowDate)));
        for ($i = 0;$i < count($futureContracts);$i++)
        {
            if (empty($futureContracts[$i]->id))
            {
                FutureContractManager::getInstance()->delete($futureContracts[$i]->id, false);
                continue;
            }

            $buyTeam = TeamManager::getInstance()->findById($futureContracts[$i]->NewTeam_id);
            
            $targetPlayer = Player::getById($futureContracts[$i]->player_id);
			if(!$targetPlayer)
				continue;
			
            $targetPlayer->ContractBegin = $nowDate;
            $targetPlayer->ContractEnd = $futureContracts[$i]->NewContractEnd;
            $targetPlayer->salary = $futureContracts[$i]->NewSalary;
            $targetPlayer->team_id = $futureContracts[$i]->NewTeam_id;
            $retiredShirts = RetiredShirtManager::getInstance()->getByTeamId($futureContracts[$i]->NewTeam_id);
            $usedNOs = PlayerManager::getInstance()->getUsedNOs($futureContracts[$i]->NewTeam_id);
            
            $targetPlayer->getNewShirtNo(array_merge($retiredShirts, $usedNOs));

            if ($buyTeam['league_id'] == $targetPlayer->league_id)
            {
                $targetPlayer->cooperate = 90;
            }
            else
            {
                $targetPlayer->cooperate = 80;
                $targetPlayer->league_id = $buyTeam['league_id'];
            }

            PlayerManager::getInstance()->saveModel($targetPlayer);
            $futureContracts[$i]->delete();

            NewsManager::getInstance()->add('自由球员<font color=green><strong>' . $targetPlayer->name . '</strong></font>加入了我们', $futureContracts[$i]->NewTeam_id, $nowDate, $targetPlayer->ImgSrc);
            $strHtml .= '自由球员<font color=blue><strong>' . $targetPlayer->name . '</strong></font>投奔了' . $buyTeam['name'] . '<br>';
        }
		return $strHtml;
    }
    
    public function drink()
    {
		$nowDate = SettingManager::getInstance()->getNowDate();
        $myCoach = CoachManager::getInstance()->getMyCoach();
        $myDrinkPlayers = PlayerManager::getInstance()->drink($myCoach->id);
        foreach ($myDrinkPlayers as $dPlayer)
        {
            NewsManager::getInstance()->add("<span class='RedBold'>" . $dPlayer['name'] . "</span>由于去夜总会酗酒，状态下降", $dPlayer['team_id'], $nowDate, $this->Ypn->defaultImg($dPlayer['ImgSrc']));
        }
    }

    public function list_player_king($matchClassId, $fieldType)
    {
        $fieldName = '';
        $conditions = array();
        if (in_array($matchClassId, array(1, 31))) //联赛
        {
            if ($fieldType == 'goal')
            {
                $fieldName = 'Goal1Count';
            }
            else if ($fieldType == 'assist')
            {
                $fieldName = 'Assist1Count';
            }
            else if ($fieldType == 'tackle')
            {
                $fieldName = 'Tackle1Count';
            }
            
            if ($matchClassId == 1)
            {
                $conditions['league_id'] = 1;
            }
            else if ($matchClassId == 31)
            {
                $conditions['league_id'] = 3;
            }
        }
		else //杯赛
		{
			
		}
        
        $players = PlayerManager::getInstance()->find('all', array(
            'conditions' => $conditions,
            'order' => array($fieldName => 'desc'),
            'limit' => 20
        ));
        
        $this->set('players', $players);
        $this->set('fieldName', $fieldName);
        $this->set('fieldText', $fieldType);
        $this->render('list_player_king');
    }
    
    public function pay_birthday()
    {
        $nowDate = SettingManager::getInstance()->getNowDate();
        $conditions = array("DATE_FORMAT(birthday, '%m-%d')" => date('m-d', strtotime($nowDate)), 'team_id > ' => 0);
		$contain = array();
		$fields = array('id', 'name', 'team_id', 'loyalty', 'ImgSrc');
		$birthdayPlayers = PlayerManager::getInstance()->find('all', compact('conditions', 'contain', 'fields'));
        $myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;

		for ($i = 0;$i < count($birthdayPlayers);$i++)
		{
			if ($birthdayPlayers[$i]['team_id'] == $myTeamId)
			{
				NewsManager::getInstance()->add($birthdayPlayers[$i]['name']."今日过生日", $birthdayPlayers[$i]['team_id'], $nowDate, $birthdayPlayers[$i]['ImgSrc']);
			}
			else
			{
				if (mt_rand(1, 2) === 1)
				{
					TeamManager::getInstance()->changeMoney($birthdayPlayers[$i]['team_id'], 2, 1, $nowDate, '给' . $birthdayPlayers[$i]['name'] . '发生日补助');
					$birthdayPlayers[$i]['loyalty'] += 1;
					PlayerManager::getInstance()->saveModel($birthdayPlayers[$i], 'update');
				}
				
			}
		}
    }
    
    public function alert_low_loyalty()
    {
		$nowDate = SettingManager::getInstance()->getNowDate();
		
        /*长期不上场的忠诚度降低*/
        $conditions = array('loyalty <' => 60, 'isSelling ' => 0, 'condition_id <>' => 4, 'state >' => 95, 'sinew <' => 78);
        $contain = array();
        $players = PlayerManager::getInstance()->find('all', compact('conditions', 'contain'));

        for ($i = 0; $i < count($players); $i++)
        {
            if (!empty($players[$i]['ImgSrc']))
            {
                $imgSrc = $players[$i]['ImgSrc'];
            }
            else
            {
                $imgSrc = "/img/DefaultPlayer.jpg";
            }

            NewsManager::getInstance()->add("<font color=red>" . $players[$i]['name'] . "</font>对于您不给其足够上场机会很不满，他已萌生去意。", $players[$i]['team_id'], $nowDate, $imgSrc);	
        }
    }
    
	/**
	 * NPC续约
	 */
    public function continue_contract()
	{
		$strHtml = '';
		$nowDate = SettingManager::getInstance()->getNowDate();

		$playersArray = PlayerManager::getInstance()->query("select * from ypn_players where isSelling=0 and team_id not in (select team_id from ypn_managers) and DATE_ADD('" . $nowDate . "', INTERVAL 360 DAY)>ContractEnd and id not in (select player_id from ypn_future_contracts)"); 
        $players = PlayerManager::getInstance()->loadData($playersArray);
        
        foreach ($players as $curPlayer)
		{
			if ($curPlayer->ClubDepending < 70)
			{
				$strHtml .= "<font color=blue><strong>" . $curPlayer->name . "</strong></font>被卖出了<br>";
				
				/*卖出*/
				PlayerManager::getInstance()->query("update ypn_players set isSelling=1, fee=" . $curPlayer->estimateFee($nowDate) * $curPlayer->ClubDepending / 100 . " where id=" . $curPlayer->id);
			}
			else
			{
				/*续约*/
				$newSalary = $curPlayer->getExpectedSalary($nowDate);

				if ($curPlayer->getAge($nowDate) > 30) 
				{
					$contractEnd = date('Y', strtotime($nowDate))+mt_rand(1, 2) . "-6-30";
				}
				else
				{
					$contractEnd = date('Y', strtotime($nowDate))+mt_rand(3, 5) . "-6-30";
				}
				
				PlayerManager::getInstance()->update(array('ContractBegin'=> $nowDate, 'ContractEnd'=> $contractEnd , 'salary'=>$newSalary), array('id'=>$curPlayer->id));
			}
		}
		return $strHtml;
	}
    
    /**
     *
     * @param type $searchType 1common 2free 3all
     */
    public function buy_list($searchType=1, $curPage=1)
    {
		$this->layout = 'main';
		$perPage = 20;
		$conditions = array();
		$searchTypes = array(1=>'list', 2=>'free', 3=>'future');
		$nowDate = SettingManager::getInstance()->getNowDate();
		$isTransferDay = YpnManager::getInstance()->checkTransferDay($nowDate);
		
		if($isTransferDay)
		{
			$d1 = new \DateTime($nowDate);
			$d1->add(new \DateInterval('P6M'));
			$sixMonthLater = $d1->format('Y-m-d');

			switch ($searchType) 
			{
				case 1:
					$conditions['isSelling'] = 1;
					break;
				case 2:
					$conditions['team_id'] = 0;
					break;
				case 3:
					$conditions['ContractEnd <'] = $sixMonthLater;
			}

			$players = Player::find('all', array(
				'conditions' => $conditions,
				'fields' => ['id', 'name', 'team_id', 'position_id', 'fee', 'salary', 'popular', 'ContractBegin', 'ContractEnd', 'birthday', 'ImgSrc', 'LeftProperties', 'MidProperties', 'RightProperties'],
				'order' => array('fee'=>'desc', 'salary'=>'desc','popular'=>'desc', 'id'=>'desc'),
				'limit' => array(($curPage-1)*$perPage, $perPage)
			));


			$recordCount = PlayerManager::getInstance()->find('count', array(
				'conditions' => $conditions
			));

			$pageCount = ceil($recordCount / $perPage);

			$teamList = TeamManager::getInstance()->find('list', array(
				'conditions' => array('league_id<>'=>100),
				'fields' => array('id', 'name')
			));

			$this->set('searchTypes', $searchTypes);
			$this->set('searchType', $searchType);
			$this->set('nowDate', $nowDate);
			$this->set('players', $players);
			$this->set('teamList', $teamList);
			$this->set('pageCount', $pageCount);
			$this->set('curPage', $curPage);
		}
		
		$this->set('isTransferDay', $isTransferDay);
        $this->render('buy_list');
    }
    
    public function chuchang($groupId = 0) 
	{
		$this->layout = 'main';
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT"); 
		header("Cache-Control: no-cache, must-revalidate"); 
		header("Pramga: no-cache"); 

        $myCoach = CoachManager::getInstance()->getMyCoach();
		
		/*nextmatchclass*/
		$conditions = array('isPlayed' => 0, 'or' => array('HostTeam_id' => $myCoach->team_id, 'GuestTeam_id' => $myCoach->team_id));
		$order = array('PlayTime'=>'asc');
		$contain = array();
		$nextMatchArray = MatchManager::getInstance()->find('first', compact('conditions', 'contain', 'order'));
        $nextMatches = MatchManager::getInstance()->loadData(array($nextMatchArray));
		$matchFields = $nextMatches[0]->getMatchField();
		$fieldPunish = $matchFields['fieldPunish'];
		
		PlayerManager::getInstance()->query('update ypn_players set condition_id=3 where team_id=' . $myCoach->team_id . ' and ' . $fieldPunish . '>0');
		
		/*使用自定义分组*/
		if ($groupId <> 0)
		{
			PlayerManager::getInstance()->query('update ypn_players set condition_id=3 where condition_id=1 and team_id=' . $myCoach->team_id);
			PlayerManager::getInstance()->query('update ypn_players set condition_id=1 where group_id=' . $groupId . ' and team_id=' . $myCoach->team_id . ' and condition_id in(2, 3) and ' . $matchFields['fieldPunish'] . '=0');
		}
				
		$players = Player::findArray('all', array(
				'conditions' => array('team_id' => $myCoach->team_id),
				'order' => array('condition_id'=>'asc', 'ShirtNo'=>'asc'),
			)
		);
		
		$playersCondition1 = PlayerManager::getInstance()->find('all', array(
			'conditions' => array(
				'team_id' => $myCoach->team_id,
				'condition_id' => 1
			),
			'contain' => array()
			)
		);
		
		$playersCondition2 = PlayerManager::getInstance()->find('all', array(
			'conditions' => array(
				'team_id' => $myCoach->team_id,
				'condition_id' => 2
			),
			'contain' => array()
			)
		);

        $positions = MainConfig::$positions;
		$playergroups = \Model\Manager\PlayerGroupManager::getInstance()->find('all', [
			'conditions' => ['team_id'=>$myCoach->team_id]
		]);
		
		$cornerPositions = [];
		foreach(Match::$cornerPositions as $k=>$cornerPosition)
		{
			if(in_array($k, [1,2,3]))
			{
				$cornerPositions[$k] = mb_substr($cornerPosition, 0, 1, 'utf-8');
			}
			else
			{
				$cornerPositions[$k] = mb_substr($cornerPosition, 2, 1, 'utf-8');
			}
		}
		
		$this->set('cornerPositions', $cornerPositions);
		$this->set('positions', $positions);
		$this->set('players', $players);
		$this->set('shoufaCount', count($playersCondition1));
		$this->set('tibus', $playersCondition2);
		$this->set('playergroups', $playergroups);
		$this->set('groupId', $groupId);
		$this->set('fieldPunish', $fieldPunish);
		$this->set('playergroups', $playergroups);
		$this->set('teamId', $myCoach->team_id);
        
        $this->render('chuchang');
	}
    
    public function ajax_change_condition($playerId, $conditionId)
	{
		PlayerManager::getInstance()->update(array('condition_id'=>$conditionId), array('id'=>$playerId));
		
		$this->responseToClient(1, []);
	}
	
	public function ajax_change_position($playerId, $positionId)
	{
		PlayerManager::getInstance()->update(array('position_id'=>$positionId), array('id'=>$playerId));
	}
	
	public function buy_apply($id)
	{
		$this->layout = 'main';
		$curPlayer = PlayerManager::getInstance()->find('first', array(
			'fields' => array('id', 'name', 'salary', 'ContractBegin', 'ContractEnd', 'team_id', 'fee'),
			'conditions' => array('id'=>$id),
		));
		
		$curTeam = TeamManager::getInstance()->find('first', array(
			'fields' => array('id', 'name'),
			'conditions' => array('id'=>$curPlayer['team_id']),
		));
		
		$myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;
		
		$this->set('curPlayer', $curPlayer);
		$this->set('curTeam', $curTeam);
		$this->set('years', array(6=>6,12=>12,18=>18,24=>24,30=>30,36=>36,42=>42,48=>48,54=>54,60=>60,66=>66,72=>72));
		$this->set('myTeamId', $myTeamId);
		
		$this->render('buy_apply');
	}
		
	/**
	 * 玩家操作买入
	 * @param type $playerId
	 */
	public function buy($playerId)
	{
		$newSalary = $_POST['new_salary'];
		$newPrice = $_POST['new_price'];
		$addMonthCount = $_POST['month'];
		$buyTeamId = $_POST['buy_team_id'];
		$nowDate = SettingManager::getInstance()->getNowDate();
		$newContractEnd = date('Y-m-d', strtotime($nowDate)+$addMonthCount*30*24*3600);
		
		$curPlayer = Player::getById($playerId);
		$sellTeam = Team::getById($curPlayer->team_id);
		$isSalaryAgreed = FALSE;
		$isClubAgreed = FALSE;
		
		$expectedSalary = $curPlayer->getExpectedSalary($nowDate);
		if ($newSalary >= $expectedSalary) //player salary同意
		{
			$isSalaryAgreed = TRUE;
			$contractRemainMonth = $curPlayer->getContractRemainMonth($nowDate); //until contract_end month
			
			if ($curPlayer->team_id == 0) //real free transfer
			{
				$isClubAgreed = TRUE;
			}
			elseif(($contractRemainMonth>0) && ($contractRemainMonth <= 6)) //future free transfer
			{
				$hasFutureContract = FutureContract::findArray(['conditions'=>['player_id'=>$playerId]]);
				if($hasFutureContract)
				{
					$buyTeam = Team::getById($hasFutureContract->NewTeam_id);
					News::create("{$curPlayer->name}已经与{$buyTeam->name}签了未来合同,引进失败", $buyTeamId, $nowDate, $buyTeam->ImgSrc);
				}
				else
				{
					$newFutureContract = new FutureContract();
					$newFutureContract->player_id = $playerId;
					$newFutureContract->OldContractEnd = $curPlayer->ContractEnd;
					$newFutureContract->NewContractEnd = $newContractEnd;
					$newFutureContract->NewTeam_id = $buyTeamId;
					$newFutureContract->NewSalary = $newSalary;
					$newFutureContract->save();
					
					News::create("已经与{$curPlayer->name}达成协议,将在6个月内入队", $buyTeamId, $nowDate, $curPlayer->ImgSrc);
				}
			}
			else 
			{
				$needFee = 0;
				if($curPlayer->isSelling)
				{
					$needFee = $curPlayer->fee;
				}
				else //force buy
				{
					$needFee = $curPlayer->liquidated_damage;		
				}
				
				if ($newPrice >=  $needFee)
				{
					$isClubAgreed = TRUE;
				}
				else
				{
					$isClubAgreed = FALSE;
					News::create("{$sellTeam->name}希望卖出{$needFee}W" . $needFee, $buyTeamId, $nowDate, $sellTeam->ImgSrc);
				}
			}
		}
		else //salary不满
		{
			News::create("{$curPlayer->name}期望周薪{$expectedSalary}", $buyTeamId, $nowDate, $curPlayer->ImgSrc);
		}
		
		if ($isSalaryAgreed && $isClubAgreed)
		{
			News::create("买进{$curPlayer->name}成功", $buyTeamId, $nowDate, $curPlayer->ImgSrc);
			
			//reset total salary
			if ($curPlayer->team_id)
			{
				$sellTeam->total_salary -= $newSalary;
				$sellTeam->player_count -= 1;
				$sellTeam->addMoney($newPrice, "卖出球员{$curPlayer->name}", $nowDate);
				$sellTeam->save();
			}

			$buyTeam = Team::getById($buyTeamId);
			$buyTeam->total_salary += $newSalary;
			$buyTeam->player_count += 1;
			$buyTeam->addMoney(-$newPrice, "买进球员{$curPlayer->name}", $nowDate);
			$buyTeam->save();

			if($curPlayer->league_id == $buyTeam->league_id)
			{
				$curPlayer->cooperate = 90;
			}
			else
			{
				$curPlayer->cooperate = 80;
			}

			$curPlayer->league_id = $buyTeam->league_id;
			$curPlayer->team_id = $buyTeamId;
			$curPlayer->salary = $newSalary;
			$curPlayer->ContractBegin = $nowDate;
			$curPlayer->ClubDepending = 80;
			$curPlayer->setBestShirtNo(PlayerManager::getInstance()->getExistNos($buyTeamId));
			$curPlayer->ContractEnd = $newContractEnd;
			$curPlayer->save();
		}
		
		$this->redirect('/ypn/new_day');
	}
	
	public function ajax_get($id)
	{
		$curPlayer = PlayerManager::getInstance()->findById($id);
		echo json_encode($curPlayer);
	}
	
	public function my_list() 
	{
        $myCoach = CoachManager::getInstance()->getMyCoach();
		$nowDate = SettingManager::getInstance()->getNowDate();
		
		$players = PlayerManager::getInstance()->find('all', array(
				'conditions' => array('team_id' => $myCoach->team_id),
				'order' => array('ShirtNo'=>'asc'),
			)
		);
		
        $positions = MainConfig::$positions;
		$this->set('positions', $positions);
		$this->set('players', $players);
		$this->set('nowDate', $nowDate);
        
        $this->render('my_list');
	}
	
	public function sell_list() 
	{
        $myCoach = CoachManager::getInstance()->getMyCoach();
		$nowDate = SettingManager::getInstance()->getNowDate();
		$isTransferDay = YpnManager::getInstance()->checkTransferDay($nowDate);
		
		if ($isTransferDay)
		{
			$players = PlayerManager::getInstance()->find('all', array(
					'conditions' => array('team_id' => $myCoach->team_id),
					'order' => array('ShirtNo'=>'asc'),
				)
			);

			$positions = MainConfig::$positions;
			$this->set('positions', $positions);
			$this->set('players', $players);
			$this->set('nowDate', $nowDate);
		}

		$this->set('isTransferDay', $isTransferDay);
        $this->render('sell_list');
	}
	
	public function ajax_sell($id, $price)
	{
		PlayerManager::getInstance()->update(array('isSelling'=>1, 'fee'=>$price), array('id'=>$id));
		echo 1;
	}
	
	public function training_list()
	{
		$myCoach = CoachManager::getInstance()->getMyCoach();
		$nowDate = SettingManager::getInstance()->getNowDate();
		
		$players = PlayerManager::getInstance()->find('all', array(
				'conditions' => array('team_id' => $myCoach->team_id),
				'order' => array('ShirtNo'=>'asc'),
			)
		);
		
        $positions = MainConfig::$positions;
		$this->set('positions', $positions);
		$this->set('players', $players);
		$this->set('nowDate', $nowDate);
		$this->set('trainingList', MainConfig::$trainings);
        
        $this->render('training_list');
	}
	
	public function ajax_change_training()
	{
		$playerId = $_POST['player_id'];
		$trainingId = $_POST['training_id'];
		PlayerManager::getInstance()->update(array('training_id'=>$trainingId), array('id'=>$playerId));
		
		echo json_encode(array('status'=>0));
	}
	
	public function show($id)
	{
		$curPlayer = Player::getById($id);
		$this->data['curPlayer'] = $curPlayer;
		
		$retiredShirts = RetiredShirtManager::getInstance()->find('all', array(
				'conditions' => ['team_id'=>$curPlayer->team_id],
			)
		);
		
		$myTeamPlayers = PlayerManager::getInstance()->find('all', [
				'conditions' => ['team_id'=>$curPlayer->team_id],
			]
		);
		
		$usedNos = [];
		foreach($myTeamPlayers as $player)
		{
			if($player['id'] != $id)
			{
				$usedNos[] = $player['ShirtNo'];
			}
		}
		
		foreach($retiredShirts as $retiredShirt)
		{
			$usedNos[] = $retiredShirt['shirt'];
		}
		
		$this->data['canUsedNos'] = [];
		for($i=1;$i<100;$i++)
		{
			if(!in_array($i, $usedNos) || ($i == $curPlayer->ShirtNo) )
			{
				$this->data['canUsedNos'][] = $i;
			}
		}
		
		$this->render('show');
	}
	
	public function ajaxCollect($playerId)
	{
		$nowDate = SettingManager::getInstance()->getNowDate();
		
		$newPlayerCollect = new \Model\Core\PlayerCollect();
		$newPlayerCollect->player_id = $playerId;
		$newPlayerCollect->manager_id = 1;
		$newPlayerCollect->created_at = $nowDate;
		$newPlayerCollect->save();
		
		exit(json_encode(['code'=>1]));
	}
	
	public function collect_list()
	{
		$playerCollects = PlayerCollect::find('all');
		
		$this->data['collectPlayers'] = [];
		
		foreach($playerCollects as $playerCollect)
		{
			$curPlayer = PlayerManager::getInstance()->findById($playerCollect->player_id);
			if($curPlayer)
			{
				$this->data['collectPlayers'][] = [
					'player_id' => $curPlayer['id'],
					'name' => $curPlayer['name'],
					'collect_date' => date('Y-m-d', strtotime($playerCollect->created_at))
				];
			}
			else
			{
				$playerCollect->delete();
			}
		}
		
		$this->render('/collect_list');
	}
	
	public function ajax_change_shirt_no()
	{
		$playerId = $_POST['player_id'];
		$shirtNo = $_POST['shirt_no'];
		PlayerManager::getInstance()->update(array('ShirtNo'=>$shirtNo), array('id'=>$playerId));
		
		exit(json_encode(['code'=>1]));
	}
	
	public function ajax_continue_contract()
	{
		$playerId = $_POST['player_id'];
		$targetMonth = $_POST['target_month'];
		$targetSalary = $_POST['target_salary'];
		$nowDate = SettingManager::getInstance()->getNowDate();
		$code = 0;
		$data = [];
		$curPlayer = Player::getById($playerId);
		
		$expectedSalary = $curPlayer->getExpectedSalary($nowDate);
		
		if($targetSalary >= $expectedSalary)
		{
			$code = 1;
			
			$curPlayer->salary = $targetSalary;
			$curPlayer->ContractBegin = $nowDate;
			$curPlayer->ContractEnd = date('Y-m-d', strtotime($nowDate)+$targetMonth*30*24*3600);
			$curPlayer->save();
			
			$data['contract_begin'] = date('Y.m.d', strtotime($curPlayer->ContractBegin));
			$data['contract_end'] = date('Y.m.d', strtotime($curPlayer->ContractEnd));
		}
		else
		{
			$data['expected_salary'] = $expectedSalary;
			$code = 0;
		}
		
		$this->responseToClient($code, $data);
	}

	public function changegroup($playerId, $groupId)
	{
		$curPlayer = Player::getById($playerId);
		
		$curPlayer->group_id = $groupId;
		$curPlayer->save();
		
		$this->redirect("/player/chuchang");
	}
	
	public function collect_del($playerId)
	{
		$playerCollects = PlayerCollect::find('all', [
			'conditions' => ['player_id'=>$playerId]
		]);
		
		foreach($playerCollects as $playerCollect)
		{
			$playerCollect->delete();
		}
		
		$this->redirect("/player/collect_list");
	}
	
	public function ajax_change_corner()
	{
		$playerId = $_POST['player_id'];
		$cornerId = $_POST['corner_id'];
		$curPlayer = Player::getById($playerId);
		$curPlayer->CornerPosition_id = $cornerId;
		$curPlayer->save();
		exit(json_encode(['code'=>1]));
	}
	
	public function ajax_set_pinqiang()
	{
		$playerId = $_POST['player_id'];
		$pinqiang = $_POST['pinqiang'];
		$curPlayer = Player::getById($playerId);
		$curPlayer->pinqiang = $pinqiang;
		$curPlayer->save();
		exit(json_encode(['code'=>1]));
	}
	
	public function ajax_set_shot_desire()
	{
		$playerId = $_POST['player_id'];
		$shotDesire = $_POST['shot_desire'];
		$curPlayer = Player::getById($playerId);
		$curPlayer->ShotDesire = $shotDesire;
		$curPlayer->save();
		exit(json_encode(['code'=>1]));
	}
		
	public function ajax_set_scope()
	{
		$playerId = $_POST['player_id'];
		$scope = $_POST['scope'];
		$curPlayer = Player::getById($playerId);
		$curPlayer->scope = $scope;
		$curPlayer->save();
		exit(json_encode(['code'=>1]));
	}
}