<div class="jumbotron"><?=$allMatchHtml?></div>

<div id="myModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">新闻</h4>
      </div>
		<div class="modal-body">
			<div style="text-align:center"><img id="new_img" /></div>
			<div id="news_content" style="text-align:center"></div>
		</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button id="showNextBtn" type="button" class="btn btn-primary">查看下一条</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
	var news = <?=json_encode($news)?>;
	var cdnUrl = "<?=$cdnUrl?>";
	
	if (news.length > 0)
	{
		$('#myModal').modal();
		showNextNews();
	}
	
	function showNextNews()
	{
		var curNews = news.pop();
		if(curNews)
		{
			$("#news_content").html(curNews.content);
			$("#new_img").attr('src', cdnUrl+curNews.ImgSrc);
		}
		else
		{
			$('#myModal').modal('hide');
		}
	}

	$("#showNextBtn").click(function(){
		showNextNews();
	});
</script>