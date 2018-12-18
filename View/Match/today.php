<span id="spTest">today match</span>
<?php 
if (count($matches) == 0)
{
?>
	<div align=center><img src="" /></div>
<?php 
}
else
{
?>
	<div align=center><a href="<?=  MainConfig::BASE_URL?>match/watch_today">全选</a></div>
	<table class="table table-bordered">
	<tr>
		<th>比赛</th>
		<th>主队</th>
		<th></th>
		<th>客队</th>
		<th><img src="" /></th>
		</tr>
	<?php
	$i = 0;
	foreach ($matches as $match):
	?>
		<tr>
            <td><?php echo MainConfig::$matchClasses[$match['class_id']]; ?></td>
		<td align="right"><?php echo $match['HostGoaler_ids']; ?><?php echo  $allTeams[$match['HostTeam_id']]; ?>		</td>
		<td>
		<?php
		if ($match['isPlayed'])
		{
			echo $match['HostGoals'] . ' ：' . $match['GuestGoals'];
		}
		else
		{
			echo '- ：-';
		}
		?>
        </td>
		<td align="left"><?=$allTeams[$match['GuestTeam_id']]?><?=$match['GuestGoaler_ids']?></td>
		<td>
	<?php
		if ($match['isPlayed'])
		{
	?>
			<a href="replay/<?php echo $match['id']; ?>"><img border="0" src="" /></a>
	<?php
		}
		else
		{
	?>
			<input type="checkbox" name="checkbox" id="cb<?php echo $match['id']; ?>" <?php if ($match['isWatched']) echo (" checked"); ?> onclick="watchMatch(<?php echo $match['id']; ?>);" />
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
		//
	});
}
</script>
