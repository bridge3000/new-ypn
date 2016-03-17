<?php
namespace Controller;
use \MainConfig;
use Model\Manager\PlayerManager;
use Model\Manager\FutureContractManager;
use Model\Manager\SettingManager;
use Model\Manager\RetiredShirtManager;
use Model\Manager\CoachManager;
use Model\Manager\TeamManager;
use Model\Manager\MatchManager;

class PlayerController extends AppController
{
    public $name = 'Player';
    
    public function transfer_free_agent()
    {
        $nowDate = SettingManager::getInstance()->getNowDate();
        PlayerManager::getInstance()->query("update ypn_players set team_id=0,ContractBegin=null,ContractEnd=null where ContractEnd<'" . $nowDate . "'");
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
            
            $targetPlayer->ShirtNo = PlayerManager::getInstance()->getPlayerNewShirtNo($targetPlayer, array_merge($retiredShirts, $usedNOs));

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
        $myCoash = CoachManager::getInstance()->getMyCoach();
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
		$fields = array('id', 'name', 'team_id', 'loyalty');
		$birthdayPlayers = PlayerManager::getInstance()->find('all', compact('conditions', 'contain', 'fields'));
        $myCoach = CoachManager::getInstance()->getMyCoach();
        $myTeamId = $myCoach->team_id;

		for ($i = 0;$i < count($birthdayPlayers);$i++)
		{
			if ($birthdayPlayers[$i]['team_id'] == $myTeamId)
			{
				echo ("<script>window.showModalDialog('/ypn/players/pay_birthday/" . $birthdayPlayers[$i]['id'] . "','','dialogHeight:400px;dialogWidth:400px;dialogLeft:300px;dialogTop:300px;');</script>");flush();
			}
			else
			{
				if (mt_rand(1, 2) === 1)
				{
//					TeamManager::getInstance()->writeJournal($birthdayPlayers[$i]['team_id'], 2, 1, '给' . $birthdayPlayers[$i]['name'] . '发生日补助');
//					$birthdayPlayers[$i]['loyalty'] += 1;
//					PlayerManager::getInstance()->save($birthdayPlayers[$i]);
				}
				
			}
		}
		unset($birthdayPlayers);
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
				echo("<font color=blue><strong>" . $curPlayer->name . "</strong></font>被卖出了<br>");flush();
				
				/*卖出*/
				$this->query("update ypn_players set isSelling=1, fee=" . $curPlayer->estimateFee($nowDate) * $curPlayer->ClubDepending / 100 . " where id=" . $curPlayer->id);
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
				
				$this->query("update ypn_players set ContractBegin='" . $nowDate . "', ContractEnd='" . $contractEnd . "', salary=" . $newSalary . " where id=" . $curPlayer->id);
			}
		}
	}
    
    /**
     *
     * @param type $type 1common 2free 3all
     */
    public function buy_list($type)
    {
        $conditions = array();
        
        switch ($type) 
        {
            case 1:
                $conditions['isSelling'] = 1;
                break;
            case 2:
                $conditions['team_id'] = 0;
                break;
        }
        
        $players = PlayerManager::getInstance()->find('all', array(
            'conditions' => array(),
            'fields' => array('id', 'name', 'team_id', 'position_id', 'fee', 'salary', 'popular'),
            'order' => array('fee'=>'desc', 'salary'=>'desc','popular'=>'desc'),
            'limit' => 20
        ));
        
        $teamList = TeamManager::getInstance()->find('list', array(
            'conditions' => array('league_id<>'=>100),
            'fields' => array('id', 'name')
        ));
        
        $this->set('players', $players);
        $this->set('teamList', $teamList);
        $this->render('buy_list');
    }
    
    public function chuchang($group_id = 0) 
	{
		$this->layout = 'main';
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
		$this->set('cornerpositions', \MainConfig::$cornerPositions);
		$this->set('fieldPunish', $fieldPunish);
		$this->set('playergroups', array());
        
        $this->render('chuchang');
	}
    
    
}
?>