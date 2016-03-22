<table class="tb_style_1">
    <caption>xxx</caption>
    <tr><th>rank</th><th>team</th><th>win</th><th>draw</th><th>lose</th><th>goal</th><th>lost</th><th>jsq</th><th>score</th></tr>
<?php
$i = 1;
foreach($teams as $n)
{
?>
        <tr><td><?php echo $i ?></td><td><?php echo $n['name'] ?></td><td><?php echo $n['win'] ?></td><td><?php echo $n['draw'] ?></td><td><?php echo $n['lost'] ?></td><td><?php echo $n['goals'] ?></td><td><?php echo $n['lose'] ?></td><td><?php echo $n['jingshengqiu'] ?></td><td><?php echo $n['score'] ?></td></tr>
<?php
    $i++;
}
?>
</table>