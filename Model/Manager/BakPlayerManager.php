<?php
namespace Model\Manager;

class BakPlayerManager extends DataManager
{
    public $table = 'bak_players';
    
    public function search($name, $birthdate)
    {
        $conditions = array();
        
        if ($name != '')
        {
            $conditions['name like '] = "%$name%";
        }
        
        if ($birthdate != '')
        {
            $conditions['birthday'] = $birthdate;
        }
        
        $data = $this->find('all', array(
            'conditions' => $conditions,
            'fields' => array('id', 'name', 'team_id', 'birthday', 'country')
        ));
        
        return $data;
    }
}

?>
