<?php
?>
<table class="tb_style_1">
<tr>
<?php
$i = 0;
foreach ($leagues as $k=>$l)
{
?>
    <td><a href='index.php?c=BakTeam&a=list_all&p=<?php echo $k?>'><?php echo $l ?></a></td>
<?php
    $i++;
    if ($i % 20 == 0) echo('</tr><tr>');
}
?>
</tr>
</table>
<br/><br/>
<table class="tb_style_1">
<tr>
<?php
$i = 0;
foreach ($teams as $k=>$l)
{
?>
    <td><a href='index.php?c=BakTeam&a=show&p=<?php echo $k?>'><?php echo $l ?></a></td>
<?php
    $i++;
    if ($i % 20 == 0) echo('</tr><tr>');
}
?>
</tr>
</table>
