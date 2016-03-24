<?php
use Util\FormHelper;
?>
<table class="tb_style_1">
	<tr><th>球 队</th><td><?=  FormHelper::select("guest_team_id", $teamList, "", array('id'=>'guest_team_id'))?></td></tr>
	<tr><th>日 期</th><td><input type="text" id="play_date" value="<?=$nowDate?>"></td></tr>
	<tr><th></th><td><input type="button" id="btnInvite" value="提交"></td></tr>
</table>

<table id="friend_match_table" class="tb_style_1" style="margin-top:20px;">
<?php foreach($friendMatches as $m): ?>
	<tr><td><?=$teamList[$m['HostTeam_id']]?></td><td><?=$teamList[$m['GuestTeam_id']]?></td><td><?=$m['PlayTime']?></td></tr>
<?php endforeach; ?>
</table>

<script>
	var teamList = <?=json_encode($teamList)?>;
	var myTeamId = <?=$myTeamId?>;
	
	$("#btnInvite").click(function(){
		var postData = {
			play_date: $("#play_date").val(),
			guest_team_id: $("#guest_team_id").val()
		};
		
		$.post("<?=  MainConfig::BASE_URL?>match/ajax_invite_friend_match", postData, function(response){
			alert('提交成功');
			
			$("#friend_match_table").append("<tr><td>" + teamList[myTeamId] + "</td><td>" + teamList[postData.guest_team_id] + "</td><td>" + postData.play_date +  "</td></tr>");
		});
	});
	
	var myDate = new Date()
	myDate.setFullYear(<?=date('Y', strtotime($nowDate))?>,<?=(date('m', strtotime($nowDate))-1)?>,<?=date('d', strtotime($nowDate))?>);

	$( "#play_date" ).datepicker({
		defaultDate: myDate,
		dateFormat: "yy-mm-dd"
	});
</script>