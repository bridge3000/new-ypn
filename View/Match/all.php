<span id="spTest">today match</span>
<?php 
if (count($matches) == 0)
{
?>
	<div align=center><img src="/res/img/NoMatch.gif" /></div>
<?php 
}
else
{
?>
	<div align=center><a href="watch/">全选</a></div>
	<table class="table table-striped">
	<tr>
        <th>日期</th>
        <th>比赛</th>
		<th>主队</th>
		<th></th>
		<th>客队</th>
		<th></th>
		</tr>
	<?php
	$i = 0;
	foreach ($matches as $match):
	?>
		<tr>
        <td><?php echo $match['PlayTime']; ?></td>
        <td><?php echo MainConfig::$matchClasses[$match['class_id']]; ?></td>
		<td align="right"><?php echo $match['HostGoaler_ids']; ?><?php echo  $allTeams[$match['HostTeam_id']]; ?>		</td>
		<td>
		<?php if($match['isPlayed']): ?>
			<?=$match['HostGoals']?>：<?=$match['GuestGoals']?>
		<?php else: ?>
			-:-
		<?php endif; ?>
		</td>
		<td align="left"><?php echo  $allTeams[$match['GuestTeam_id']]; ?><?php echo $match['GuestGoaler_ids']; ?></td>
		<td>
	<?php
		if ($match['isPlayed'])
		{
	?>
			<a href="replay/<?php echo $match['id']; ?>"><img border="0" src="../img/replay.gif" /></a>
	<?php
		}
		else
		{
	?>
			<input type="checkbox" name="checkbox" id="cb<?php echo $match['id']; ?>" <?php if ($match['isWatched']) echo (" checked"); ?> onclick="watchMatch(<?php echo $match['id']; ?>);return false;" />
	<?php
		}
	?>
		</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php 
}
?>

<script>
function watchMatch(match_id)
{
	$.get("/index.php?c=match&a=watch&p=" + match_id, {}, function(data){
		$('#spTest').html(data);
	});
	
}
</script>