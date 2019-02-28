<?php
namespace Model\Core;

use Model\Collection\PlayerCollection;

class Match extends YpnModel
{
	protected $table = 'matches';
    private $faqiuquan = 1;
	static $cornerPositions = array(
        1 => '前点',
        2 => '中点',
        3 => '后点',
        4 => '禁区外'
    );
    
    public function turnFaqiuquan()
    {
        $this->faqiuquan = !$this->faqiuquan;
    }
    
    public function getFaqiuquan()
    {
        return $this->faqiuquan;
    }
    
    public function saveGoal()
    {
        if ($this->getFaqiuquan())
        {
            $this->HostGoals++;
        }
        else
        {
            $this->GuestGoals++;
        }
    }
    
    public function getMatchField()
	{
		switch ($this->class_id)
		{
			case 1:
			case 31:
				$data['fieldRedCard'] = "RedCard1Count";
				$data['fieldYellowCard'] = "YellowCard1Count";
				$data['fieldPunish'] = "Punish1Count";
				$data['fieldTackle'] = "Tackle1Count";
				break;
			case 3:
			case 4:
			case 5:
			case 6:
			case 7:
			case 12:
			case 13:
			case 14:
			case 15:
			case 16:
			case 17:
			case 23:
				$data['fieldRedCard'] = "RedCard3Count";
				$data['fieldYellowCard'] = "YellowCard3Count";
				$data['fieldPunish'] = "Punish3Count";
				$data['fieldTackle'] = "Tackle3Count";
				break;
			default:
				$data['fieldRedCard'] = "RedCard2Count";
				$data['fieldYellowCard'] = "YellowCard2Count";
				$data['fieldPunish'] = "Punish2Count";
				$data['fieldTackle'] = "Tackle2Count";
				break;
		}	

		return $data;
	}
	
	public static function create($hostTeam_id, $guestTeam_id, $nowDate, $classId, $isHostPark)
	{
        $newMatch = new static();
        $newMatch->HostTeam_id = $hostTeam_id;
		$newMatch->GuestTeam_id = $guestTeam_id;
		$newMatch->PlayTime = $nowDate;
		$newMatch->class_id = $classId;
		$newMatch->is_host_park = $isHostPark;
		$newMatch->save();
	}
	
	public function save()
	{
		unset($this->hostTeam);
		unset($this->guestTeam);
		unset($this->hostPlayers);
		unset($this->guestPlayers);
		unset($this->hostShoufaCollection);
		unset($this->hostBandengCollection);
		unset($this->guestShoufaCollection);
		unset($this->guestBandengCollection);
			
		parent::save();
	}
	
	public function getPunishFieldByMatchClassId($matchClassId)
	{
		$punishField = '';
		switch ($matchClassId)
        {
            case 1:
            case 31:
                $punishField = "Punish1Count";
                break;
            case 2:
            case 8:
            case 9:
            case 10:
                $punishField = "Punish2Count";
                break;
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 12:
            case 13:
            case 14:
            case 15:
            case 16:
            case 17:
            case 23:
                $punishField = "Punish3Count";
                break;
            default:
            	$punishField = "Punish2Count";
                break;
        }
		return $punishField;
	}
	
	public function setShoufa($players, $isHostTeam)
    {
		$matchClassId = $this->class_id;
		$punishField = $this->getPunishFieldByMatchClassId($matchClassId);
		
		$curTeam = $isHostTeam ? $this->hostTeam : $this->guestTeam;
		
		$playerCollection = new PlayerCollection($players);
		
		if($curTeam->is_auto_format)
		{
			$playerCollection->autoSetShoufa($matchClassId, $curTeam->formattion, $punishField);
		}
		
		/*ClubDepending 首发+1,场外-1*/
		foreach($playerCollection as $curPlayer)
		{
			if ($curPlayer->condition_id == 1)
			{
				if ($curPlayer->ClubDepending < 100)
				{
					$curPlayer->ClubDepending += 1;
				}
				
				if ($curPlayer->loyalty < 100)
				{
					$curPlayer->loyalty += 1;
				}
			}
			
			if ( ($curPlayer->condition_id == 3) && ($curPlayer->ClubDepending > 30) && ($curPlayer->state > 95) && ($curPlayer->sinew > 78) && ($curPlayer->$punishField == 0) )
			{
				$curPlayer->ClubDepending -= 1;
				$curPlayer->loyalty -= 1;
			}
		}
        
        $matchPlayers = array();
        
		$matchPlayers['bandeng'] = [];
        foreach($players as $player)
        {
			$player->score = 0;
			$player->yellow_today = 0;
            if ($player->condition_id == 1)
            {
                $matchPlayers['shoufa'][] = $player;
            }
            else if ($player->condition_id == 2)
            {
                $matchPlayers['bandeng'][] = $player;
            }
			
			/*主队加成*/
			if($isHostTeam && $this->is_host_park) 
			{
				$player->state += 5;
			}
        }
		
		if($isHostTeam) //是主队
		{
//			$this->hostPlayers = $matchPlayers; //数组是为了兼容老代码，以后都用集合
			$this->hostShoufaCollection = new PlayerCollection($matchPlayers['shoufa']);
			$this->hostBandengCollection = new PlayerCollection($matchPlayers['bandeng']);
		}
		else //是客队
		{
//			$this->guestPlayers = $matchPlayers;
			$this->guestShoufaCollection = new PlayerCollection($matchPlayers['shoufa']);
			$this->guestBandengCollection = new PlayerCollection($matchPlayers['bandeng']);
		}
    }
}