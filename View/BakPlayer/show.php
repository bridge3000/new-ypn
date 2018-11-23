<?php
$myLeagueTeams = array();

if (array_key_exists($curCollectPlayer['league_id'], $teams))
{
    foreach($teams[$curCollectPlayer['league_id']] as $team)
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
<div style="text-align: center;"><a href="index.php?c=BakTeam&a=show&p=<?php echo $curCollectPlayer['team_id'] ?>">back team</a></div>
<form method="post" action="index.php?c=BakPlayer&a=save">
<input type="hidden" name="id" value="<?php echo $curCollectPlayer['id'] ?>" />
<table class="tb_style_1">
    <tr><th>shirt no</th><td><input type="text" name="ShirtNo" value="<?php echo $curCollectPlayer['ShirtNo'] ?>" /></td></tr>
    <tr><th>name</th><td><input type="text" name="name" value="<?php echo $curCollectPlayer['name'] ?>" x-webkit-speech="" /></td></tr>
    <tr><th>country</th><td><input type="text" name="country" value="<?php echo $curCollectPlayer['country'] ?>" x-webkit-speech="" /></td></tr>
    <tr><th>league_id</th><td><?php Util\FormHelper::select(array('id'=>'league_id', 'name'=>'league_id', 'onchange'=>'changeTeams()'), $leagues, $curCollectPlayer['league_id']) ?></td></tr>
    <tr><th>team_id</th><td><?php Util\FormHelper::select(array('id'=>'team_id', 'name'=>'team_id'), $myLeagueTeams, $curCollectPlayer['team_id']) ?></td></tr>  
    <tr><th>birthday</th><td><input type="text" name="birthday" value="<?php echo $curCollectPlayer['birthday'] ?>" /></td></tr>
    <tr><th>condition_id</th><td><?php Util\FormHelper::select(array('id'=>'condition_id', 'name'=>'condition_id'), MainConfig::$conditions, $curCollectPlayer['condition_id']) ?></td></tr>
    <tr><th>position_id</th><td><?php Util\FormHelper::select(array('id'=>'position_id', 'name'=>'position_id'), MainConfig::$positions, $curCollectPlayer['position_id']) ?></td></tr>
    <tr><th>height</th><td><input type="text" name="height" value="<?php echo $curCollectPlayer['height'] ?>" /></td></tr>
    <tr><th>weight</th><td><input type="text" name="weight" value="<?php echo $curCollectPlayer['weight'] ?>" /></td></tr>
    <tr><th>fee</th><td><input type="text" name="fee" value="<?php echo $curCollectPlayer['fee'] ?>" /></td></tr>
    <tr><th>LeftProperties</th><td><input type="text" name="LeftProperties" value="<?php echo $curCollectPlayer['LeftProperties'] ?>" /></td></tr>
    <tr><th>MidProperties</th><td><input type="text" name="MidProperties" value="<?php echo $curCollectPlayer['MidProperties'] ?>" /></td></tr>
    <tr><th>RightProperties</th><td><input type="text" name="RightProperties" value="<?php echo $curCollectPlayer['RightProperties'] ?>" /></td></tr>
    <tr><th>ShotPower</th><td><input type="text" name="ShotPower" value="<?php echo $curCollectPlayer['ShotPower'] ?>" /></td></tr>
    <tr><th>ShotAccurate</th><td><input type="text" name="ShotAccurate" value="<?php echo $curCollectPlayer['ShotAccurate'] ?>" /></td></tr>
    <tr><th>header</th><td><input type="text" name="header" value="<?php echo $curCollectPlayer['header'] ?>" /></td></tr>
    <tr><th>InjuredDay</th><td><input type="text" name="InjuredDay" value="<?php echo $curCollectPlayer['InjuredDay'] ?>" /></td></tr>
    <tr><th>tackle</th><td><input type="text" name="tackle" value="<?php echo $curCollectPlayer['tackle'] ?>" /></td></tr>
    <tr><th>creativation</th><td><input type="text" name="creativation" value="<?php echo $curCollectPlayer['creativation'] ?>" /></td></tr>
    <tr><th>BallControl</th><td><input type="text" name="BallControl" value="<?php echo $curCollectPlayer['BallControl'] ?>" /></td></tr>
    <tr><th>speed</th><td><input type="text" name="speed" value="<?php echo $curCollectPlayer['speed'] ?>" /></td></tr>
    <tr><th>salary</th><td><input type="text" name="salary" value="<?php echo $curCollectPlayer['salary'] ?>" /></td></tr>
    <tr><th>ShotDesire</th><td><input type="text" name="ShotDesire" value="<?php echo $curCollectPlayer['ShotDesire'] ?>" /></td></tr>
    <tr><th>state</th><td><input type="text" name="state" value="<?php echo $curCollectPlayer['state'] ?>" /></td></tr>
    <tr><th>agility</th><td><input type="text" name="agility" value="<?php echo $curCollectPlayer['agility'] ?>" /></td></tr>
    <tr><th>pass</th><td><input type="text" name="pass" value="<?php echo $curCollectPlayer['pass'] ?>" /></td></tr>
    <tr><th>qiangdian</th><td><input type="text" name="qiangdian" value="<?php echo $curCollectPlayer['qiangdian'] ?>" /></td></tr>
    <tr><th>pinqiang</th><td><input type="text" name="pinqiang" value="<?php echo $curCollectPlayer['pinqiang'] ?>" /></td></tr>
    <tr><th>arc</th><td><input type="text" name="arc" value="<?php echo $curCollectPlayer['arc'] ?>" /></td></tr>
    <tr><th>scope</th><td><input type="text" name="scope" value="<?php echo $curCollectPlayer['scope'] ?>" /></td></tr>
    <tr><th>beat</th><td><input type="text" name="beat" value="<?php echo $curCollectPlayer['beat'] ?>" /></td></tr>
    <tr><th>close_marking</th><td><input type="text" name="close_marking" value="<?php echo $curCollectPlayer['close_marking'] ?>" /></td></tr>
    <tr><th>SinewMax</th><td><input type="text" name="SinewMax" value="<?php echo $curCollectPlayer['SinewMax'] ?>" /></td></tr>
    <tr><th>loyalty</th><td><input type="text" name="loyalty" value="<?php echo $curCollectPlayer['loyalty'] ?>" /></td></tr>
    <tr><th>popular</th><td><input type="text" name="popular" value="<?php echo $curCollectPlayer['popular'] ?>" /></td></tr>
    <tr><th>ImgSrc</th><td><input type="text" name="ImgSrc" value="<?php echo $curCollectPlayer['ImgSrc'] ?>" /></td></tr>
    <tr><th>ContractBegin</th><td><input type="text" name="ContractBegin" value="<?php echo $curCollectPlayer['ContractBegin'] ?>" /></td></tr>
    <tr><th>ContractEnd</th><td><input type="text" name="ContractEnd" value="<?php echo $curCollectPlayer['ContractEnd'] ?>" /></td></tr>
    <tr><th>training_id</th><td><input type="text" name="training_id" value="<?php echo $curCollectPlayer['training_id'] ?>" /></td></tr>
    <tr><th>moral</th><td><input type="text" name="moral" value="<?php echo $curCollectPlayer['moral'] ?>" /></td></tr>
    <tr><th>temper</th><td><input type="text" name="temper" value="<?php echo $curCollectPlayer['temper'] ?>" /></td></tr>
    <tr><th>mind</th><td><input type="text" name="mind" value="<?php echo $curCollectPlayer['mind'] ?>" /></td></tr>
    <tr><th>CornerPosition_id</th><td><input type="text" name="CornerPosition_id" value="<?php echo $curCollectPlayer['CornerPosition_id'] ?>" /></td></tr>
    <tr><th>save</th><td><input type="text" name="save" value="<?php echo $curCollectPlayer['save'] ?>" /></td></tr>
    <tr><th>ClubDepending</th><td><input type="text" name="ClubDepending" value="<?php echo $curCollectPlayer['ClubDepending'] ?>" /></td></tr>
    <tr><th>cooperate</th><td><input type="text" name="cooperate" value="<?php echo $curCollectPlayer['cooperate'] ?>" /></td></tr>
    <tr><th></th><th><input type="submit" /></th></tr>
</table>
</form>