<span id="spTest">全部赛事</span>
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
	<td align="right"><?=$match['host_team_name']?>		</td>
	<td>
	<?php if($match['isPlayed']): ?>
		<?=$match['HostGoals']?>：<?=$match['GuestGoals']?>
	<?php else: ?>
		-:-
	<?php endif; ?>
	</td>
	<td align="left"><?=$match['guest_team_name']?></td>
	<td>
<?php
	if ($match['isPlayed'])
	{
?>
		<a href="replay/<?php echo $match['id']; ?>"><img border="0" src="/res/img/replay.gif" /></a>
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

<script>
function watchMatch(match_id)
{
	$.get("/index.php?c=match&a=watch&p=" + match_id, {}, function(data){
		$('#spTest').html(data);
	});
	
}
</script>