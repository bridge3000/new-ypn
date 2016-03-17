<table class="tb_style_1">
    <caption>xxx</caption>
    <tr><th>rank</th><th>team</th><th>win</th><th>draw</th><th>lose</th><th>goal</th><th>lost</th><th>jsq</th><th>score</th></tr>
<?php
$i = 1;
foreach($teams as $player)
{
?>
        <tr><td><?php echo $i ?></td><td><?php echo $player['name'] ?></td><td><?php echo $player['win'] ?></td><td><?php echo $player['draw'] ?></td><td><?php echo $player['lost'] ?></td><td><?php echo $player['goals'] ?></td><td><?php echo $player['lose'] ?></td><td><?php echo $player['jingshengqiu'] ?></td><td><?php echo $player['score'] ?></td></tr>
<?php
    $i++;
}
?>
</table>