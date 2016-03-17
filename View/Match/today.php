
<script>
function watchMatch(match_id)
{
	$.get("index.php?c=match&a=watch&p=" + match_id, {}, function(data){
		//
	});
}
</script>
<?php 
//print_r($allTeams);
?>
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
	<div align=center><a href="watch/">全选</a></div>
	<table border="0" align="center" cellpadding="3" cellspacing="1" bgcolor="silver">
	<tr bgcolor="whitesmoke">
		<th>比赛</th>
		<th>主队</th>
		<th></th>
		<th>客队</th>
		<th><img src="" /></th>
		</tr>
	<?php
	$i = 0;
	foreach ($matches as $match):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
		<tr bgcolor="#FFFFFF"<?php echo $class;?>>
            <td><?php echo MainConfig::$matchClasses[$match['class_id']]; ?></td>
		<td align="right" bgcolor="#FFFFFF"><?php echo $match['HostGoaler_ids']; ?><?php echo  $allTeams[$match['HostTeam_id']]; ?>		</td>
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
		<td align="left" bgcolor="#FFFFFF"><?php echo  $allTeams[$match['GuestTeam_id']]; ?><?php echo $match['GuestGoaler_ids']; ?></td>
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

