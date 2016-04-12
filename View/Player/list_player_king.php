<table class="tb_style_1">
    <caption><?php echo $title ?></caption>
    <tr><th>rank</th><th>name</th><th><?php echo $fieldText ?></th></tr>
<?php
$i = 1;
foreach($players as $curPlayer)
{
?>
        <tr><td><?php echo $i ?></td><td><?php echo $curPlayer['name'] ?></td><td><?php echo $curPlayer[$fieldName] ?></td></tr>
<?php
    $i++;
}
?>
</table>