<table class="table table-striped">
	<tr>
		<th>号码</th>
		<th>姓名</th>
		<th>位置</th>
		<th>状态</th>
		<th>体力</th>
		<th>磨合度</th>
		<th>生日</th>
		<th>合同</th>
		<th>操作</th>
	</tr>
	<?php foreach ($players as $curCollectPlayer): 	?>
		<tr>
			<td><?= $curCollectPlayer['ShirtNo'] ?></td>
			<td><a href="/player/show/<?= $curCollectPlayer['id'] ?>" class="player_name" value="<?= $curCollectPlayer['id'] ?>"><?= $curCollectPlayer['name'] ?></a></td>
			<td><?=MainConfig::$positions[$curCollectPlayer['position_id']]?></td>
			<td><?=$curCollectPlayer['state']?></td>
			<td>
			<?php
			if ($curCollectPlayer['sinew'] < 78) {
				echo "<font color=red>" . $curCollectPlayer['sinew'] . "</font>";
			} else {
				echo $curCollectPlayer['sinew'];
			}
			?>
			</td>
			<td><?= $curCollectPlayer['cooperate'] ?></td>
			<td>
				<?php if (date('md', strtotime($curCollectPlayer['birthday'])) == date('md', strtotime($nowDate))): ?>
					<a href="javascript:;" value="<?= $curCollectPlayer['id'] ?>" class="birthday_link">今日生日</a>
				<?php else: ?>
					<?= $curCollectPlayer['birthday'] ?>
				<?php endif; ?>
			</td>
			<td><?=date('Y.m.d', strtotime($curCollectPlayer['ContractBegin']))?> - <?=date('Y.m.d', strtotime($curCollectPlayer['ContractEnd']))?></td>
			<td>
				<a href="">解约</a>
				
			<?php if( strtotime($curCollectPlayer['ContractEnd'])-strtotime($nowDate) < 12*30*24*3600): ?>
				<a href="/player/show/<?= $curCollectPlayer['id'] ?>">
					<button class="btn btn-info">续约</button>
				</a>
			<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>
<div>共 <?=count($players)?> 人</div>

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