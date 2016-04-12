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
			<td><?=date('Y-m-d', $curPlayer['date']) ?></td>
			<td><?= (($curPlayer['dir'] == 1) ? "收入" : "支出") ?></td>
			<td><?= $curPlayer['money'] ?> </td>
			<td><?= $curPlayer['content'] ?> </td>
			<td><?= $curPlayer['remain'] ?> </td>
		</tr>
	<?php endforeach; ?>
</table>

<script>
</script>