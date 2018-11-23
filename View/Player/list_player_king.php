<table class="tb_style_1">
    <tr><th>rank</th><th>name</th><th><?php echo $fieldText ?></th></tr>
<?php
$i = 1;
foreach($players as $curCollectPlayer)
{
?>
        <tr><td><?php echo $i ?></td><td><?php echo $curCollectPlayer['name'] ?></td><td><?php echo $curCollectPlayer[$fieldName] ?></td></tr>
<?php
    $i++;
}
?>
</table>