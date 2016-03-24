<?php
use \Util\FormHelper;
?>
<form method="post" action="<?=MainConfig::BASE_URL?>player/buy/<?=$curPlayer['id']?>" >
	<input type="hidden" name="buy_team_id" value="<?=$myTeamId?>" >
<table class="tb_style_1">
	<tr><th>name</th><td><?=$curPlayer['name']?></td></tr>
	<tr><th>目前球队：</th><td><?=($curPlayer['team_id'])?$curTeam['name']:'自由球员'?></td></tr>
	<tr><th>挂牌价格：</th><td><?=($curPlayer['team_id'])?$curPlayer['fee']:0?></td></tr>
	<tr><th>出价：</th><td><input type="number" name="new_price" value="0"></td></tr>
	<tr><th>目前周新：</th><td><?=$curPlayer['salary']?></td></tr>
	<tr><th>签约周新：</th><td><input name="new_salary" type="text" value="<?=$curPlayer['salary']?>" ></td></tr>
	<tr><th>合同期：</th><td><?=$curPlayer['ContractBegin']?> - <?=$curPlayer['ContractEnd']?></td></tr>
	<tr>
		<th>签约时间：</th>
		<td><?=FormHelper::select("month", $years, '', array())?></td>
	</tr>
	<tr><th></th><td><input type="submit" value="buy"></td></tr>
</table>
</form>