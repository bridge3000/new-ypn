
<table class="tb_style_1">
	<tr><th>id</th><th>name</th></tr>
	<?php foreach ($collectPlayers as $curPlayer): ?>
		<tr>
			<td><?= $curPlayer['player_id'] ?></td>
			<td><a href="/player/buy_apply/<?= $curPlayer['player_id'] ?>"><?= $curPlayer['name'] ?></a></td>
		</tr> 
	<?php endforeach; ?>
</table>



