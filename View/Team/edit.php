<span id="spMsg"></span>

<table width="500" border="0" cellspacing="1" cellpadding="3" align="center" bgcolor="#CCCCCC">
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
		<td bgcolor="#FFFFFF"><?php echo $myTeam['TotalSalary']; ?>万欧元</td>
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
		<td bgcolor="#FFFFFF"><input type="checkbox" id="chkAutoFormat" <?=($myTeam['is_auto_format']?"checked":"")?> /></td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">攻击程度：</td>
		<td bgcolor="#FFFFFF">
			<table border="0" cellspacing="0" cellpadding="3">
				<tr>
<?php
for ($i = 10; $i < 110; $i += 10) {
	$bgColor = "";
	if ($i == $myTeam['attack']) {
		$bgColor = "red";
	}
	?>
						<td class="attack_td" value="<?=$i?>" style="cursor:pointer;background-color:<?=$bgColor; ?>" onclick="changeAttack(<?=$i?>);"><?=$i?></td>
						<?php
					}
					?>
				</tr>
			</table>		
		</td>
	</tr>
	<tr>
		<td align="right" bgcolor="whitesmoke">守门员参与最后一次定位球进攻：</td>
		<td bgcolor="#FFFFFF"><input type="checkbox" name="checkbox" id="checkbox" onclick="changeGoalkeeperAttack();" <?=($myTeam['isGoalkeeperAttack'] ? "checked" : '') ?> /></td>
	</tr>
	<tr>
		<td colspan="2" align="center" bgcolor="#FFFFFF">
			<object id="flashad" width="500" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"> 
				<param value="../flash/club.swf" name="movie">  
				<param value="autohigh" name="quality">  
				<param value="opaque" name="wmode">  
				<embed width="500" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" swliveconnect="TRUE" name="flashad" wmode="opaque" quality="autohigh" src="../flash/club.swf">
			</object>
		</td>
	</tr>
</table>

<script>
	function changeAttack(attackRate)
	{
		$.get("/index.php?c=team&a=ajax_change_attack&p=" + attackRate, {}, function (attack) {
			$(".attack_td").each(function(){
				if ($(this).attr("value") == attack)
				{
					$(this).css("background-color", "red");
				}
				else
				{
					$(this).css("background-color", "white");
				}
			});
		});
	}

	function changeGoalkeeperAttack()
	{
		$.get("/change_goalkeeper_attack/", {}, function () {

		});
	}

	$("#chkAutoFormat").click(function(){
		
		var isChecked = $(this).is(':checked');
		isAutoFormat = isChecked ? 1 : 0;
		console.log(isAutoFormat);
		
		$.post("ajax_change_auto_format/", {auto_format:isAutoFormat}, function () {

		});
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