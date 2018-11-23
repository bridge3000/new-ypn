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

function changePro()
{
	switch ($("#position_id").val())
	{
	case "1":
		$("#MidProperties").val('100');
		$("#RightProperties").val('90');
		$("#ShotPower").val('83');
		$("#ShotAccurate").val('78');
		$("#ShotDesire").val('83');
		$("#header").val('78');
		$("#qiangdian").val('78');
		$("#speed").val('82');
		changeTraining(1);
		break;
	case "2":
		$("#tackle").val('76');
		$("#pinqiang").val('78');
		$("#SinewMax").val('86');
		changeTraining(3);
		break;
	case "3":
		$("#height").val('185');
		$("#weight").val('80');
		$("#qiangdian").val('77');
		$("#tackle").val('77');		
		$("#close_marking").val('77');
		changeTraining(3);
		break;
	case "4":
		$("#ShotDesire").val('30');
		$("#save").val('78');
		$("#height").val('185');
		$("#weight").val('80');
		changeTraining(7);
		break;
	case "5":
		$("#LeftProperties").val('100');
		$("#MidProperties").val('95');
		$("#RightProperties").val('90');
		$("#ShotPower").val('83');
		$("#ShotAccurate").val('78');
		$("#ShotDesire").val('81');
		$("#speed").val('83');
		$("#beat").val('78');
		changeTraining(6);
		break;
	case "6":
		$("#LeftProperties").val('90');
		$("#MidProperties").val('95');
		$("#RightProperties").val('100');
		$("#ShotPower").val('83');
		$("#ShotAccurate").val('78');
		$("#ShotDesire").val('81');
		$("#speed").val('83');
		$("#beat").val('78');
		changeTraining(6);
		break;	
	case "7":
		$("#ShotPower").val('83');
		$("#ShotAccurate").val('78');
		$("#ShotDesire").val('82');
		$("#header").val('78');
		$("#qiangdian").val('78');
		$("#agility").val('78');
		$("#height").val('185');
		$("#weight").val('80');
		$("#BallControl").val('80');
		changeTraining(4);
		break;
	case "8":
		$("#ShotPower").val('83');
		$("#ShotAccurate").val('78');
		$("#ShotDesire").val('78');
		$("#pass").val('80');
		$("#BallControl").val('80');
		changeTraining(2);
		break;
	case "9":
		$("#LeftProperties").val('100');
		$("#MidProperties").val('90');
		$("#RightProperties").val('90');
		$("#beat").val('78');
		$("#speed").val('82');
		changeTraining(6);
		break;
	case "10":
		$("#LeftProperties").val('90');
		$("#MidProperties").val('90');
		$("#RightProperties").val('100');
		$("#beat").val('78');
		$("#speed").val('82');
		changeTraining(6);
		break;
	case "13":
		$("#LeftProperties").val('100');
		$("#MidProperties").val('90');
		$("#RightProperties").val('90');
		$("#tackle").val('77');
		$("#close_marking").val('77');
		$("#speed").val('82');
		changeTraining(3);
		break;
	case "14":
		$("#LeftProperties").val('90');
		$("#MidProperties").val('90');
		$("#RightProperties").val('100');
		$("#tackle").val('77');
		$("#close_marking").val('77');
		$("#speed").val('82');
		changeTraining(3);
		break;
	}
}

function changeTraining(training_id)
{
	var BakplayerTrainingId = document.getElementById("training_id");

	for(var i = 0; i < BakplayerTrainingId.length; i++)
	{
		if (BakplayerTrainingId.options[i].value == training_id) 
		{
			BakplayerTrainingId.options[i].selected = true;
			break;
		}
	}
}

$(document).ready(function(){
    teamId = document.getElementById("team_id");
    changeTeams();
});
</script>

<form method="post" action="index.php?c=BakPlayer&a=save">
<table class="tb_style_1">
    <tr><th>shirt no</th><td><input type="text" name="ShirtNo" value="40" /></td></tr>
    <tr><th>name</th><td><input type="text" name="name" value="<?php echo $curCollectPlayer['name'] ?>" x-webkit-speech="" /></td></tr>
    <tr><th>country</th><td><input type="text" name="country" value="意大利" x-webkit-speech="" /></td></tr>
    <tr><th>league_id</th><td><?php Util\FormHelper::select(array('id'=>'league_id', 'name'=>'league_id', 'onchange'=>'changeTeams()'), $leagues, $curCollectPlayer['league_id']) ?></td></tr>
    <tr><th>team_id</th><td><?php Util\FormHelper::select(array('id'=>'team_id', 'name'=>'team_id'), $myLeagueTeams, $curCollectPlayer['team_id']) ?></td></tr>  
    <tr><th>birthday</th><td><input type="text" name="birthday" value="1988-3-11" /></td></tr>
    <tr><th>condition_id</th><td><?php Util\FormHelper::select(array('id'=>'condition_id', 'name'=>'condition_id'), MainConfig::$conditions, 3) ?></td></tr>
    <tr><th>position_id</th><td><?php Util\FormHelper::select(array('id'=>'position_id', 'name'=>'position_id', 'onchange'=>'changePro()'), MainConfig::$positions, '') ?></td></tr>
    <tr><th>height</th><td><input type="text" id="height" name="height" value="179" /></td></tr>
    <tr><th>weight</th><td><input type="text" id="weight" name="weight" value="73" /></td></tr>
    <tr><th>LeftProperties</th><td><input type="text" id="LeftProperties" name="LeftProperties" value="90" /></td></tr>
    <tr><th>MidProperties</th><td><input type="text" id="MidProperties" name="MidProperties" value="100" /></td></tr>
    <tr><th>RightProperties</th><td><input type="text" id="RightProperties" name="RightProperties" value="90" /></td></tr>
    <tr><th>ShotPower</th><td><input type="text" id="ShotPower" name="ShotPower" value="80" /></td></tr>
    <tr><th>ShotAccurate</th><td><input type="text" id="ShotAccurate" name="ShotAccurate" value="75" /></td></tr>
    <tr><th>ShotDesire</th><td><input type="text" id="ShotDesire" name="ShotDesire" value="75" /></td></tr>
    <tr><th>header</th><td><input type="text" id="header" name="header" value="75" /></td></tr>
    <tr><th>qiangdian</th><td><input type="text" id="qiangdian" name="qiangdian" value="75" /></td></tr>
    <tr><th>InjuredDay</th><td><input type="text" name="InjuredDay" value="0" /></td></tr>
    <tr><th>tackle</th><td><input type="text" id="tackle" name="tackle" value="70" /></td></tr>
    <tr><th>creativation</th><td><input type="text" name="creativation" value="70" /></td></tr>
    <tr><th>BallControl</th><td><input type="text" id="BallControl" name="BallControl" value="75" /></td></tr>
    <tr><th>speed</th><td><input type="text" id="speed" name="speed" value="80" /></td></tr>
    <tr><th>salary</th><td><input type="text" name="salary" value="0.2" /></td></tr>
    <tr><th>state</th><td><input type="text" name="state" value="80" /></td></tr>
    <tr><th>agility</th><td><input type="text" id="agility" name="agility" value="80" /></td></tr>
    <tr><th>pass</th><td><input type="text" id="pass" name="pass" value="75" /></td></tr>
    <tr><th>save</th><td><input type="text" id="save" name="save" value="65" /></td></tr>
    <tr><th>pinqiang</th><td><input type="text" id="pinqiang" name="pinqiang" value="73" /></td></tr>
    <tr><th>arc</th><td><input type="text" id="arc" name="arc" value="73" /></td></tr>
    <tr><th>scope</th><td><input type="text" id="scope" name="scope" value="75" /></td></tr>
    <tr><th>beat</th><td><input type="text" id="beat" name="beat" value="75" /></td></tr>
    <tr><th>close_marking</th><td><input type="text" id="close_marking" name="close_marking" value="75" /></td></tr>
    <tr><th>SinewMax</th><td><input type="text" id="SinewMax" name="SinewMax" value="84" /></td></tr>
    <tr><th>loyalty</th><td><input type="text" name="loyalty" value="75" /></td></tr>
    <tr><th>popular</th><td><input type="text" name="popular" value="60" /></td></tr>
    <tr><th>ImgSrc</th><td><input type="text" name="ImgSrc" value="<?php echo $curCollectPlayer['ImgSrc'] ?>" /></td></tr>
    <tr><th>ContractBegin</th><td><input type="text" name="ContractBegin" value="2012-7-1" /></td></tr>
    <tr><th>ContractEnd</th><td><input type="text" name="ContractEnd" value="2016-6-30" /></td></tr>
    <tr><th>moral</th><td><input type="text" name="moral" value="75" /></td></tr>
    <tr><th>temper</th><td><input type="text" name="temper" value="75" /></td></tr>
    <tr><th>mind</th><td><input type="text" name="mind" value="75" /></td></tr>
    <tr><th>ClubDepending</th><td><input type="text" name="ClubDepending" value="75" /></td></tr>
    <tr><th>cooperate</th><td><input type="text" name="cooperate" value="80" /></td></tr>
    <tr><th>CornerPosition_id</th><td><input type="text" name="CornerPosition_id" value="<?php echo mt_rand(1,4) ?>" /></td></tr>
    <tr><th>training_id</th><td><?php Util\FormHelper::select(array('id'=>'training_id', 'name'=>'training_id'), $trainings, '') ?></td></tr>
    <tr><th></th><th><input type="submit" /></th></tr>
</table>
</form>