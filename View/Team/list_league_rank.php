<table class="tb_style_1">
    <caption>xxx</caption>
    <tr><th>rank</th><th>team</th><th>win</th><th>draw</th><th>lose</th><th>goal</th><th>lost</th><th>jsq</th><th>score</th></tr>
<?php
$i = 1;
foreach($teams as $curPlayer)
{
?>
        <tr><td><?php echo $i ?></td><td><?php echo $curPlayer['name'] ?></td><td><?php echo $curPlayer['win'] ?></td><td><?php echo $curPlayer['draw'] ?></td><td><?php echo $curPlayer['lost'] ?></td><td><?php echo $curPlayer['goals'] ?></td><td><?php echo $curPlayer['lose'] ?></td><td><?php echo $curPlayer['jingshengqiu'] ?></td><td><?php echo $curPlayer['score'] ?></td></tr>
<?php
    $i++;
}
?>
</table>