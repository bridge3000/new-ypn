<?php
?>
<div style="float:left;">
    <table class="tb_style_1">
        <tr><th>name</td><td><?php echo $curTeam['name'] ?></td></tr>
        <tr><th>money</td><td><?php echo $curTeam['money'] ?></td></tr>
        <tr><th></td><td></td></tr>
        <tr><th></td><td></td></tr>
    </table>
</div>

<div style="float:left; margin-left: 50px;">
    <table class="tb_style_1">
    <tr><th>no</th><th>name</th><th>birthday</th><th>edit</th><th>del</th></tr>
<?php
foreach ($players as $n)
{
?>
    <tr>
        <td><?php echo $n['ShirtNo'] ?></td>
        <td><a href="index.php?c=BakPlayer&a=show&p=<?php echo $n['id'] ?>"><?php echo $n['name'] ?></a></td>
        <td><?php echo $n['birthday'] ?></td>
        <td>edit</td>
        <td>del</td>
    </tr>
<?php
}
?>
    </table>
    
</div>