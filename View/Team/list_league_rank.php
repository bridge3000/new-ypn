<table class="tb_style_1">
    <caption>xxx</caption>
    <tr><th>rank</th><th>team</th><th>win</th><th>draw</th><th>lose</th><th>goal</th><th>lost</th><th>jsq</th><th>score</th></tr>
<?php
$i = 1;
foreach($teams as $curCollectPlayer)
{
?>
        <tr><td><?php echo $i ?></td><td><?php echo $curCollectPlayer['name'] ?></td><td><?php echo $curCollectPlayer['win'] ?></td><td><?php echo $curCollectPlayer['draw'] ?></td><td><?php echo $curCollectPlayer['lost'] ?></td><td><?php echo $curCollectPlayer['goals'] ?></td><td><?php echo $curCollectPlayer['lose'] ?></td><td><?php echo $curCollectPlayer['jingshengqiu'] ?></td><td><?php echo $curCollectPlayer['score'] ?></td></tr>
<?php
    $i++;
}
?>
</table>