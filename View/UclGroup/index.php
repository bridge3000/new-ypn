
<?php foreach ($groups as $k => $group): ?>
	<div><?= strtoupper($k) ?> GROUP</div>
	<table class="tb_style_1">
		<tr>
			<th>team</th>
			<th>goal</th>
			<th>lost</th>
			<th>win</th>
			<th>lose</th>
			<th>draw</th>
			<th>score</th>
		</tr> 
		<?php foreach ($group as $team): ?>
			<tr>
				<td><?= $team['team_name'] ?></td>
				<td><?= $team['goal'] ?></td>
				<td><?= $team['lost'] ?></td>
				<td><?= $team['win'] ?></td>
				<td><?= $team['lose'] ?></td>
				<td><?= $team['draw'] ?></td>
				<td><?= $team['score'] ?></td>
			</tr> 
		<?php endforeach; ?>
	</table>
	<br><br>
<?php endforeach; ?>