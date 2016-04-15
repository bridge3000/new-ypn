<table class="tb_style_1">
	<tr>
		<th>号码</th>
		<th>姓名</th>
		<th>位置</th>
	<?php foreach($trainingList as $t):?>
		<th><?=$t['title']?></th>
	<?php endforeach; ?>
	</tr>
	<?php foreach ($players as $curPlayer): 	?>
		<tr>
			<td><?= $curPlayer['ShirtNo'] ?></td>
			<td><a href="javascript:;" class="player_name" value="<?= $curPlayer['id'] ?>"><?= $curPlayer['name'] ?></a></td>
			<td><?=MainConfig::$positions[$curPlayer['position_id']]?></td>
		<?php foreach($trainingList as $t):?>	
			<td><?=$curPlayer[$t['field']]?>(<?=$curPlayer[$t['experience']]?>)</td>
		<?php endforeach; ?>
		</tr>
	<?php endforeach; ?>
</table>

<div id="full_bg"></div>

<script>
	
</script>