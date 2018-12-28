<?php
namespace Model\Core;

class Team extends YpnModel
{
    public $id;
    
    public function getId() {
        return $this->id;
    }

    public function getMoney() {
        return $this->money;
    }

    public function getName() {
        return $this->name;
    }

    public function getFormattion() {
        return $this->formattion;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setMoney($money) {
        $this->money = $money;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setFormattion($formattion) {
        $this->formattion = $formattion;
    }
    public function getLeagueId() {
        return $this->league_id;
    }

    public function getManagerId() {
        return $this->managerId;
    }

    public function setLeagueId($leagueId) {
        $this->league_id = $leagueId;
    }

    public function setManagerId($managerId) {
        $this->manager_id = $managerId;
    }
    
    public function getPlayerCount() {
        return $this->player_count;
    }

    public function setPlayerCount($playerCount) {
        $this->player_count = $playerCount;
    }
    
    public function getPositionInfo() {
        return $this->positionInfo;
    }

    public function setPositionInfo(array $positionInfo) {
        $this->positionInfo = $positionInfo;
    }

    public function addMoney($num, $info, $nowDate)
    {
        $this->money += $num;
        
        $this->addBill(array('info'=> $info, 'money'=>$num, 'PubTime'=>strtotime($nowDate), 'remain'=>$this->money));
    }
    
    public function paySalary($nowDate)
    {
        $this->addMoney(-($this->total_salary), 'pay salary', $nowDate);
    }
    
    private function addBill($data)
    {
        if ($this->bills != null)
        {
            $bills = json_decode($this->bills, true);
        }
        else
        {
            $bills = array();
        }
        $bills[] = $data;
        
        $this->bills = json_encode($bills);
    }
    
    public function getRndName()
    {
        if ($this->alias == "")
        {
            return $this->name;
        }
        else
        {
            return mt_rand(0, 1)?$this->name:$this->alias;
        }
    }
	
	public function getNeedPoses()
	{
		$needPoses = array(
//            array('positionId'=>4, 'minCount'=>3),
			4 => 3,
            3 => 2,
            9 => 2,
            10 => 2,
            13 => 2,
            14 => 2,
            2 => 2,
        );

        switch ($this->formattion) 
        {
            case "4-4-2":
                $needPoses[1] = 4;
                $needPoses[8] = 2;
                $needPoses[3] = 2;
                break;
            case "3-5-2":
                $needPoses[2] = 2;
                $needPoses[8] = 2;
                $needPoses[1] = 4;
                break;
            case "5-3-2":
                $needPoses[3] = 4;
                $needPoses[1] = 4;
                break;
            case "3-4-3":
                $needPoses[2] = 2;
                $needPoses[5] = 2;
                $needPoses[6] = 2;
                $needPoses[7] = 2;
                break;
            case "4-3-3":
                $needPoses[3] = 2;
                $needPoses[5] = 2;
                $needPoses[6] = 2;
                $needPoses[7] = 2;
                break;
            case "4-5-1":
                $needPoses[3] = 2;
                $needPoses[7] = 2;
                $needPoses[2] = 2;
                $needPoses[8] = 2;
                break;
            case "圣诞树":
                $needPoses[3] = 2;
                $needPoses[8] = 4;
                $needPoses[7] = 2;
                break;
        }
		return $needPoses;
	}
}
