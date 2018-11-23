<?php

?>
<form method="post" action="index.php?c=BakPlayer&a=search">
<div>name:<input type="text" name="name" value="<?php echo $name ?>" x-webkit-speech="" /> birthdate:<input type="text" name="birthdate" value="<?php echo $birthdate ?>" /> <input type="submit" /></div>
</form>

<table class="tb_style_1">
    <tr><th>name</th><th>country</th><th>birthdate</th></tr>
<?php
foreach ($players as $curCollectPlayer)
{
?>
    <tr><td><?php echo $curCollectPlayer['name'] ?></td><td><?php echo $curCollectPlayer['country'] ?></td><td><?php echo $curCollectPlayer['birthday'] ?></td></tr>
<?php
}
?>

</table>