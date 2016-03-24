<table class="tb_style_1">
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
	<?php foreach ($players as $n): 	?>
		<tr>
			<td><?= $n['ShirtNo'] ?></td>
			<td><a href="javascript:;" class="player_name" value="<?= $n['id'] ?>"><?= $n['name'] ?></a></td>
			<td><?=MainConfig::$positions[$n['position_id']]?></td>
			<td><?=$n['state']?></td>
			<td>
			<?php
			if ($n['sinew'] < 78) {
				echo "<font color=red>" . $n['sinew'] . "</font>";
			} else {
				echo $n['sinew'];
			}
			?>
			</td>
			<td><?= $n['cooperate'] ?></td>
			<td>
				<?php if (date('md', strtotime($n['birthday'])) == date('md', strtotime($nowDate))): ?>
					<a href="javascript:;" value="<?= $n['id'] ?>" class="birthday_link">今日生日</a>
				<?php else: ?>
					<?= $n['birthday'] ?>
				<?php endif; ?>
			</td>
			<td><?=$n['ContractBegin']?> - <?=$n['ContractEnd']?></td>
			<td>
			<?php if($n['isSelling']): ?>
				挂牌中 <?=$n['fee']?>W
			<?php else: ?>
				<a href="javascript:;" class="sell_link" player_id="<?=$n['id']?>" player_name="<?=$n['name']?>">卖出</a>
			<?php endif; ?>

				<a href="">解约</a>
			</td>
		</tr>
	<?php endforeach; ?>
</table>

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