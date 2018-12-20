<div class="jumbotron"><?=$allMatchHtml?></div>
<script>
	var news = <?=json_encode($news)?>;
	for(var i in news)
	{
		alert(news[i].content);
	}
	
</script>