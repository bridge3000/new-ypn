<?php
//print_r($teamList);
?>

<table class="tb_style_1">
    <tr><th>name</th><th>team</th><th>pos</th><th>fee</th><th>salary</th></tr>
<?php
foreach ($players as $player)
{
?>
    <tr><td><?php echo $player['name']?></td><td><?php echo $teamList[$player['team_id']] ?></td><td><?php echo MainConfig::$positions[$player['position_id']] ?></td><td><?php echo $player['fee']?></td><td><?php echo $player['salary']?></td></tr> 
<?php
}
?>
</table>