<?php

use Util\FormHelper;
?>

<?php if ($isTransferDay): ?>
	<div>
	<?= FormHelper::select('search_type', $searchTypes, $searchType, array('id' => 'search_type')) ?>
	</div>

	<table class="tb_style_1">
		<tr><th>photo</th><th>name</th><th>team</th><th>pos</th><th>dirs</th><th>age</th><th>fee</th><th>salary</th><th>contract-begin</th><th>contract-end</th><th>操作</th></tr>
	<?php foreach ($players as $curPlayer): ?>
			<tr>
				<td><img src="/<?= $curPlayer->ImgSrc ?>" style="width:60px" /></td>
				<td><?= $curPlayer->name ?></td>
				<td><?= (($curPlayer->team_id ? $teamList[$curPlayer->team_id] : '自由球员')) ?></td>
				<td><?= MainConfig::$positions[$curPlayer->position_id] ?></td>
				<td><?= $curPlayer->LeftProperties ?>|<?= $curPlayer->MidProperties ?>|<?= $curPlayer->RightProperties ?></td>
				<td><?= $curPlayer->getAge($nowDate) ?></td>
				<td><?= $curPlayer->fee ?></td>
				<td><?= $curPlayer->salary ?></td>
				<td><?= $curPlayer->ContractBegin ?></td>
				<td><?= $curPlayer->ContractEnd ?></td>
				<td>
					<a href="<?= MainConfig::BASE_URL ?>player/buy_apply/<?= $curPlayer->id ?>"><button type="button" class="btn btn-danger">buy</button></a>
					<button name="collect_btn" type="button" player_id="<?= $curPlayer->id ?>" class="btn btn-info">收藏</button>
				</td>
			</tr> 
	<?php endforeach; ?>
	</table>

	<nav aria-label="Page navigation">
		<ul class="pagination">
			<li><a href="<?= MainConfig::BASE_URL ?>player/buy_list/<?= $searchType ?>/<?= $curPage - 1 ?>"> &lt; </a></li>
		<?php for ($i = 1; $i <= $pageCount; $i++): ?>
			<li><a href="<?= MainConfig::BASE_URL ?>player/buy_list/<?= $searchType ?>/<?= $i ?>" class="<?= (($i == $curPage) ? "active" : "") ?>"><?= $i ?></a></li>
		<?php endfor; ?>
			<li><a href="<?= MainConfig::BASE_URL ?>player/buy_list/<?= $searchType ?>/<?= $curPage + 1 ?>"> &gt; </a></li>
		</ul>
	</nav>
	<script>
		$("#search_type").change(function () {
			location.href = "<?= MainConfig::BASE_URL ?>player/buy_list/" + $(this).val();
		});
		
		$("[name='collect_btn']").click(function(){
			var $collectBtn = $(this);
			var playerId = $collectBtn.attr("player_id");
			
			$.get("/player/ajaxCollect/"+playerId, {}, function(response){
				if(response.code == 1)
				{
					$collectBtn.attr("disabled", "diabled");
					$collectBtn.text("已收藏");
				}
				else
				{
					alert("收藏失败");
				}
			}, 'json');
		});
	</script>
<?php else: ?>
	<div>转会窗口关闭</div>
<?php endif; ?>