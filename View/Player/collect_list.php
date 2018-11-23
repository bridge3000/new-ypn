
<table class="tb_style_1">
	<tr><th>id</th><th>name</th><th>收藏时间</th><th>删除</th></tr>
	<?php foreach ($collectPlayers as $curCollectPlayer): ?>
		<tr>
			<td><?= $curCollectPlayer['player_id'] ?></td>
			<td><a href="/player/buy_apply/<?= $curCollectPlayer['player_id'] ?>"><?= $curCollectPlayer['name'] ?></a></td>
			<td><?= $curCollectPlayer['collect_date'] ?></td>
			<td><a href="/player/collect_del/<?= $curCollectPlayer['player_id'] ?>">删除</a></td>
		</tr> 
	<?php endforeach; ?>
</table>
