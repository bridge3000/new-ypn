<?php
namespace Model\Manager;
use Model\Core\Coach;
use Model\Manager\DataManager;

class YpnManager extends DataManager
{
    //put your code here
    public function resetEuroCup()
    {
        $this->Ypn->query('delete from ypn_euro_cup_groups');
        $allElgroups = $this->Ypn->query('select * from ypn_bak_euro_cup_groups');
        for ($i = 0;$i < count($allElgroups);$i++)
        {
            $this->EuroCupGroup->create();
            $this->data['EuroCupGroup']['GroupName'] = $allElgroups[$i]['bak_euro_cup_groups']['GroupName'];
            $this->data['EuroCupGroup']['team_id'] = $allElgroups[$i]['bak_euro_cup_groups']['team_id'];
            $this->EuroCupGroup->saveModel($this->data);
        }
        unset($allElgroups);
        echo('欧洲杯分组更新完成！<br>');
        flush();
    }
    
    public function resetWorldcup()
    {
		$this->Ypn->query('delete from ypn_worldcup_groups');
        $allElgroups = $this->BakWorldcupGroup->find('all', array('contain'=>array()));
		for ($i = 0;$i < count($allElgroups);$i++)
		{
			$this->WorldcupGroup->create();
			$this->request->data['Worldcupgroup']['GroupName'] = $allElgroups[$i]['BakWorldcupGroup']['GroupName'];
			$this->request->data['Worldcupgroup']['team_id'] = $allElgroups[$i]['BakWorldcupGroup']['team_id'];
			$this->WorldcupGroup->saveModel($this->data);
		}
		unset($allElgroups);
		echo('世界杯分组更新完成！<br>');
		flush();
    }
    
    public function resetPlayerUpload()
    {
		$this->Ypn->query('delete from ypn_player_uploads');
		$bakPlayerUploads = $this->BakPlayerUpload->find('all', array('contain' => array()));
		
		foreach ($bakPlayerUploads as $bpu)
		{
			$this->PlayerUpload->create();
			$data['PlayerUpload'] = $bpu['BakPlayerUpload'];
			$this->PlayerUpload->saveModel($data);			
		}
		echo('国家队上调球员名单更新完成！<br>');
		flush();
    }
    
    public function country2Club()
    {
        $mBakPlayerUpload = $this->getModel("BakPlayerUpload");
        $mBakPlayer = $this->getModel("Bakplayer");
        $countryPlayers = $mBakPlayerUpload->find('all');
        $mBakTeam = $this->getModel('Bakteam');
        $teams = $mBakTeam->find('all', array('contain'=>array()));
        
        foreach($countryPlayers as $cp)
        {
            if (!empty($cp['Bakplayer']))
            {
                $cp['Bakplayer']['team_id'] = $cp['BakPlayerUpload']['ClubTeam_id'];
                $cp['Bakplayer']['ShirtNo'] = $cp['BakPlayerUpload']['ClubShirtNo'];
                
                foreach($teams as $tm)
                {
                    if ($tm['Bakteam']['id'] == $cp['Bakplayer']['team_id'])
                    {
                        $leagueId = $tm['Bakteam']['league_id'];
                        break;
                    }
                }
                
                $cp['Bakplayer']['league_id'] = $leagueId;
                $data['Bakplayer'] = $cp['Bakplayer'];
                if ($data['Bakplayer']['name'] == null)
                {
                    pr($data);
                }
                else
                {
                    $mBakPlayer->saveModel($data);
                }
                
            }
        }
        
        $this->query('delete from ypn_bak_player_uploads');
        
        exit('country to club complete');
    }
    
	public function checkHoliday($nowDate)
	{
		$thisYear = date('Y', strtotime($nowDate));
		
		if ((($nowDate >= $thisYear . "-01-01") && ($nowDate <= $thisYear . "-05-30")) || (($nowDate >= $thisYear . "-08-01") && ($nowDate <= $thisYear . "-12-31")))
		{
			$isHoliday = false;
		}
		else
		{
			$isHoliday = true;
		}
		
		return $isHoliday;
	}
    
    public function checkTransferDay($nowDate)
	{
		$thisYear = date('Y', strtotime($nowDate));
		
		if (($thisYear - 2010) % 4 == 0)/*世界杯年转会日期拖后一些*/
		{
			$summerBegin = $thisYear . "-07-12";
		}
		else if (($thisYear - 2008) % 4 == 0)/*欧洲杯年日期延后*/
		{
			$summerBegin = $thisYear . "-07-03";
		}
		else
		{
			$summerBegin = $thisYear . "-07-01";
		}
		
		if (($nowDate >= $summerBegin && $nowDate <= $thisYear . "-08-31") || ($nowDate >= $thisYear . "-01-01" && $nowDate < $thisYear . "-02-01"))
		{
			$isTransferDay = true;
		}
		else
		{
			$isTransferDay = false;
		}
		
		return $isTransferDay;
	}
    
    public function checkWorldCupDay($nowDate)
	{
		$thisYear = date('Y', strtotime($nowDate));
		
		/*世界杯年转会日期拖后一些*/
		if (($thisYear - 2010) % 4 <> 0)
		{
			$isWorldCupDay = false;
		}
		else
		{
			if ($nowDate >= $thisYear . "-06-10" && $nowDate <= $thisYear . "-07-12")
			{
				$isWorldCupDay = true;
			}
			else
			{
				$isWorldCupDay = false;
			}
		}
		
		return $isWorldCupDay;
	}
	
	function checkEuroCupDay($nowDate)
	{
		$thisYear = date('Y', strtotime($nowDate));
		
		/*世界杯年转会日期拖后一些*/
		if (($thisYear - 2008) % 4 <> 0)
		{
			$isEuroCupDay = false;
		}
		else
		{
			if ($nowDate >= $thisYear . "-06-01" && $nowDate <= $thisYear . "-07-12")
			{
				$isEuroCupDay = true;
			}
			else
			{
				$isEuroCupDay = false;
			}
		}
		
		return $isEuroCupDay;
	}
}

?>
