<table class="tb_style_1">
	<tr>
		<th>号码</th>
		<th>姓名</th>
		<th>位置</th>
	<?php foreach($trainingList as $t):?>
		<th><?=$t['title']?></th>
	<?php endforeach; ?>
	</tr>
	<?php foreach ($players as $curPlayer): 	?>
		<tr>
			<td><?= $curPlayer['ShirtNo'] ?></td>
			<td><a href="javascript:;" class="player_name" value="<?= $curPlayer['id'] ?>"><?= $curPlayer['name'] ?></a></td>
			<td><?=MainConfig::$positions[$curPlayer['position_id']]?></td>
		<?php foreach($trainingList as $trainingId=>$t):?>	
			<td style="background-color: <?=($trainingId==$curPlayer['training_id'])?'green':''?>;" class="training_td" training_id="<?=$trainingId?>" player_id="<?=$curPlayer['id']?>"><?=$curPlayer[$t['skill']]?>(<?=$curPlayer[$t['experience']]?>)</td>
		<?php endforeach; ?>
		</tr>
	<?php endforeach; ?>
</table>

<div id="full_bg"></div>

<script>
	$(".training_td").click(function(){
		var onBgColor = "green";
		var offBgColor = "#ffffff";
		var playerId = $(this).attr("player_id");
		var trainingId = $(this).attr("training_id");
		var postData = {
			player_id: playerId,
			training_id: trainingId
		};

		$.post("<?=  MainConfig::BASE_URL?>player/ajax_change_training", postData, function(response){
			if(response.status == 0)
			{
				$("td").each(function(){
					if($(this).attr("player_id") == playerId)
					{
						if($(this).attr("training_id") == trainingId)
						{
							$(this).css("background-color", onBgColor);
						}
						else
						{
							$(this).css("background-color", offBgColor);
						}
					}
				});
			}
		}, 'json');
	});
</script>