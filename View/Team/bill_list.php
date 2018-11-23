<table class="tb_style_1">
	<tr>
		<th>日期</th>
		<th>方式</th>
		<th>金额</th>
		<th>内容</th>
		<th>余额</th>
	</tr>
	<?php foreach ($bills as $curCollectPlayer): ?>
		<tr>
			<td><?=date('Y-m-d', $curCollectPlayer['PubTime']) ?></td>
			<td><?= (($curCollectPlayer['money'] > 0) ? "收入" : "支出") ?></td>
			<td><?= $curCollectPlayer['money'] ?> </td>
			<td><?= $curCollectPlayer['info'] ?> </td>
			<td><?= $curCollectPlayer['remain'] ?> </td>
		</tr>
	<?php endforeach; ?>
</table>

<script>
</script>