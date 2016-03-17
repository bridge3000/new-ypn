<?php

?>
<form method="post" action="index.php?c=BakPlayer&a=search">
<div>name:<input type="text" name="name" value="<?php echo $name ?>" x-webkit-speech="" /> birthdate:<input type="text" name="birthdate" value="<?php echo $birthdate ?>" /> <input type="submit" /></div>
</form>

<table class="tb_style_1">
    <tr><th>name</th><th>country</th><th>birthdate</th></tr>
<?php
foreach ($players as $player)
{
?>
    <tr><td><?php echo $player['name'] ?></td><td><?php echo $player['country'] ?></td><td><?php echo $player['birthday'] ?></td></tr>
<?php
}
?>

</table>