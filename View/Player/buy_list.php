<?php

use Util\FormHelper;
?>

<?php if ($isTransferDay): ?>
	<div>
	<?= FormHelper::select('search_type', $searchTypes, $searchType, array('id' => 'search_type')) ?>
	</div>

	<table class="tb_style_1">
		<tr><th>name</th><th>team</th><th>pos</th><th>dirs</th><th>age</th><th>fee</th><th>salary</th><th>contract-begin</th><th>contract-end</th><th>操作</th></tr>
	<?php foreach ($players as $curCollectPlayer): ?>
			<tr>
				<td><?= $curCollectPlayer->name ?></td>
				<td><?= (($curCollectPlayer->team_id ? $teamList[$curCollectPlayer->team_id] : '自由球员')) ?></td>
				<td><?= MainConfig::$positions[$curCollectPlayer->position_id] ?></td>
				<td><?= $curCollectPlayer->LeftProperties ?>|<?= $curCollectPlayer->MidProperties ?>|<?= $curCollectPlayer->RightProperties ?></td>
				<td><?= $curCollectPlayer->getAge($nowDate) ?></td>
				<td><?= $curCollectPlayer->fee ?></td>
				<td><?= $curCollectPlayer->salary ?></td>
				<td><?= $curCollectPlayer->ContractBegin ?></td>
				<td><?= $curCollectPlayer->ContractEnd ?></td>
				<td>
					<a href="<?= MainConfig::BASE_URL ?>player/buy_apply/<?= $curCollectPlayer->id ?>"><button type="button" class="btn btn-danger">buy</button></a>
					<button name="collect_btn" type="button" player_id="<?= $curCollectPlayer->id ?>" class="btn btn-info">收藏</button>
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