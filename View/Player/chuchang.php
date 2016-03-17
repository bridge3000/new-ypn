<?php 

?>
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
    $i = 0;
    
    foreach ($players as $player):
        $class = null;
        if ($i++ % 2 == 0) {
            $class = ' class="altrow"';
        }
    ?>
        <tr<?php echo $class;?>>
        <td><?php echo $player['ShirtNo']; ?></td>
        <td><a href="/ypn/players/view/<?php echo $player['id']; ?>"><?php echo $player['name']; ?></a></td>
        <td>
            <?php // echo $this->Form->input('position_id', array('label' => false, 'options' => $positions, 'selected' => $player['position_id'], 'onChange' => 'changePosition(' . $player['id'] . ', this.value);')); ?> 
            <select>
<?php
foreach(MainConfig::$positions as $k=>$v)
{
?>
                <option value="<?php echo $k ?>"><?php echo $v ?></option>               
<?php
}
?>
            </select>        
        </td>
        <td>
    <?php
        if ($player['condition_id'] == 4)
        {
            echo ("<img src='" . IMG_DIR . "/img/injured.gif'> " . $player['InjuredDay'] . "天");
        }
        else if ($player[$fieldPunish] > 0)
        {
            echo("<img src='" . IMG_DIR . "/img/RedCard.gif' />停赛" . $player[$fieldPunish] . "场");
        }
        else
        {
    ?>
            <select name="select" id="select" onchange="changeCondition(<?php echo $player['id']; ?>, this.value);">
            <option value="1" <?php if ($player['condition_id'] == 1) echo(" selected"); ?>>首发</option>
            <option value="2" <?php if ($player['condition_id'] == 2) echo(" selected"); ?>>板凳</option>
            <option value="3" <?php if ($player['condition_id'] == 3) echo(" selected"); ?>>场外</option>
            </select>
    <?php
        }	 
    ?>
        </td>
        <td>
        <select name="select3" id="select3" onchange="location='/ypn/players/changegroup/<?php echo $player['id']; ?>/' + this.value;">
        <option value="0">未分组</option>
    <?php
        for ($i = 0;$i < count($playergroups);$i++)
        {
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
foreach(MainConfig::$cornerPositions as $k=>$v)
{
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
        if ($player['sinew'] < 78)
        {
            echo "<font color=red>" . $player['sinew'] . "</font>"; 
        }
        else
        {
            echo $player['sinew']; 
        }
    ?>
        </td>
        <td><?php echo $player['cooperate']; ?></td>
        <td><?php echo $player['LeftProperties']; ?> </td>
        <td><?php echo $player['MidProperties']; ?> </td>
        <td><?php echo $player['RightProperties']; ?> </td>
        </tr>
    <?php endforeach; 
    ?>
    </table>
</td>

<td>
<table border="0" cellspacing="0" cellpadding="0">
<form method="post" action="addgroup">
<tr>
<td>分组：</td>
<td>
<select name="select3" id="select3" onchange="location='/ypn/players/chuchang/' + this.value;">
<option value="0">未选择</option>
<?php
for ($i = 0;$i < count($playergroups);$i++)
{
?>
	<option value="<?php echo $playergroups[$i]['ypn_player_groups']['id']; ?>" <?php if ($playergroups[$i]['ypn_player_groups']['id'] == $group_id) echo(" selected"); ?>><?php echo $playergroups[$i]['ypn_player_groups']['name']; ?></option>
<?php
}
?>
</select>
</td>
<td><input type="text" name="name" /></td>
<td><input type="submit" value="新建分组" /></td>
</tr>
</form>
</table>


<table border="0" cellspacing="0" cellpadding="3"  width="350" height="468" background="/img/field.gif">
	<tr>
		<td id="td5" align="center" class="whitebold12"></td>
		<td id="td7" align="center" class="whitebold12"></td>
		<td id="td6" align="center" class="whitebold12"></td>
		</tr>
	<tr>
		<td></td>
		<td id="td1" align="center" class="whitebold12"></td>
		<td></td>
		</tr>
	<tr>
		<td></td>
		<td id="td8" align="center" class="whitebold12"></td>
		<td></td>
		</tr>
	<tr>
		<td id="td9" align="center" class="whitebold12"></td>
		<td class="whitebold12"></td>
		<td id="td10" align="center" class="whitebold12"></td>
		</tr>
			<tr>
		<td class="whitebold12"></td>
		<td id="td2" align="center" class="whitebold12"></td>
		<td class="whitebold12"></td>
		</tr>
	<tr>
		<td id="td13" align="center" class="whitebold12"></td>
		<td id="td3" align="center" class="whitebold12"></td>
		<td id="td14" align="center" class="whitebold12"></td>
		</tr>
	<tr>
		<td></td>
		<td id="td4" align="center" class="whitebold12"></td>
		<td></td>
		</tr>
</table>
<br /><div align="center">目前场上有<span id="spShoufaCount" style="color:#0000FF;font-weight:bold;"><?php echo $shoufaCount; ?></span>人</div>
<div id="divTibu" align="center">
<table id="tbTibu" border="0" cellspacing="0" cellpadding="1">
<?php
for ($i = 0;$i < count($tibus);$i++):

?>
	<tr id="tr<?php echo $tibus[$i]['id']; ?>">
	<td><img src="/img/chair.gif" /></td><td><?php echo $tibus[$i]['ShirtNo']; ?></td><td><?php echo $tibus[$i]['name']; ?></td>
	</tr>
<?php
endfor;
?>
</table>
</div>
</td>


</tr>
</table>
<script>
<?php
for ($i = 0;$i < count($players);$i++):
	if ($players[$i]['condition_id'] == 1)
	{
		echo("$(\"#td" . $players[$i]['position_id'] . "\").html($(\"#td" . $players[$i]['position_id'] . "\").html() + \"&nbsp;" . $players[$i]['ShirtNo'] . $players[$i]['name'] . "\");");
	}
endfor;
?>
</script>
<script>
function changeCondition(playerId, conditionId)
{
	$.get("/ypn/players/ChangeCondition/" + playerId + "/" + conditionId, {}, function(data){
		$("#spMsg").html(data);
	});
}

function changePosition(playerId, positionId)
{
	$.get("/ypn/players/ChangePosition/" + playerId + "/" + positionId, {}, function(data){
		$("#spMsg").html(data);
	});
}

function changeCornerPosition(playerId, cornerPositionId)
{
	$.get("/ypn/players/ChangeCornerPosition/" + playerId + "/" + cornerPositionId, {}, function(data){
		$("#spMsg").html(data);
	});
}


</script>