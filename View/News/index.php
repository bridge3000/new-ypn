<table class="tb_style_1">
<?php foreach ($news as $curPlayer): ?>
    <tr>
		<td><img src="<?=MainConfig::BASE_URL.$curPlayer['ImgSrc']?>"></td>
		<td><?=$curPlayer['content']?></td>
		<td><?=$curPlayer['PubTime']?></td>
	</tr> 
<?php endforeach; ?>
</table>