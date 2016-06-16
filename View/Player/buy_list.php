<?php
use Util\FormHelper;
?>

<?php if($isTransferDay): ?>
	<div>
		<?=	FormHelper::select('search_type', $searchTypes, $searchType, array('id'=>'search_type'))?>
	</div>

	<table class="tb_style_1">
		<tr><th>name</th><th>team</th><th>pos</th><th>age</th><th>fee</th><th>salary</th><th>contract-begin</th><th>contract-end</th><th>操作</th></tr>
	<?php foreach ($players as $curPlayer): ?>
		<tr>
			<td><?=$curPlayer->name?></td>
			<td><?=(($curPlayer->team_id?$teamList[$curPlayer->team_id]:'自由球员'))?></td>
			<td><?=MainConfig::$positions[$curPlayer->position_id]?></td>
			<td><?=$curPlayer->getAge($nowDate)?></td>
			<td><?=$curPlayer->fee?></td>
			<td><?=$curPlayer->salary?></td>
			<td><?=$curPlayer->ContractBegin?></td>
			<td><?=$curPlayer->ContractEnd?></td>
			<td><a href="<?=  MainConfig::BASE_URL?>player/buy_apply/<?=$curPlayer->id?>"><button>buy</button></a></td>
		</tr> 
	<?php endforeach; ?>
	</table>

	<div class="pagination">
		<a href="<?=  MainConfig::BASE_URL?>player/buy_list/<?=$searchType?>/<?=$curPage-1?>"> &lt; </a>
	<?php for($i=1;$i<=$pageCount;$i++): ?>
		<a href="<?=  MainConfig::BASE_URL?>player/buy_list/<?=$searchType?>/<?=$i?>" class="<?=(($i==$curPage)?"active":"")?>"><?=$i?></a>
	<?php endfor; ?>
		<a href="<?=  MainConfig::BASE_URL?>player/buy_list/<?=$searchType?>/<?=$curPage+1?>"> &gt; </a>
	</div>

	<script>
		$("#search_type").change(function(){
			location.href = "<?=  MainConfig::BASE_URL?>player/buy_list/" + $(this).val();
		});
	</script>
<?php else: ?>
	<div>转会窗口关闭</div>
<?php endif; ?>
