<table class="tb_style_1">
<?php foreach ($news as $curCollectPlayer): ?>
    <tr>
		<td><img src="<?=MainConfig::STATIC_URL.$curCollectPlayer['ImgSrc']?>"></td>
		<td><?=$curCollectPlayer['content']?></td>
		<td><?=$curCollectPlayer['PubTime']?></td>
	</tr> 
<?php endforeach; ?>
</table>