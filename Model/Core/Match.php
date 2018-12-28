<?php
namespace Model\Core;

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
}