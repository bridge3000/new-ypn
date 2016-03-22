<table class="tb_style_1">
    <tr><th>name</th><th>team</th><th>pos</th><th>fee</th><th>salary</th><th>contract-begin</th><th>contract-end</th><th>操作</th></tr>
<?php foreach ($players as $n): ?>
    <tr>
		<td><?=$n['name']?></td>
		<td><?=(($n['team_id']?$teamList[$n['team_id']]:'自由球员'))?></td>
		<td><?=MainConfig::$positions[$n['position_id']]?></td>
		<td><?=$n['fee']?></td>
		<td><?=$n['salary']?></td>
		<td><?=$n['ContractBegin']?></td>
		<td><?=$n['ContractEnd']?></td>
		<td><a href="<?=  MainConfig::BASE_URL?>player/buy_apply/<?=$n['id']?>"><button>buy</button></a></td>
	</tr> 
<?php endforeach; ?>
</table>

<div class="pagination">
<?php for($i=1;$i<=$pageCount;$i++): ?>
	<a href="<?=  MainConfig::BASE_URL?>player/buy_list/1/<?=$i?>" class="<?=(($i==$curPage)?"active":"")?>"><?=$i?></a>
<?php endfor; ?>
</div>