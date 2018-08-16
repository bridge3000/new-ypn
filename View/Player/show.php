
<table class="table table-striped">
	<tr><th>姓名</th><td><?=$curPlayer->name?></td></tr>
	<tr>
		<th>号码</th>
		<td>
			<select id="player_no" class="form-control">
			<?php foreach($canUsedNos as $no): ?>
				<option value="<?=$no?>" <?=(($no==$curPlayer->ShirtNo)?'selected':'')?>><?=$no?></option>
			<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr><th>年龄</th><td><?=$curPlayer->birthday?></td></tr>
	<tr><th>工资</th><td><?=$curPlayer->salary?>W / 周</td></tr>
	<tr><th>合同</th><td><?=date('Y.m.d', strtotime($curPlayer->ContractBegin))?> - <?=date('Y.m.d', strtotime($curPlayer->ContractEnd))?><button type="button" id="btnContinue">续约</button></td></tr>
	<tr id="continue_contract" style="display:none"><th></th><td><input type="text"  /> 个月 </td></tr>
</table>

<button class="btn btn-info" type="button" onclick="history.back()">返回上一页</button>

<script>
	$("#player_no").change(function(){
		var postData = {
			player_id: <?=$curPlayer->id?>,
			shirt_no: $(this).val()
		};
		
		$.post("/player/ajax_change_shirt_no", postData, function(response){
			if(response.code == 1)
			{
				alert('修改成功');
			}
		}, 'json');
	});
	
	$("#btnContinue").click(function(){
		$("#continue_contract").toggle();
	});
</script>