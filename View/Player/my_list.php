<table class="table table-striped">
	<tr>
		<th>号码</th>
		<th>姓名</th>
		<th>位置</th>
		<th>状态</th>
		<th>体力</th>
		<th>磨合度</th>
		<th>生日</th>
		<th>得分</th>
		<th>场次</th>
		<th>场均得分</th>
		<th>合同</th>
		<th>操作</th>
	</tr>
	<?php foreach ($players as $curPlayer): 	?>
		<tr>
			<td><?= $curPlayer['ShirtNo'] ?></td>
			<td><a href="/player/show/<?= $curPlayer['id'] ?>" class="player_name" value="<?= $curPlayer['id'] ?>"><?= $curPlayer['name'] ?></a></td>
			<td><?=MainConfig::$positions[$curPlayer['position_id']]?></td>
			<td><?=$curPlayer['state']?></td>
			<td>
			<?php
			if ($curPlayer['sinew'] < 78) {
				echo "<font color=red>" . $curPlayer['sinew'] . "</font>";
			} else {
				echo $curPlayer['sinew'];
			}
			?>
			</td>
			<td><?= $curPlayer['cooperate'] ?></td>
			<td>
				<?php if (date('md', strtotime($curPlayer['birthday'])) == date('md', strtotime($nowDate))): ?>
					<a href="javascript:;" value="<?= $curPlayer['id'] ?>" class="birthday_link">今日生日</a>
				<?php else: ?>
					<?= $curPlayer['birthday'] ?>
				<?php endif; ?>
			</td>
			<td><?= $curPlayer['total_score'] ?></td>
			<td><?= $curPlayer['all_matches_count'] ?></td>
			<td><?= $curPlayer['all_matches_count'] ? round( $curPlayer['total_score']/$curPlayer['all_matches_count'],2) :0 ?></td>
			<td><?=date('Y.m.d', strtotime($curPlayer['ContractBegin']))?> - <?=date('Y.m.d', strtotime($curPlayer['ContractEnd']))?></td>
			<td>
				<a href="">解约</a>
				
			<?php if( strtotime($curPlayer['ContractEnd'])-strtotime($nowDate) < 12*30*24*3600): ?>
				<a href="/player/show/<?= $curPlayer['id'] ?>">
					<button class="btn btn-info">续约</button>
				</a>
			<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>
<div>共 <?=count($players)?> 人 </div>

<table class="table table-striped">
	<tr>
		<th>位置</th>
		<td>
			<select id="position_id" class="form-control">
			<?php foreach(MainConfig::$positions as $k=>$v): ?>
				<option value="<?=$k?>"><?=$v?></option>
			<?php endforeach; ?>
			</select>
		</td>
		<td><button type="button" class="btn btn-warning" onclick="generateYoung()">Generate Young</button></td>
	</tr>
</table>

<script>
	function generateYoung()
	{
		var positionId = $("#position_id").val();
		$.post("/team/ajax_generate_young", {position_id: positionId}, function(response){
			if(response.code == 1)
			{
				location.href = "/player/show/"+response.player_id;
			}
			else
			{
				alert("操作失败");
			}
		}, 'json');
	}
</script>

<div id="full_bg"></div>
<div id="player_sell_div">
	<input type="hidden" id="sellingPlayerId">
	<span id="spanPlayerName"></span>
	<input type="text" id="playerPrice" value="0">
	<button id="btnSell">SELL</button>
</div>

<script>
	$(".birthday_link").click(function () {
		var playerId = $(this).attr("value");
		if (confirm('给予生日补助吗？'))
		{
			$.get("<?= MainConfig::BASE_URL ?>player/ajax_give_birthday_subsidy/" + playerId);
		}
	});
	
	$(".sell_link").click(function () {
		var playerId = $(this).attr("player_id");
		var playerName = $(this).attr("player_name");
		$("#sellingPlayerId").val(playerId);
		$("#spanPlayerName").text(playerName);
		
		$("#full_bg").show();
		$("#player_sell_div").fadeIn();
	});
	
	$("#btnSell").click(function(){
		var playerId = $("#sellingPlayerId").val();
		var price = $("#playerPrice").val();
		$.get("<?= MainConfig::BASE_URL ?>player/ajax_sell/" + playerId + "/" + price, {}, function(result){
			if(result == 1)
			{
				$("#full_bg").fadeOut();
				$("#player_sell_div").fadeOut();
		
				$(".sell_link").each(function(){
					if($(this).attr("player_id") == playerId)
					{
						$(this).text("挂牌中 " + price + "W");
					}
				});
			}
		});
	});
	
	$("#full_bg").click(function(){
		$("#full_bg").fadeOut();
		$("#player_sell_div").fadeOut();
	});
</script>