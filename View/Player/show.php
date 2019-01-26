<div id="MessageDiv" class="alert alert-danger" role="alert" style="display:none;"></div>
<table class="table table-striped">
	<tr><th>照片</th><td><img src="<?=  MainConfig::STATIC_URL.$curPlayer->ImgSrc?>" /></td></tr>
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
	<tr><th>工资</th><td><span id="spanSalary"><?=$curPlayer->salary?></span>W / 周</td></tr>
	
	<tr><th>身高</th><td><?=$curPlayer->height?></td></tr>
	<tr><th>抢点</th><td><?=$curPlayer->qiangdian?></td></tr>
	<tr><th>头球</th><td><?=$curPlayer->header?></td></tr>
	
	<tr>
		<th>合同</th>
		<td>
			<span id="spanContractBegin"><?=date('Y.m.d', strtotime($curPlayer->ContractBegin))?></span> - <span id="spanContractEnd"><?=date('Y.m.d', strtotime($curPlayer->ContractEnd))?></span>
			<button type="button" id="btnShowContinue" class="btn btn-danger">续约</button>
		</td>
	</tr>
	<tr><th>拼抢</th><td><div id="sliderPinqiang"></div></td></tr>
	<tr><th>射门欲望</th><td><div id="sliderShotDesire"></div></td></tr>
	<tr><th>范围</th><td><div id="sliderScope"></div></td></tr>
	
	<tr id="continue_contract" style="display:none">
		<th></th>
		<td>
			<input id="target_salary" type="text" value="<?=$curPlayer->salary?>" style="width:40px"  />W/月 
			<input id="target_month" type="text" value="6" style="width:40px"  /> 个月
			<button id="btnDoContinue" type="button" class="btn btn-primary">提交</button>
		</td>
	</tr>
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
				showMessage('success', "修改成功");
			}
		}, 'json');
	});
	
	function showMessage(type, text)
	{
		$("#MessageDiv").fadeIn().attr("class", "alert alert-"+type).text(text);
		setTimeout(function(){
			$("#MessageDiv").fadeOut()
		}, 5000);
	}
	
	$("#btnShowContinue").click(function(){
		$("#continue_contract").toggle();
	});
	
	$("#btnDoContinue").click(function(){
		var targetSalary = $("#target_salary").val();
		var postData = {
			player_id: <?=$curPlayer->id?>,
			target_month: $("#target_month").val(),
			target_salary: targetSalary
		};
		
		$.post("/player/ajax_continue_contract", postData, function(response){
			if(response.code == 1)
			{
				showMessage('success', "续约成功");
				
				$("#spanSalary").text(targetSalary);
				$("#spanContractBegin").text(response.data.contract_begin);
				$("#spanContractEnd").text(response.data.contract_end);
			}
			else
			{
				showMessage('danger', "续约失败,想要"+response.data.expected_salary+"W/周");
			}
		}, 'json');
	});

	$("#sliderPinqiang").slider({
		value: <?=$curPlayer->pinqiang?>,
		stop: function( event, ui ) {
			console.log(ui.value);
			var postData = {
				player_id: <?=$curPlayer->id?>,
				pinqiang: ui.value
			};
			
			$.post("/player/ajax_set_pinqiang", postData, function(response){
			}, 'json');
		}
	});
	
	$("#sliderShotDesire").slider({
		value: <?=$curPlayer->ShotDesire?>,
		stop: function( event, ui ) {
			var postData = {
				player_id: <?=$curPlayer->id?>,
				shot_desire: ui.value
			};
			
			$.post("/player/ajax_set_shot_desire", postData, function(response){
			}, 'json');
		}
	});
		
	$("#sliderScope").slider({
		value: <?=$curPlayer->scope?>,
		stop: function( event, ui ) {
			var postData = {
				player_id: <?=$curPlayer->id?>,
				scope: ui.value
			};
			
			$.post("/player/ajax_set_scope", postData, function(response){
			}, 'json');
		}
	});
</script>