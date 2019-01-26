<table class="table">
    <caption>联赛积分榜</caption>
    <tr><th>rank</th><th>team</th><th>win</th><th>draw</th><th>lose</th><th>goal</th><th>lost</th><th>jsq</th><th>score</th></tr>
<?php foreach($teams as $k=>$curTeam): ?>
        <tr>
			<td><?= ($k+1) ?></td>
			<td>
			<?php if($curTeam['id'] == $myTeamId): ?>
				<span style="color: green;font-weight: bold"><?= $curTeam['name'] ?></span>
			<?php else: ?>
				<?= $curTeam['name'] ?>
			<?php endif; ?>
			</td>
			<td><?php echo $curTeam['win'] ?></td>
			<td><?php echo $curTeam['draw'] ?></td>
			<td><?php echo $curTeam['lose'] ?></td>
			<td><?php echo $curTeam['goals'] ?></td>
			<td><?php echo $curTeam['lost'] ?></td>
			<td><?php echo $curTeam['jingshengqiu'] ?></td>
			<td><?php echo $curTeam['score'] ?></td>
		</tr>
<?php endforeach; ?>
</table>