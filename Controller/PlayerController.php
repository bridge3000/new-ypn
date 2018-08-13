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
use \DateTime;
use \DateInterval;

class PlayerController extends AppController
{
    public $name = 'Player';
	public $layout = "main";
    
    public function transfer_free_agent()
    {
        $nowDate = SettingManager::getInstance()->getNowDate();
        PlayerManager::getInstance()->query("update ypn_players set team_id=0,ContractBegin=null,ContractEnd=null where ContractEnd<'" . $nowDate . "'");

		TeamManager::getInstance()->saveMany(PlayerManager::getInstance()->resetTotalSalaryAndPlayerCount());
		
		$futureContracts = FutureContractManager::getInstance()->find('all', array('conditions' => array('OldContractEnd <' => $nowDate)));
        for ($i = 0;$i < count($futureContracts);$i++)
        {
            if (empty($futureContracts[$i]['id']))
            {
                FutureContractManager::getInstance()->delete($futureContracts[$i]['id'], false);
                continue;
            }

            $buyTeam = TeamManager::getInstance()->findById($futureContracts[$i]['NewTeam_id']);
            
            $playerData = PlayerManager::getInstance()->findById($futureContracts[$i]['player_id']);
            $targetPlayer = PlayerManager::getInstance()->loadData($playerData, 'Player');
            $targetPlayer->ContractBegin = $nowDate;
            $targetPlayer->ContractEnd = $futureContracts[$i]['NewContractEnd'];
            $targetPlayer->salary = $futureContracts[$i]['NewSalary'];
            $targetPlayer->team_id = $futureContracts[$i]['NewTeam_id'];
            $retiredShirts = RetiredShirtManager::getInstance()->getByTeamId($futureContracts[$i]['NewTeam_id']);
            $usedNOs = PlayerManager::getInstance()->getUsedNOs($futureContracts[$i]['NewTeam_id']);
            
            $targetPlayer->getNewShirtNo(array_merge($retiredShirts, $usedNOs));

            if ($buyTeam['league_id'] == $futureContracts[$i]['league_id'])
            {
                $targetPlayer->cooperate = 90;
            }
            else
            {
                $targetPlayer->cooperate = 80;
                $targetPlayer->league_id = $buyTeam['league_id'];
            }

            PlayerManager::getInstance()->save($targetPlayer);
            $this->FutureContract->delete($futureContracts[$i]['id'], false);

            NewsManager::getInstance()->add('自由球员<font color=green><strong>' . $targetPlayer->name . '</strong></font>加入了我们', $futureContracts[$i]['NewTeam_id'], $nowDate, $targetPlayer->ImgSrc);
            echo('自由球员<font color=blue><strong>' . $targetPlayer->name . '</strong></font>投奔了' . $buyTeam['name'] . '<br>');flush();

        }
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
        if (in_array($matchClassId, array(1, 31)))
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
					PlayerManager::getInstance()->save($birthdayPlayers[$i], 'update');
				}
				
			}
		}
    }
    
    public function alert_low_loyalty()
    {
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
    
    public function continue_contract()
	{
		$nowDate = SettingManager::getInstance()->getNowDate();

		$playersArray = PlayerManager::getInstance()->query("select * from ypn_players where isSelling=0 and team_id not in (select team_id from ypn_managers) and DATE_ADD('" . $nowDate . "', INTERVAL 360 DAY)>ContractEnd and id not in (select player_id from ypn_future_contracts)"); 
        $players = PlayerManager::getInstance()->loadData($playersArray);
        
        foreach ($players as $curPlayer)
		{
			if ($curPlayer->ClubDepending < 70)
			{
				$this->flushNow("<font color=blue><strong>" . $curPlayer->name . "</strong></font>被卖出了<br>");
				
				/*卖出*/
				PlayerManager::getInstance()->execute("update ypn_players set isSelling=1, fee=" . $curPlayer->estimateFee($nowDate) * $curPlayer->ClubDepending / 100 . " where id=" . $curPlayer->id);
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
			$d1 = new DateTime($nowDate);
			$d1->add(new DateInterval('P6M'));
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

			$records = PlayerManager::getInstance()->find('all', array(
				'conditions' => $conditions,
				'fields' => array('id', 'name', 'team_id', 'position_id', 'fee', 'salary', 'popular', 'ContractBegin', 'ContractEnd', 'birthday'),
				'order' => array('fee'=>'desc', 'salary'=>'desc','popular'=>'desc', 'id'=>'desc'),
				'limit' => array(($curPage-1)*$perPage, $perPage)
			));

			$players = PlayerManager::getInstance()->loadData($records);

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
    
    public function chuchang($group_id = 0) 
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
		if ($group_id <> 0)
		{
			PlayerManager::getInstance()->query('update ypn_players set condition_id=3 where condition_id=1 and team_id=' . $myCoach->team_id);
			PlayerManager::getInstance()->query('update ypn_players set condition_id=1 where group_id=' . $group_id . ' and team_id=' . $myCoach->team_id . ' and condition_id in(2, 3) and ' . $matchFields['fieldPunish'] . '=0');
		}
				
		$players = PlayerManager::getInstance()->find('all', array(
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
		$playergroups = PlayerManager::getInstance()->query('select * from ypn_player_groups where team_id=' . $myCoach->team_id);
		$this->set('positions', $positions);
		$this->set('players', $players);
		$this->set('shoufaCount', count($playersCondition1));
		$this->set('tibus', $playersCondition2);
		$this->set('playergroups', $playergroups);
		$this->set('group_id', $group_id);
		$this->set('cornerpositions', MainConfig::$cornerPositions);
		$this->set('fieldPunish', $fieldPunish);
		$this->set('playergroups', array());
        
        $this->render('chuchang');
	}
    
    public function ajax_change_condition($playerId, $conditionId)
	{
		PlayerManager::getInstance()->update(array('condition_id'=>$conditionId), array('id'=>$playerId));
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
			'conditions' => array('id'=>$id),
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
	 * 人操作买入
	 * @param type $playerId
	 */
	public function buy($playerId)
	{
		$newSalary = $_POST['new_salary'];
		$newPrice = $_POST['new_price'];
		$addMonthCount = $_POST['month'];
		$buyTeamId = $_POST['buy_team_id'];
		$nowDate = SettingManager::getInstance()->getNowDate();
		$curPlayerArray = PlayerManager::getInstance()->findById($playerId);
		$arr = PlayerManager::getInstance()->loadData(array($curPlayerArray));
		$curPlayer = $arr[0];
		$isSalaryAgreed = FALSE;
		
		$expectedSalary = $curPlayer->getExpectedSalary($nowDate);
		if ($newSalary >= $expectedSalary) //player salary同意
		{
			$contractRemainMonth = $curPlayer->getContractRemainMonth($nowDate); //until contract_end month
		
			if ( ($curPlayer->team_id == 0) || ($contractRemainMonth <= 6) )//free transfer
			{
				$isSalaryAgreed = TRUE;
				NewsManager::getInstance()->add("买进{$curPlayer->name}成功", $buyTeamId, $nowDate, '', 0);
			}
			else
			{
				$expectedValue = $curPlayer->estimateFee($nowDate);
				$wave = mt_rand(1, 10);
				if ($newPrice >= $expectedValue - $expectedSalary*$wave/100)
				{
					$isSalaryAgreed = TRUE;
				}
				else
				{
					$isSalaryAgreed = FALSE;
					NewsManager::getInstance()->add('fee error, expected ' . $expectedValue, $buyTeamId, $nowDate, '', 0);
				}
			}
		}
		else //salary不满
		{
			NewsManager::getInstance()->add('salaray error, expected ' . $expectedSalary, $buyTeamId, $nowDate, '', 0);
		}
		
		if ($isSalaryAgreed)
		{
			//reset total salary
			if ($curPlayer->team_id)
			{
				$sellTeam = TeamManager::getInstance()->findById($curPlayer->team_id);
				$sellTeam['money'] += $newPrice;
				$sellTeam['TotalSalary'] -= $newSalary;
				$sellTeam['player_count'] -= 1;
				TeamManager::getInstance()->update(array('money'=>$sellTeam['money'], 'TotalSalary'=>$sellTeam['TotalSalary'], 'player_count'=>$sellTeam['player_count']), array('id'=>$curPlayer->team_id));
			}
			
			TeamManager::getInstance()->changeMoney($buyTeamId, 2, $newPrice, $nowDate, "买进球员{$curPlayer->name}");
			
			$buyTeam = TeamManager::getInstance()->findById($buyTeamId);
			$buyTeam['TotalSalary'] += $newSalary;
			$buyTeam['player_count'] += 1;
			TeamManager::getInstance()->update(array('money'=>$buyTeam['money'], 'TotalSalary'=>$buyTeam['TotalSalary'], 'player_count'=>$buyTeam['player_count']), array('id'=>$buyTeamId));
			
			$curPlayer->team_id = $buyTeamId;
			$curPlayer->salary = $newSalary;
			$curPlayer->ContractBegin = $nowDate;
			$curPlayer->ClubDepending = 80;
			$curPlayer->setBestShirtNo(PlayerManager::getInstance()->getExistNos());
			
			$d1 = new DateTime($nowDate);
			$d1->add(new DateInterval('P' . $addMonthCount . 'M'));
			
			$curPlayer->ContractEnd = $d1->date;
			PlayerManager::getInstance()->save($curPlayer, 'update');
		}
		else
		{
//			echo 'failed';
		}
		
		header("location:".\MainConfig::BASE_URL.'ypn/new_day');
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
		$curPlayer = PlayerManager::getInstance()->findById($id);
		print_r($curPlayer);exit;
	}
}