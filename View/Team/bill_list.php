<table class="tb_style_1">
	<tr>
		<th>日期</th>
		<th>方式</th>
		<th>金额</th>
		<th>内容</th>
		<th>余额</th>
	</tr>
	<?php foreach ($bills as $n): ?>
		<tr>
			<td><?=date('Y-m-d', $n['date']) ?></td>
			<td><?= (($n['dir'] == 1) ? "收入" : "支出") ?></td>
			<td><?= $n['money'] ?> </td>
			<td><?= $n['content'] ?> </td>
			<td><?= $n['remain'] ?> </td>
		</tr>
	<?php endforeach; ?>
</table>

<script>
</script>