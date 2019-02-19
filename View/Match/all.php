<span id="spTest">全部赛事</span>
<div align=center><a href="watch/">全选</a></div>
<div class="jumbotron" style="display: none"></div>
<table class="table table-striped">
<tr>
	<th>日期</th>
	<th>比赛</th>
	<th>主队</th>
	<th></th>
	<th>客队</th>
	<th></th>
	</tr>
<?php foreach ($matches as $match): ?>
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
	<?php if ($match['isPlayed']): ?>
		<img border="0" src="/res/img/replay.gif" onclick="reply(<?=$match['id']?>)" />
	<?php else: ?>
		<input type="checkbox" name="checkbox" id="cb<?php echo $match['id']; ?>" <?php if ($match['isWatched']) echo (" checked"); ?> onclick="watchMatch(<?php echo $match['id']; ?>);return false;" />
	<?php endif; ?>
	</td>
	</tr>
<?php endforeach; ?>
</table>

<script>
	function watchMatch(match_id)
	{
		$.get("/match/watch/" + match_id, {}, function(data){
			$('#spTest').html(data);
		});
	}

	function reply(matchId)
	{
		$.get("/match/ajax_get_reply/" + matchId, {}, function(reply){
			$(".jumbotron").html(reply).fadeIn();
		});
	}
</script>