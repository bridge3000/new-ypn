<span id="spMsg" style="color:#ff0000;"></span>
<table border="0" cellspacing="0" cellpadding="3">
	<tr valign="top">
		<td>
			<table class="table table-bordered">
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
				<?php foreach ($players as $curPlayer): ?>
					<tr>
						<td><?= $curPlayer['ShirtNo'] ?></td>
						<td><a href="/player/show/<?= $curPlayer['id'] ?>" class="player_name"><?= $curPlayer['name'] ?></a></td>
						<td>
							<select id="sel_position" onchange="changePosition(<?= $curPlayer['id'] ?>, this.value)">
								<?php foreach (MainConfig::$positions as $k => $v): ?>
									<option value="<?= $k ?>" <?= (($k == $curPlayer['position_id']) ? 'selected' : '') ?> ><?= $v ?></option>               
								<?php endforeach; ?>
							</select>        
						</td>
						<td>
							<?php
							if ($curPlayer['condition_id'] == 4)
							{
								echo ("<img src='" . MainConfig::BASE_URL . "/res/img/injured.gif'> " . $curPlayer['InjuredDay'] . "天");
							} else if ($curPlayer[$fieldPunish] > 0)
							{
								echo("<img src='" . MainConfig::BASE_URL . "/res/img/RedCard.gif' />停赛" . $curPlayer[$fieldPunish] . "场");
							} else
							{
								?>
								<select name="select" id="select" onchange="changeCondition(<?php echo $curPlayer['id']; ?>, this.value)">
									<option value="1" <?php if ($curPlayer['condition_id'] == 1) echo(" selected"); ?>>首发</option>
									<option value="2" <?php if ($curPlayer['condition_id'] == 2) echo(" selected"); ?>>板凳</option>
									<option value="3" <?php if ($curPlayer['condition_id'] == 3) echo(" selected"); ?>>场外</option>
								</select>
								<?php
							}
							?>
						</td>
						<td>
							<select onchange="location = '/player/changegroup/<?php echo $curPlayer['id']; ?>/' + this.value;">
								<option value="0">未分组</option>
								<?php for ($i = 0; $i < count($playergroups); $i++): ?>
									<option value="<?=$playergroups[$i]['id']?>" <?php if ($playergroups[$i]['id'] == $curPlayer['group_id']) echo(" selected"); ?>><?=$playergroups[$i]['name']?></option>
								<?php endfor; ?>
							</select>
						</td>
						<td>
						<?php foreach ($cornerPositions as $k => $v): ?>
							<button name="corner_button" type="button" class="btn btn-xs <?=($curPlayer['CornerPosition_id']==$k ? 'btn-danger' : 'btn-default')?>" player_id="<?=$curPlayer['id']?>" corner_id="<?=$k?>"><?=$v?></button>
						<?php endforeach; ?>
						</td>  
						<td><?php echo $curPlayer['state']; ?></td>
						<td>
							<?php
							if ($curPlayer['sinew'] < 78)
							{
								echo "<font color=red>" . $curPlayer['sinew'] . "</font>";
							} else
							{
								echo $curPlayer['sinew'];
							}
							?>
						</td>
						<td><?= $curPlayer['cooperate'] ?></td>
						<td><?= $curPlayer['LeftProperties'] ?> </td>
						<td><?= $curPlayer['MidProperties'] ?> </td>
						<td><?= $curPlayer['RightProperties'] ?> </td>
					</tr>
			<?php endforeach; ?>
			</table>
		</td>

		<td>
			<table border="0" cellspacing="0" cellpadding="0">
				<form method="post" action="/playergroup/add">
					<input type="hidden" name="team_id" value="<?=$teamId?>" />
					<tr>
						<td>分组：</td>
						<td>
							<select onchange="location = '/player/chuchang/' + this.value;">
								<option value="0">未选择</option>
							<?php foreach($playergroups as $playerGroup): ?>
								<option value="<?=$playerGroup['id']?>" <?=($playerGroup['id']==$groupId)?'selected':''?>><?=$playerGroup['name']?></option>
							<?php endforeach; ?>
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
				<li><?= $tibus[$i]['ShirtNo'] ?><?= $tibus[$i]['name'] ?></li>
			<?php endfor; ?>
			</ul>
		</td>
	</tr>
</table>

<script>
	var players = <?= json_encode($players) ?>;
	var fieldWidth = 350;
	var fieldHeight = 468;
	var perWidth = Math.floor(fieldWidth / 5);
	var perHeight = Math.floor(fieldHeight / 7);
	var shoufaCount = <?= $shoufaCount ?>;

	var positionCor = {
		1: [1, 2], // => '前锋',
		2: [4, 2], // => '后腰',
		3: [5, 2], // => '中后卫',
		4: [6, 2], // => '门将',
		5: [0, 0], // => '左边锋',
		6: [0, 4], // => '右边锋',
		7: [0, 2], //=> '中锋',
		8: [2, 2], // => '前腰',
		9: [3, 1], // => '左前卫',
		10: [3, 3], // => '右前卫',
		13: [5, 0], // => '左后卫',
		14: [5, 4] // => '右后卫'
	};
	
	$(document).ready(function(){
		for (var i in players)
		{
			if (players[i].condition_id == 1)
			{
				players[i].div = getDiv(players[i]);
				players[i].div.appendTo($("#field"));
				changeCor(players[i].position_id, players[i].div);
			}
		}
	});

	function getDiv(player)
	{
		var curDiv = $("<div></div>")
				.text(player.name)
				.attr("class", "player_position")
				.css("width", perWidth)
				.css("height", perHeight)
				.css("line-height", perHeight + "px");
		return curDiv;
	}

	function changeCor(positionId, $div)
	{
		var rndColors = ['white', 'yellow', 'red'];
		var posX = positionCor[positionId][1] * perWidth + 80*(Math.random()-0.5);
		var posY = positionCor[positionId][0] * perHeight;
		
		$div.css("left", posX);
		$div.css("top", posY);
		
		var rndIndex = parseInt(Math.random()*rndColors.length);
		$div.css("color", rndColors[rndIndex]);
	}

	function changeCondition(playerId, conditionId)
	{
		$.get("<?= MainConfig::BASE_URL ?>player/ajax_change_condition/" + playerId + "/" + conditionId, {}, function (data) {
			for (var i in players)
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
							shoufaCount--;
						}

						$("#spShoufaCount").text(shoufaCount);
					}
					else if (conditionId == 3)
					{
						if (players[i].condition_id == 1)
						{
							players[i].div.remove();
							shoufaCount--;
						}
						
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
			for (var i in players)
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

	function reloadTibu()
	{
		$("#tibu").empty();
		for (var i in players)
		{
			if (players[i].condition_id == 2)
			{
				$("#tibu").append("<li>" + players[i].ShirtNo + players[i].name + "</li>");
			}
		}
	}

	$(".player_name").click(function () {
		$.get("<?= MainConfig::BASE_URL ?>player/ajax_get/" + $(this).attr("value"), {}, function (player) {
			$("#player_div").fadeIn();
			$("#player_name").text(player.name);
			$("#player_state").text(player.state);
			$("#player_sinew").text(player.sinew);
			$("#full_bg").show();
		}, 'json');
	});

	$("#full_bg").click(function () {
		$("#player_div").fadeOut();
		$("#full_bg").fadeOut();
	});

	$("[name='corner_button']").click(function(){
		var playerId = $(this).attr("player_id");
		var cornerId = $(this).attr("corner_id");
		var postData = {
			player_id: playerId,
			corner_id: cornerId
		};
		
		var $btn = $(this);
		
		$.post("/player/ajax_change_corner", postData, function(response){
			if(response.code == 1)
			{
				$("[name='corner_button']").each(function(){
					if($(this).attr("player_id") == playerId)
					{
						$(this).removeClass("btn-danger");
					}
				});
				
				$btn.removeClass("btn-default").addClass("btn-danger");
			}
		}, 'json');
	});
</script>