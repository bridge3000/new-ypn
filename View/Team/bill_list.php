<table class="tb_style_1">
	<tr>
		<th>日期</th>
		<th>方式</th>
		<th>金额</th>
		<th>内容</th>
		<th>余额</th>
	</tr>
	<?php foreach ($bills as $bill): ?>
		<tr>
			<td>
		<?php if(isset($bill['PubTime'])): ?>
			<?=date('Y-m-d', $bill['PubTime']) ?>
		<?php else: ?>
			<?php print_r($bill); ?>
		<?php endif; ?>
			
			</td>
			<td><?= (($bill['money'] > 0) ? "收入" : "支出") ?></td>
			<td><?= $bill['money'] ?> </td>
			<td><?= $bill['info'] ?> </td>
			<td><?= $bill['remain'] ?> </td>
		</tr>
	<?php endforeach; ?>
</table>

<script>
</script>