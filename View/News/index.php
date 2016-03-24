<table class="tb_style_1">
<?php foreach ($news as $n): ?>
    <tr>
		<td><img src="<?=MainConfig::BASE_URL.$n['ImgSrc']?>"></td>
		<td><?=$n['content']?></td>
		<td><?=$n['PubTime']?></td>
	</tr> 
<?php endforeach; ?>
</table>