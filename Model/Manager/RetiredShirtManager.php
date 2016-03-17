<?php
namespace Model\Manager;

class RetiredShirtManager extends DataManager
{
    public $table = "retired_shirts";
    
    public function getAll()
    {
        
    }
    
    public function getByTeamId($teamId)
    {
        $data = $this->find('all', array(
            'conditions' => array('team_id'=>$teamId),
            'fields' => array('shirt')
        ));
        
        $nos = array();
        foreach($data as $d)
        {
            $nos[] = $d['shirt'];
        }
        
        return $nos;
    }
}

?>