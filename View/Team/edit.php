<link href="/res/css/bootstrapSwitch.css" rel="stylesheet">
<script src="/res/js/bootstrapSwitch.js"></script>
<span id="spMsg"></span>

<table class="table">
	<tr>
		<td align="right" bgcolor="whitesmoke">队 标：</td>
		<td bgcolor="#FFFFFF"><img src="" /></td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">名 称：</td>
		<td bgcolor="#FFFFFF"><?php echo $myTeam['name']; ?></td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">流动资金：</td>
		<td bgcolor="#FFFFFF">
			<?php
			if ($myTeam['money'] > 0) {
				echo "<font color=green><strong>" . $myTeam['money'] . "</strong></font>";
			} else {
				echo "<font color=red><strong>" . $myTeam['money'] . "</strong></font>";
			}
			?>万欧元</td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">赞助费：</td>
		<td bgcolor="#FFFFFF"><?php echo $myTeam['sponsor']; ?>万欧元</td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">票 价：</td>
		<td bgcolor="#FFFFFF"><div id="spPrice" style="width:30px; height:18px; float:left;"><?php echo $myTeam['TicketPrice']; ?></div>万欧元&nbsp;
			<span id="btnPrice1" style="cursor:pointer;  background-color:#868BF2; padding:3px; color:#FFF;" onclick="changePrice();">修 改</span>
			<span id="btnPrice2" style="cursor:pointer; display:none; background-color:#868BF2; padding:3px;color:#FFF;" onclick="savePrice();">保 存</span></td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">每周球员工资总额：</td>
		<td bgcolor="#FFFFFF"><?php echo $myTeam['total_salary']; ?>万欧元</td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">人 气：</td>
		<td bgcolor="#FFFFFF"><span id="spPopular"><?php echo $myTeam['popular']; ?></span></td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">座位数：</td>
		<td bgcolor="#FFFFFF"><?php echo $myTeam['seats']; ?></td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">球场名称：</td>
		<td bgcolor="#FFFFFF">
			<div id="spfieldName" style="width:140px; height:18px; float:left;"><?php echo $myTeam['FieldName']; ?></div>&nbsp;
			<span id="button1" style="cursor:pointer;  background-color:#868BF2; padding:3px; color:#FFF;" onclick="changefieldName();">修 改</span>
			<span id="button2" style="cursor:pointer; display:none; background-color:#868BF2; padding:3px;color:#FFF;" onclick="savefieldName();">保 存</span>

		</td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">阵 型：</td>
		<td bgcolor="#FFFFFF"><?php echo $myTeam['formattion']; ?></td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">队 长：</td>
		<td bgcolor="#FFFFFF">

			<select onchange="changeKicker('captain_id', $(this).val())">
<?php
foreach ($myPlayers as $k => $v) {
	?>
					<option value="<?php echo $k ?>" <?php if ($k == $myTeam['captain_id']) echo(' selected') ?>><?php echo $v ?></option>
					<?php
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">点球手：</td>
		<td bgcolor="#FFFFFF">
			<select onchange="changeKicker('PenaltyKicker_id', $(this).val())">
<?php
foreach ($myPlayers as $k => $v) {
	?>
					<option value="<?php echo $k ?>" <?php if ($k == $myTeam['PenaltyKicker_id']) echo(' selected') ?>><?php echo $v ?></option>
					<?php
				}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">任意球手：</td>
		<td bgcolor="#FFFFFF">
			<select onchange="changeKicker('FreeKicker_id', $(this).val())">
<?php
foreach ($myPlayers as $k => $v) {
	?>
					<option value="<?php echo $k ?>" <?php if ($k == $myTeam['FreeKicker_id']) echo(' selected') ?>><?php echo $v ?></option>
	<?php
}
?>
			</select>
		</td></tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">角球手：</td>
		<td bgcolor="#FFFFFF">
			<select onchange="changeKicker('CornerKicker_id', $(this).val())">
			<?php foreach ($myPlayers as $k => $v): 	?>
				<option value="<?php echo $k ?>" <?php if ($k == $myTeam['CornerKicker_id']) echo(' selected') ?>><?php echo $v ?></option>
			<?php endforeach; ?>
			</select>
		</td></tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">自动设置阵容：</td>
		<td bgcolor="#FFFFFF">
			<div class="switch">
				<input type="checkbox" id="chkAutoFormat" <?=($myTeam['is_auto_format']?"checked":"")?> />
			</div>
		</td>
	</tr>
	<tr>
		<th>攻击程度：</th>
		<td><div id="sliderAttack"></div></td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">守门员参与最后一次定位球进攻：</td>
		<td bgcolor="#FFFFFF"><input type="checkbox" name="checkbox" id="checkbox" onclick="changeGoalkeeperAttack();" <?=($myTeam['isGoalkeeperAttack'] ? "checked" : '') ?> /></td>
	</tr>
</table>

<script>
	$("#sliderAttack").slider({
		value: <?=$myTeam['attack']?>,
		stop: function( event, ui ) {
			$.get("/team/ajax_change_attack/" + ui.value, {}, function (attack) {});
		}
	});

	function changeGoalkeeperAttack()
	{
		$.get("/change_goalkeeper_attack/", {}, function () {

		});
	}

	$("#chkAutoFormat").change(function(){
		var isChecked = $(this).is(':checked');
		isAutoFormat = isChecked ? 1 : 0;
		$.post("ajax_change_auto_format/", {auto_format:isAutoFormat}, function () {});
	});

	function changeKicker(kickerType, kickerId)
	{
		$.get("/index.php", {c: 'team', a: 'change_kicker', p: kickerType + ',' + kickerId}, function () {
		});
	}

	function changefieldName()
	{
		$("#spfieldName").html("<input type=text id='txtfieldName' style='width:160px; height:18px;border:1px dashed silver;' value='" + $("#spfieldName").html() + "' />");
		$("#button1").hide();
		$("#button2").show();
	}

	function savefieldName()
	{
		var fieldName = $('#txtfieldName').val();
		if (confirm('更改球场名称将使俱乐部人气下降，确认更改吗？'))
		{
			$.get("change_field_name/" + fieldName, function (data) {
				$("#spfieldName").html(fieldName);
				$("#button1").show();
				$("#button2").hide();
				$("#spPopular").html(eval($("#spPopular").html()) - 5);
			});
		}
	}

	function changePrice()
	{
		$("#spPrice").html("<input type=text id='txtPrice' style='width:30px; height:18px;border:1px dashed silver;' value='" + $("#spPrice").html() + "' />");
		$("#btnPrice1").hide();
		$("#btnPrice2").show();
	}

	function savePrice()
	{
		price = $('#txtPrice').val();
		if (confirm('更改球票价格将使俱乐部人气变化，确认更改吗？'))
		{
			$.get("/change_price/" + price, function (data) {
				$("#spPrice").html(price);
				$("#btnPrice1").show();
				$("#btnPrice2").hide();
				$("#spPopular").html(eval($("#spPopular").html()) - data);
			});
		}
	}
</script>