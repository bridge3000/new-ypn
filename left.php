<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link type="text/css" rel="stylesheet" href="Css/main.css" />
        <style type="text/css">
        <!--
        body,td,th {
            font-size: 14px;
        }

        body {
        /*	background-color: #003D4C;*/
        }
        a:link {
            font-size: 14px;
            color: #333333;
            text-decoration: none;
        }

        a:visited {
            font-size: 14px;
            color: #333333;
            text-decoration: none;
        }

        a:hover {
            font-size: 14px;
            color: #ff0000;
            text-decoration: none;
        }
        -->
        </style>
        <script src="Js/jquery.js"></script>
    </head>
    <body>
<div id="cover" style="display:none;position:absolute;top:0px;left:0px;width:100%;height:100%;background-color:whitesmoke;filter:alpha(opacity=50);z-Index:999;-moz-opacity:0.5;opacity: 0.5;"></div>
<br />
<table id="menu" border="0" align="center" cellpadding="3" cellspacing="0" bgcolor="#FFFFFF">
    <tr>
		<td><a href="index.php?c=match&a=all" target="content"><img src="img/left/cal.gif" width="20" height="20" border="0" /> all</a></td>
	</tr>
    <tr>
		<td><a href="index.php?c=match&a=today" target="content"><img src="img/left/TodayMatch.gif" width="20" height="20" border="0" /> today match</a></td>
	</tr>
    <tr>
		<td><a href="index.php?c=player&a=chuchang" target="content"><img src="img/left/modify.jpg" width="20" height="20" border="0" /> chuchang</a></td>
	</tr>
    <tr>
		<td><a href="index.php?c=ypn&a=new_day" target="content" onclick="//$('#cover').show();"><img src="img/left/NewDay.gif" width="20" height="20" border="0" /> new day</a></td>
	</tr>
    <tr>
		<td><a href="index.php?c=team&a=edit" target="content"><img src="img/left/club.gif" width="20" height="20" border="0" /> myclub</a></td>
	</tr>
	<tr>
		<td><a href="#" target="content" onclick="$('#tr_italy').toggle();return false;"><img src="img/left/yj.gif" width="20" height="20" border="0" /> ItalyLeague</a></td>
	</tr>
    <tr id="tr_italy" style="display:none;">
		<td>
            <div><a href="index.php?c=team&a=list_league_rank&p=1" target="content"><img src="img/left/yj.gif" width="20" height="20" border="0" /> 积分榜</a></div>
            <div><a href="index.php?c=player&a=list_player_king&p=1,goal" target="content"><img src="img/left/yj.gif" width="20" height="20" border="0" /> 射手榜</a></div>
            <div><a href="index.php?c=player&a=list_player_king&p=1,assist" target="content"><img src="img/left/yj.gif" width="20" height="20" border="0" /> 助攻榜</a></div>
            <div><a href="index.php?c=player&a=list_player_king&p=1,tackle" target="content"><img src="img/left/yj.gif" width="20" height="20" border="0" /> 抢断榜</a></div>
        </td>
	</tr>
    
    <tr>
		<td><a href="#" target="content" onclick="$('#tr_premier').toggle();return false;"><img src="img/left/yj.gif" width="20" height="20" border="0" /> PremierLeague</a></td>
	</tr>
    <tr id="tr_premier" style="display:none;">
		<td>
            <div><a href="index.php?c=team&a=list_league_rank&p=3" target="content"><img src="img/left/yj.gif" width="20" height="20" border="0" /> 积分榜</a></div>
            <div><a href="index.php?c=player&a=list_player_king&p=31,goal" target="content"><img src="img/left/yj.gif" width="20" height="20" border="0" /> 射手榜</a></div>
            <div><a href="index.php?c=player&a=list_player_king&p=31,assist" target="content"><img src="img/left/yj.gif" width="20" height="20" border="0" /> 助攻榜</a></div>
            <div><a href="index.php?c=player&a=list_player_king&p=31,tackle" target="content"><img src="img/left/yj.gif" width="20" height="20" border="0" /> 抢断榜</a></div>
        </td>
	</tr>
    
    <tr>
        <td><a href="#" target="content" onclick="$('#tr_transfer').toggle();return false;"><img src="img/left/yj.gif" width="20" height="20" border="0" /> transfer market</a></td>
	</tr>
    
    <tr id="tr_transfer" style="display:none;">
		<td>
            <div><a href="index.php?c=player&a=buy_list&p=1" target="content"><img src="img/left/market.jpg" width="20" height="20" border="0" /> buy</a></div>
            <div><a href="transfer/selling" target="content"><img src="img/left/market.jpg" width="20" height="20" border="0" /> sell</a></div>
        </td>
	</tr>
</table>
    <!--

    <tr>
		<td><a href="managers/view/ " target="content"><img src="img/left/oicq.gif" width="20" height="20" border="0" /> 经理生涯</a></td>
	</tr>


	<tr>
		<td><a href="players/training" target="content"><img src="img/left/training.gif" width="20" height="20" border="0" /> 训练设置</a></td>
	</tr>
    	<tr>
		<td><a href="players/all" target="content"><img src="img/left/modify.jpg" width="20" height="20" border="0" /> 我的球员</a></td>
	</tr>

			<tr>
		<td><a href="news/manage" target="content"><img src="img/left/write.jpg" width="20" height="20" border="0" /> 新闻列表</a></td>
	</tr>
<tr>
		<td><a href="teams/list_journal" target="content"><img src="img/left/modify.jpg" width="20" height="20" border="0" /> 账单</a></td>
	</tr>

		<tr>
		<td><a href="teams/rank_pl" target="content"><img src="img/left/PremierLeague.gif" width="20" height="20" border="0" /> 英超积分榜</a></td>
	</tr>
	<tr>
		<td><a href="matches/uclgroups" target="content"><img src="img/left/euro.jpg" width="20" height="20" border="0" /> 欧洲冠军联赛</a></td>
	</tr>
	<tr>
		<td><a href="matches/elgroups" target="content"><img src="img/left/euro.jpg" width="20" height="20" border="0" /> 欧洲联赛</a></td>
	</tr>
		<tr>
		<td><a href="matches/friend" target="content"><img src="img/left/friend.gif" width="20" height="20" border="0" /> 友谊赛</a></td>
	</tr>
	<tr>
		<td><a href="players/goalking" target="content"><img src="img/left/star.gif" width="20" height="20" border="0" /> 英雄榜</a></td>
	</tr>
    <tr>
		<td><a href="players/create_new" target="content"><img src="img/left/market.jpg" width="20" height="20" border="0" /> 抽调</a></td>
	</tr>

    <tr>
		<td><a href="players/collect" target="content"><img src="img/left/bookmark.gif" width="20" height="20" border="0" /> 收藏的球员</a></td>
	</tr>
	<tr>
		<td><a href="honours/index" target="content"><img src="img/left/honour.jpg" width="20" height="20" border="0" /> 荣誉室</a></td>
	</tr>
	<tr>
		<td><a href="managers/logout"><img src="img/left/door.gif" width="20" height="20" border="0" /> 注销退出</a></td>
	</tr>

-->
    </body>
</html>