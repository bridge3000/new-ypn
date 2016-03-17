<?php
namespace Model\Core;

class Team 
{
    public $id;
    public $money;
    public $name;
    public $formattion;
    public $league_id;
    public $managerId;
    public $playerCount;
    private $positionInfo;
    private $bill;
    
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
        return $this->playerCount;
    }

    public function setPlayerCount($playerCount) {
        $this->playerCount = $playerCount;
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
        
        $this->addBill(array('info'=>$info, 'money'=>$num, 'PubTime'=>$nowDate, 'remain'=>$this->money));
    }
    
    public function paySalary($nowDate)
    {
        $this->addMoney(-($this->TotalSalary), 'pay salary', $nowDate);
    }
    
    public function addBill($data)
    {
        if ($this->bill != null)
        {
            $bill = json_decode($this->bill, true);
        }
        else
        {
            $bill = array();
        }
        $bill[] = $data;
        
        $this->bill = json_encode($bill);
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
    
}
