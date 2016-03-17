<?php
$myLeagueTeams = array();

if (array_key_exists($curPlayer['league_id'], $teams))
{
    foreach($teams[$curPlayer['league_id']] as $team)
    {
        $myLeagueTeams[$team['id']] = $team['name'];
    }
}
?>
<script>
var teams = <?php echo json_encode($teams) ?>;
var teamId;
    
function changeTeams()
{
    teamId.options.length = 0;
    
    for(var i in teams[$("#league_id").val()])
    {
        var varItem = new Option(teams[$("#league_id").val()][i].name, teams[$("#league_id").val()][i].id);      
        teamId.options.add(varItem);     
    }
    teamId.options.add(new Option('free agent', '0'));
}

$(document).ready(function(){
    teamId = document.getElementById("team_id");
    var varItem = new Option('free agent', '0');      
    teamId.options.add(varItem);
});
</script>
<div style="text-align: center;"><a href="index.php?c=BakTeam&a=show&p=<?php echo $curPlayer['team_id'] ?>">back team</a></div>
<form method="post" action="index.php?c=BakPlayer&a=save">
<input type="hidden" name="id" value="<?php echo $curPlayer['id'] ?>" />
<table class="tb_style_1">
    <tr><th>shirt no</th><td><input type="text" name="ShirtNo" value="<?php echo $curPlayer['ShirtNo'] ?>" /></td></tr>
    <tr><th>name</th><td><input type="text" name="name" value="<?php echo $curPlayer['name'] ?>" x-webkit-speech="" /></td></tr>
    <tr><th>country</th><td><input type="text" name="country" value="<?php echo $curPlayer['country'] ?>" x-webkit-speech="" /></td></tr>
    <tr><th>league_id</th><td><?php Util\FormHelper::select(array('id'=>'league_id', 'name'=>'league_id', 'onchange'=>'changeTeams()'), $leagues, $curPlayer['league_id']) ?></td></tr>
    <tr><th>team_id</th><td><?php Util\FormHelper::select(array('id'=>'team_id', 'name'=>'team_id'), $myLeagueTeams, $curPlayer['team_id']) ?></td></tr>  
    <tr><th>birthday</th><td><input type="text" name="birthday" value="<?php echo $curPlayer['birthday'] ?>" /></td></tr>
    <tr><th>condition_id</th><td><?php Util\FormHelper::select(array('id'=>'condition_id', 'name'=>'condition_id'), MainConfig::$conditions, $curPlayer['condition_id']) ?></td></tr>
    <tr><th>position_id</th><td><?php Util\FormHelper::select(array('id'=>'position_id', 'name'=>'position_id'), MainConfig::$positions, $curPlayer['position_id']) ?></td></tr>
    <tr><th>height</th><td><input type="text" name="height" value="<?php echo $curPlayer['height'] ?>" /></td></tr>
    <tr><th>weight</th><td><input type="text" name="weight" value="<?php echo $curPlayer['weight'] ?>" /></td></tr>
    <tr><th>fee</th><td><input type="text" name="fee" value="<?php echo $curPlayer['fee'] ?>" /></td></tr>
    <tr><th>LeftProperties</th><td><input type="text" name="LeftProperties" value="<?php echo $curPlayer['LeftProperties'] ?>" /></td></tr>
    <tr><th>MidProperties</th><td><input type="text" name="MidProperties" value="<?php echo $curPlayer['MidProperties'] ?>" /></td></tr>
    <tr><th>RightProperties</th><td><input type="text" name="RightProperties" value="<?php echo $curPlayer['RightProperties'] ?>" /></td></tr>
    <tr><th>ShotPower</th><td><input type="text" name="ShotPower" value="<?php echo $curPlayer['ShotPower'] ?>" /></td></tr>
    <tr><th>ShotAccurate</th><td><input type="text" name="ShotAccurate" value="<?php echo $curPlayer['ShotAccurate'] ?>" /></td></tr>
    <tr><th>header</th><td><input type="text" name="header" value="<?php echo $curPlayer['header'] ?>" /></td></tr>
    <tr><th>InjuredDay</th><td><input type="text" name="InjuredDay" value="<?php echo $curPlayer['InjuredDay'] ?>" /></td></tr>
    <tr><th>tackle</th><td><input type="text" name="tackle" value="<?php echo $curPlayer['tackle'] ?>" /></td></tr>
    <tr><th>creativation</th><td><input type="text" name="creativation" value="<?php echo $curPlayer['creativation'] ?>" /></td></tr>
    <tr><th>BallControl</th><td><input type="text" name="BallControl" value="<?php echo $curPlayer['BallControl'] ?>" /></td></tr>
    <tr><th>speed</th><td><input type="text" name="speed" value="<?php echo $curPlayer['speed'] ?>" /></td></tr>
    <tr><th>salary</th><td><input type="text" name="salary" value="<?php echo $curPlayer['salary'] ?>" /></td></tr>
    <tr><th>ShotDesire</th><td><input type="text" name="ShotDesire" value="<?php echo $curPlayer['ShotDesire'] ?>" /></td></tr>
    <tr><th>state</th><td><input type="text" name="state" value="<?php echo $curPlayer['state'] ?>" /></td></tr>
    <tr><th>agility</th><td><input type="text" name="agility" value="<?php echo $curPlayer['agility'] ?>" /></td></tr>
    <tr><th>pass</th><td><input type="text" name="pass" value="<?php echo $curPlayer['pass'] ?>" /></td></tr>
    <tr><th>qiangdian</th><td><input type="text" name="qiangdian" value="<?php echo $curPlayer['qiangdian'] ?>" /></td></tr>
    <tr><th>pinqiang</th><td><input type="text" name="pinqiang" value="<?php echo $curPlayer['pinqiang'] ?>" /></td></tr>
    <tr><th>arc</th><td><input type="text" name="arc" value="<?php echo $curPlayer['arc'] ?>" /></td></tr>
    <tr><th>scope</th><td><input type="text" name="scope" value="<?php echo $curPlayer['scope'] ?>" /></td></tr>
    <tr><th>beat</th><td><input type="text" name="beat" value="<?php echo $curPlayer['beat'] ?>" /></td></tr>
    <tr><th>close_marking</th><td><input type="text" name="close_marking" value="<?php echo $curPlayer['close_marking'] ?>" /></td></tr>
    <tr><th>SinewMax</th><td><input type="text" name="SinewMax" value="<?php echo $curPlayer['SinewMax'] ?>" /></td></tr>
    <tr><th>loyalty</th><td><input type="text" name="loyalty" value="<?php echo $curPlayer['loyalty'] ?>" /></td></tr>
    <tr><th>popular</th><td><input type="text" name="popular" value="<?php echo $curPlayer['popular'] ?>" /></td></tr>
    <tr><th>ImgSrc</th><td><input type="text" name="ImgSrc" value="<?php echo $curPlayer['ImgSrc'] ?>" /></td></tr>
    <tr><th>ContractBegin</th><td><input type="text" name="ContractBegin" value="<?php echo $curPlayer['ContractBegin'] ?>" /></td></tr>
    <tr><th>ContractEnd</th><td><input type="text" name="ContractEnd" value="<?php echo $curPlayer['ContractEnd'] ?>" /></td></tr>
    <tr><th>training_id</th><td><input type="text" name="training_id" value="<?php echo $curPlayer['training_id'] ?>" /></td></tr>
    <tr><th>moral</th><td><input type="text" name="moral" value="<?php echo $curPlayer['moral'] ?>" /></td></tr>
    <tr><th>temper</th><td><input type="text" name="temper" value="<?php echo $curPlayer['temper'] ?>" /></td></tr>
    <tr><th>mind</th><td><input type="text" name="mind" value="<?php echo $curPlayer['mind'] ?>" /></td></tr>
    <tr><th>CornerPosition_id</th><td><input type="text" name="CornerPosition_id" value="<?php echo $curPlayer['CornerPosition_id'] ?>" /></td></tr>
    <tr><th>save</th><td><input type="text" name="save" value="<?php echo $curPlayer['save'] ?>" /></td></tr>
    <tr><th>ClubDepending</th><td><input type="text" name="ClubDepending" value="<?php echo $curPlayer['ClubDepending'] ?>" /></td></tr>
    <tr><th>cooperate</th><td><input type="text" name="cooperate" value="<?php echo $curPlayer['cooperate'] ?>" /></td></tr>
    <tr><th></th><th><input type="submit" /></th></tr>
</table>
</form>