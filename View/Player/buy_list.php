<table class="tb_style_1">
    <tr><th>name</th><th>team</th><th>pos</th><th>fee</th><th>salary</th><th>contract-begin</th><th>contract-end</th><th>操作</th></tr>
<?php foreach ($players as $player): ?>
    <tr>
		<td><?=$player['name']?></td>
		<td><?=(($player['team_id']?$teamList[$player['team_id']]:'自由球员'))?></td>
		<td><?=MainConfig::$positions[$player['position_id']]?></td>
		<td><?=$player['fee']?></td>
		<td><?=$player['salary']?></td>
		<td><?=$player['ContractBegin']?></td>
		<td><?=$player['ContractEnd']?></td>
		<td><a href="<?=  MainConfig::BASE_URL?>player/buy_apply/<?=$player['id']?>"><button>buy</button></a></td>
	</tr> 
<?php endforeach; ?>
</table>