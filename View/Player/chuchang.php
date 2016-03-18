<span id="spMsg" style="color:#ff0000;"></span>
<table border="0" cellspacing="0" cellpadding="3">
	<tr valign="top">
		<td>
			<table border="0" align="center" cellpadding="3" cellspacing="1">
				<tr>
					<th>号码</th>
					<th>姓名</th>
					<th>位置</th>
					<th>状况</th>
					<th>所在分组</th>
					<th>角球位</th>
					<th>状态</th>
					<th>体力</th>
					<th>磨合度</th>
					<th>左属性</th>
					<th>中属性</th>
					<th>右属性</th>
				</tr>
<?php
foreach ($players as $player):
?>
					<tr>
						<td><?php echo $player['ShirtNo']; ?></td>
						<td><a href="/ypn/players/view/<?php echo $player['id']; ?>"><?php echo $player['name']; ?></a></td>
						<td>
	<?php // echo $this->Form->input('position_id', array('label' => false, 'options' => $positions, 'selected' => $player['position_id'], 'onChange' => 'changePosition(' . $player['id'] . ', this.value);'));  ?> 
							<select id="sel_position" onchange="changePosition(<?=$player['id']?>, this.value)">
							<?php
							foreach (MainConfig::$positions as $k => $v) {
								?>
									<option value="<?=$k?>" <?=(($k==$player['position_id'])?'selected':'')?> ><?=$v?></option>               
									<?php
								}
								?>
							</select>        
						</td>
						<td>
	<?php
	if ($player['condition_id'] == 4) {
		echo ("<img src='" . IMG_DIR . "/img/injured.gif'> " . $player['InjuredDay'] . "天");
	} else if ($player[$fieldPunish] > 0) {
		echo("<img src='" . IMG_DIR . "/img/RedCard.gif' />停赛" . $player[$fieldPunish] . "场");
	} else {
		?>
								<select name="select" id="select" onchange="changeCondition(<?php echo $player['id']; ?>, this.value)">
									<option value="1" <?php if ($player['condition_id'] == 1) echo(" selected"); ?>>首发</option>
									<option value="2" <?php if ($player['condition_id'] == 2) echo(" selected"); ?>>板凳</option>
									<option value="3" <?php if ($player['condition_id'] == 3) echo(" selected"); ?>>场外</option>
								</select>
								<?php
							}
							?>
						</td>
						<td>
							<select name="select3" id="select3" onchange="location = '/ypn/players/changegroup/<?php echo $player['id']; ?>/' + this.value;">
								<option value="0">未分组</option>
							<?php
							for ($i = 0; $i < count($playergroups); $i++) {
								?>
									<option value="<?php echo $playergroups[$i]['ypn_player_groups']['id']; ?>" <?php if ($playergroups[$i]['ypn_player_groups']['id'] == $player['group_id']) echo(" selected"); ?>><?php echo $playergroups[$i]['ypn_player_groups']['name']; ?></option>
		<?php
	}
	?>
							</select>
						</td>
						<td><?php // echo $this->Form->input('CornerPosition_id', array('options' => $cornerpositions, 'label' => false, 'default' => $player['CornerPosition_id'], 'onchange' => 'changeCornerPosition(' . $player['id'] . ', $(this).val())')); ?>

							<select>
								<?php
								foreach (MainConfig::$cornerPositions as $k => $v) {
									?>
									<option value="<?php echo $k ?>"><?php echo $v ?></option>               
		<?php
	}
	?>	
							</select> 

						</td>  

						<td><?php echo $player['state']; ?></td>
						<td>

								<?php
								if ($player['sinew'] < 78) {
									echo "<font color=red>" . $player['sinew'] . "</font>";
								} else {
									echo $player['sinew'];
								}
								?>
						</td>
						<td><?=$player['cooperate']?></td>
						<td><?=$player['LeftProperties']?> </td>
						<td><?=$player['MidProperties']?> </td>
						<td><?=$player['RightProperties']?> </td>
					</tr>
				<?php endforeach; ?>
			</table>
		</td>

		<td>
			<table border="0" cellspacing="0" cellpadding="0">
				<form method="post" action="addgroup">
					<tr>
						<td>分组：</td>
						<td>
							<select name="select3" id="select3" onchange="location = '/ypn/players/chuchang/' + this.value;">
								<option value="0">未选择</option>
							<?php for ($i = 0; $i < count($playergroups); $i++): ?>
								<option value="<?php echo $playergroups[$i]['ypn_player_groups']['id']; ?>" <?php if ($playergroups[$i]['ypn_player_groups']['id'] == $group_id) echo(" selected"); ?>><?php echo $playergroups[$i]['ypn_player_groups']['name']; ?></option>
							<?php endfor; ?>
							</select>
						</td>
						<td><input type="text" name="name" /></td>
						<td><input type="submit" value="新建分组" /></td>
					</tr>
				</form>
			</table>

			<div id="field"></div>

			<br /><div align="center">目前场上有<span id="spShoufaCount" style="color:#0000FF;font-weight:bold;"><?php echo $shoufaCount; ?></span>人</div>
			
			<ul id="tibu">
			<?php for ($i = 0; $i < count($tibus); $i++): ?>
				<li><?=$tibus[$i]['ShirtNo']?><?=$tibus[$i]['name']?></li>
			<?php endfor; ?>
			</ul>
		</td>
	</tr>
</table>

<script>
	var players = <?=json_encode($players)?>;
	var fieldWidth = 350;
	var fieldHeight = 468;
	var perWidth = Math.floor(fieldWidth / 5);
	var perHeight = Math.floor(fieldHeight / 7);
	var shoufaCount = <?=$shoufaCount?>;
	
	var positionCor = {
		1: [1,1], // => '前锋',
        2: [2,4], // => '后腰',
        3: [1,5], // => '中后卫',
        4: [2,6], // => '门将',
        5: [0,0], // => '左边锋',
        6: [4,0], // => '右边锋',
        7: [2,0], //=> '中锋',
        8: [2,2], // => '前腰',
        9: [1,3], // => '左前卫',
        10: [3,3], // => '右前卫',
        13: [0,5], // => '左后卫',
        14: [4,5] // => '右后卫'
	};
	
	function getDiv(player)
	{
		var curDiv = $("<div></div>")
				.text(player.name)
				.attr("class", "player_position")
				.css("width", perWidth)
				.css("height", perHeight)
				.css("line-height", perHeight+"px");
		return curDiv;
	}
	
	function changeCor(positionId, $div)
	{
		var posX = positionCor[positionId][0] * perWidth;
		var posY = positionCor[positionId][1] * perHeight;
		$div.css("left", posX);
		$div.css("top", posY);
	}
	
	for(var i in players)
	{
		if (players[i].condition_id == 1)
		{
			players[i].div = getDiv(players[i]);
			players[i].div.appendTo($("#field"));
			changeCor(players[i].position_id, players[i].div);
		}
	}
	
	function changeCondition(playerId, conditionId)
	{
		$.get("<?= MainConfig::BASE_URL ?>player/ajax_change_condition/" + playerId + "/" + conditionId, {}, function (data) {
			for(var i in players)
			{
				if (players[i].id == playerId)
				{

					if (conditionId == 1)
					{
						players[i].div = getDiv(players[i]);
						players[i].div.appendTo($("#field"));
						changeCor(players[i].position_id, players[i].div);
						shoufaCount++;
						$("#spShoufaCount").text(shoufaCount);
					}
					else if (conditionId == 2)
					{
						if (players[i].condition_id == 1)
						{
							players[i].div.remove();
						}
						shoufaCount--;
						$("#spShoufaCount").text(shoufaCount);
					}
					else if (conditionId == 3)
					{
						if (players[i].condition_id == 1)
						{
							players[i].div.remove();
						}
						shoufaCount--;
						$("#spShoufaCount").text(shoufaCount);
					}
					
					players[i].condition_id = conditionId;
					break;
				}
			}
			
			reloadTibu();
		});
	}

	function changePosition(playerId, positionId)
	{
		$.get("<?= MainConfig::BASE_URL ?>player/ajax_change_position/" + playerId + "/" + positionId, {}, function (data) {
			for(var i in players)
			{
				if (players[i].id == playerId)
				{
					players[i].position_id = positionId;
					if (players[i].condition_id == 1)
					{
						changeCor(positionId, players[i].div)
					}
					break;
				}
			}
		});
	}

	function changeCornerPosition(playerId, cornerPositionId)
	{
		$.get("/ypn/players/ChangeCornerPosition/" + playerId + "/" + cornerPositionId, {}, function (data) {
			$("#spMsg").html(data);
		});
	}

	function reloadTibu()
	{
		$("#tibu").empty();
		for(var i in players)
		{
			if(players[i].condition_id == 2)
			{
				$("#tibu").append("<li>" + players[i].ShirtNo + players[i].name + "</li>");
			}
		}
	}
</script>