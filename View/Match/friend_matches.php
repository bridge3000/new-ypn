<?php
use Util\FormHelper;
?>
<table class="tb_style_1">
	<tr><th>球 队</th><td><?=  FormHelper::select("guest_team_id", $teamList, "", array('id'=>'guest_team_id'))?></td></tr>
	<tr><th>日 期</th><td><input type="date" id="play_date" value="<?=date('Y-m-d', strtotime($nowDate)+24*3600)?>"></td></tr>
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
			if(response.result == 0)
			{
				alert('预约成功');
				$("#friend_match_table").append("<tr><td>" + teamList[myTeamId] + "</td><td>" + teamList[postData.guest_team_id] + "</td><td>" + postData.play_date +  "</td></tr>");
			}
			else if(response.result == -1)
			{
				alert('预约失败，对方人员不足');
			}
			else if(response.result == -2)
			{
				alert('假期不能安排比赛');
			}

		}, 'json');
	});
	
	var myDate = new Date()
	myDate.setFullYear(<?=date('Y', strtotime($nowDate))?>,<?=(date('m', strtotime($nowDate))-1)?>,<?=date('d', strtotime($nowDate))?>);

	$( "#play_date" ).datepicker({
		defaultDate: myDate,
		dateFormat: "yy-mm-dd"
	});
</script>