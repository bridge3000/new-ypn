<table class="tb_style_1">
	<tr>
		<th>日期</th>
		<th>方式</th>
		<th>金额</th>
		<th>内容</th>
		<th>余额</th>
	</tr>
	<?php foreach ($bills as $curPlayer): ?>
		<tr>
			<td><?=date('Y-m-d', $curPlayer['PubTime']) ?></td>
			<td><?= (($curPlayer['money'] > 0) ? "收入" : "支出") ?></td>
			<td><?= $curPlayer['money'] ?> </td>
			<td><?= $curPlayer['info'] ?> </td>
			<td><?= $curPlayer['remain'] ?> </td>
		</tr>
	<?php endforeach; ?>
</table>

<script>
</script>