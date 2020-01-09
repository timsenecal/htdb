<!DOCTYPE html>
<html>
<head>
<style>
	a { color: black; font-family: sans-serif; text-decoration: none;} /* CSS link color */
	
	input[type=range] {
	  height: 36px;
	  -webkit-appearance: none;
	  margin: 10px 0;
	  width: 80%;
	}
	input[type=range]::-webkit-slider-runnable-track {
	  width: 100%;
	  height: 8px;
	  cursor: pointer;
	  animate: 0.2s;
	  background: #FFFFFF;
	  border-radius: 5px;
	  border: 1px solid #000000;
	}
	input[type=range]::-webkit-slider-thumb {
	  height: 32px;
	  width: 32px;
	  border-radius: 16px;
	  background: #000000;
	  cursor: pointer;
	  -webkit-appearance: none;
	  margin-top: -12px;
	}
	input[type=range]:focus::-webkit-slider-runnable-track {
	  background: #FFFFFF;
	}
	input[type=range]::-moz-range-track {
	  width: 100%;
	  height: 10px;
	  cursor: pointer;
	  animate: 0.2s;
	  background: #FFFFFF;
	  border-radius: 5px;
	  border: 1px solid #000000;
	}
	input[type=range]::-moz-range-thumb {
	  height: 32px;
	  width: 32px;
	  border-radius: 16px;
	  background: #000000;
	  cursor: pointer;
	}
</style>
<script>
	top.document.title="HTDB - Home"
</script>

</head>
<body bgcolor=#FFFFFF forecolor=#000000>
<?php

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}


$muscastID = $_REQUEST['muscastID'];
$mvcastID = $_REQUEST['mvcastID'];
$tvcastID = $_REQUEST['tvcastID'];
$stcastID = $_REQUEST['stcastID'];
$cookie_client = $_COOKIE["htdb-client"];

//print "<!-- mvcastID = '$mvcastID' -->\n";


$has_pencil = "no";

if (strlen($muscastID) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $muscastID music";
//	print "<p>command = '$cast_cmd'</p>\n";
	$output = "";
	$return_value = "";
	$result = exec($cast_cmd, $output, $return_value);
//	print "<p>return = '$return_value'</p>\n";
//	print_r ($output);
}

if (strlen($mvcastID) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $mvcastID movie 0 0 0 $cookie_client";
//	print "<p>command = '$cast_cmd'</p>\n";
	$output = "";
	$return_value = "";
	$result = exec($cast_cmd, $output, $return_value);
//	print "<p>return = '$return_value'</p>\n";
//	print_r ($output);
}

if (strlen($stcastID) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $stcastID tvstation 0 0 0 $cookie_client";
//	print "<p>command = '$cast_cmd'</p>\n";
	$output = "";
	$return_value = "";
	$result = exec($cast_cmd, $output, $return_value);
//	print "<p>return = '$return_value'</p>\n";
//	print_r ($output);
}

if (strlen($tvcastID) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $tvcastID tvshow 0 0 0 $cookie_client";
//	print "<p>command = '$cast_cmd'</p>\n";
	$output = "";
	$return_value = "";
	$result = exec($cast_cmd, $output, $return_value);
//	print "<p>return = '$return_value'</p>\n";
//	print_r ($output);
}

$client = $_SERVER["HTTP_USER_AGENT"];

$start = strpos($client, "(");
$end = strpos($client, ")");
$len = $end-$start;
$client = substr($client, $start, $len);
$start = strpos($client, ";");
$client = substr($client, $start+1);
print "<!-- client = '$client' -->\n";
print "<!-- cookie client = '$cookie_client' -->\n";

$link_target = "";
$icon_size = "24";
if (strpos($client, "Android") > 0) {
	$link_target = "target=\"_blank\" ";
	$icon_size = "32";
}
//$link_target = "";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$has_tvshows = "no";
$has_sched = "no";
$has_movies = "no";
$has_music = "no";
$embed_vids = "yes";

$query = "select has_tvshows, has_tvsched, has_movies, has_music, embed_vids, has_chromecast from settings where client = '$client';";
if (strlen($cookie_client) > 0) {
	$query = "select has_tvshows, has_tvsched, has_movies, has_music, embed_vids, has_chromecast from settings where label = '$cookie_client';";
}

print "<!-- query = '$query' -->\n";

$result = pg_query($htdb_conn, $query);

$rows = pg_num_rows($result);

if ($rows == 0) {
	$query = "select has_tvshows, has_tvsched, has_movies, has_pics, has_music, embed_vids from settings where client = 'all';";
	print "<!-- query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
}

for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);
	
	$has_tvshows = $record[0];
	$has_sched = $record[1];
	$has_movies = $record[2];
	$has_music = $record[3];
	$embed_vids = $record[4];
	$has_chromecast = $record[5];
}

print "<!-- embed vids = '$embed_vids' -->\n";

if ($embed_vids == "yes") {
	$link_target = "";
} else {
	$link_target = "target=\"_blank\" ";
}

if ($has_chromecast == "yes") {

	print "<script>\n";
	print "function sendTVCmd(command) {\n";
	print "	var xmlhttp = new XMLHttpRequest();\n";
	print "	xmlhttp.open(\"POST\", \"http://$server/htdb/htdbsendtvcmd.php?cmd=\"+command);\n";
	print "	xmlhttp.onload = function() {\n";
	print "		if (xmlhttp.status === 200 ) {\n";
	print "			var obj = JSON.parse(xmlhttp.responseText);\n";
	print "			var slider = document.getElementById('vidSlider');\n";
	print "			slider.value = obj.ctime;\n";
	print "			var status = document.getElementById('vidState');\n";
	print "			status.textContent = obj.state;\n";
	print "		}\n";
	print "		else if (xmlhttp.status !== 200) {\n";
	print "			console.log('Request failed.  Returned status of ' + xmlhttp.status);\n";
	print "		}\n";
	print "	}\n";
	print "	xmlhttp.send();\n";
	print "	if (command == 'pl_stop') {\n";
	print "		setTimeout(function() { window.location='http://$server/htdb/htdbhome.php';},2000);\n";
	print " }\n";
	print "}\n";
	print "function sendStatusCmd() {\n";
	print "	var xmlhttp = new XMLHttpRequest();\n";
	print "	xmlhttp.open(\"POST\", \"http://$server/htdb/htdbsendtvcmd.php?cmd=pl_status\");\n";
	print "	xmlhttp.onload = function() {\n";
	print "		if (xmlhttp.status === 200 ) {\n";
	print "			var obj = JSON.parse(xmlhttp.responseText);\n";
	print "			var slider = document.getElementById('vidSlider');\n";
	print "			slider.value = obj.ctime;\n";
	print "			var status = document.getElementById('vidState');\n";
	print "			status.textContent = obj.state	;\n";
	print "			setTimeout(sendStatusCmd(), 3000);\n";
	print "		}\n";
	print "		else if (xmlhttp.status !== 200) {\n";
	print "			console.log('Request failed.  Returned status of ' + xmlhttp.status);\n";
	print "		}\n";
	print "	}\n";
	print "	xmlhttp.send();\n";
	print "}\n";
	print "function sendSliderCmd() {\n";
	print "	var xmlhttp = new XMLHttpRequest();\n";
	print "	var slider = document.getElementById('vidSlider');\n";
	print "	xmlhttp.open(\"POST\", \"http://$server/htdb/htdbsendtvcmd.php?cmd=\"+slider.value);\n";
	print "	xmlhttp.onload = function() {\n";
	print "		if (xmlhttp.status === 200 ) {\n";
	print "			var obj = JSON.parse(xmlhttp.responseText);\n";
	print "			if(obj.state == 'playing') {\n";
	print "				slider.value = obj.ctime;\n";
	print "			}\n";
	print "			setTimeout(sendStatusCmd(), 3000);\n";
	print "		}\n";
	print "		else if (xmlhttp.status !== 200) {\n";
	print "			console.log('Request failed.  Returned status of ' + xmlhttp.status);\n";
	print "		}\n";
	print "	}\n";
	print "	xmlhttp.send();\n";
	print "}\n";
	print "</script>\n";

	$vlc_status = "/var/www/html/htdb/chromecast_check.py skip > /var/www/html/htdb/chromecast_check_htdb.log";
	$output = `$vlc_status`;
	
	$label = "";
	$query = "select name, ipaddress from chromecast_devices;";
	print "<!-- device query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result, $row);
		
		$label = $record[0];
		$ipaddress = $record[1];
		
		$query2 = "select ttype, file_id, channelid, title, status, repeat, loop, ctime, runtime from chromecast_playing where ipaddress = '$ipaddress';";
		print "<!-- playing query = '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
		$rows2 = pg_num_rows($result2);
		for ($row2 = 0; $row2 < $rows2; $row2++ ){
			$record2 = pg_fetch_row($result2, $row2);
			
			$ttype = $record2[0];
			$fileid = $record2[1];
			$channelid = $record2[2];
			$title = $record2[3];
			$status = $record2[4];
			$repeat = $record2[5];
			$loop = $record2[6];
			$currenttime = $record2[7];
			$runtime = $record2[8];
			
			$item_data = $title;
			$movie_dims = "";
			$poster = "&nbsp;";
			
			$continue = "no";
			$has_slider = "no";
			$cellheight = "200";
			
			print "<!-- ttype = '$ttype' -->\n";
			
			if ($ttype == "tvstation") {
				$continue = "yes";
				$has_slider = "no";
				$cellheight = "130";
				
				$query_data = "select callname, 'width=\"120\" height=\"90\"', callname||'.png' from tv_channel_info where channelid = $channelid;";
				print "<!-- tvstation poster query = '$query_data' -->\n";
				$result_data = pg_query($htdb_conn, $query_data);
				$record_data = pg_fetch_row($result_data, 0);
				$callname = $record_data[0];
				$movie_dims = $record_data[1];
				$poster = $record_data[2];
				
				$query_name = "select title from tv_info where channelid = $channelid and starttime <= now() and endtime >= now();";
				print "<!-- tvstation current show query = '$query_name' -->\n";
				$result_data = pg_query($htdb_conn, $query_name);
				$record_data = pg_fetch_row($result_data, 0);
				$showtitle = $record_data[0];
				
				$item_data = "$callname $title \"$showtitle\"";
			}
			
			if ($ttype == "tvshow") {
				$query3 = "select filename, tconst, primarytitle, season, episode, runtime from tv_files where id = $fileid;";
				print "<!-- tvshow query = '$query3' -->\n";
				$result3 = pg_query($htdb_conn, $query3);
				$record3 = pg_fetch_row($result3, 0);
				
				$filename = $record3[0];
				$tconst = $record3[1];
				$title = $record3[2];
				$season = $record3[3];
				$episode = $record3[4];
				$dbruntime = $record3[5];
				
				$query_data = "select movie_dims from movie_info where tconst = '$tconst';";
				print "<!-- poster query = '$query_data' -->\n";
				$result_data = pg_query($htdb_conn, $query_data);
				$record_data = pg_fetch_row($result_data, 0);
				$movie_dims = $record_data[0];
				
				$query_data = "select title from tv_episodes where showtconst = '$tconst' and season = $season and episode = $episode and poster != '';";
				print "<!-- tv_episode query = '$query_data' -->\n";
				$result_data = pg_query($htdb_conn, $query_data);
				$record_data = pg_fetch_row($result_data, 0);
				$epititle = $record_data[0];
				
				$poster = "$tconst.jpg";
				
				$item_data = "$title - s$season"."e$episode - $epititle";
				
				$continue = "yes";
				$has_slider = "yes";
			}
			
			if ($ttype == "movie") {
				$query3 = "select filename, tconst, runtime from movie_files where id = $fileid;";
				print "<!-- movie query = '$query3' -->\n";
				$result3 = pg_query($htdb_conn, $query3);
				$record3 = pg_fetch_row($result3, 0);
				
				$filename = $record3[0];
				$tconst = $record3[1];
				$dbruntime = $record3[2];
				
				$query_data = "select movie_dims from movie_info where tconst = '$tconst';";
				$result_data = pg_query($htdb_conn, $query_data);
				$record_data = pg_fetch_row($result_data, 0);
				$movie_dims = $record_data[0];
				
				$poster = "$tconst.jpg";
				
				$item_data = $title;
				
				$continue = "yes";
				$has_slider = "yes";
			}
			
			print "<!-- movie dims = '$movie_dims' -->\n";
			
			if ($continue == "yes") {
				$image_path = "&nbsp;";
				if ($tconst == "nada") {
					$image_path = "&nbsp;";
				}
				else {
					$image_path = "<image src=\"http://$server/htdb/htdb-posters/$poster\" $movie_dims >";
				}
				
				if ($status == "paused") {
					$val = (int)$currenttime;
					$sec = $val%60;
					$val = $val-$sec;
					$hour = "";
					if ($val > 3600) {
						$thing = $val%3600;
						$thing = $val-$thing;
						$hour = $thing/3600;
						$hour = (string)$hour;
						if (strlen($hour) == 1) { $hour = "0$hour";};
						$hour = "$hour:";
					};
					print "<!-- hour $val, $hour -->\n";
					$min = $val/60;
					print "<!-- min $val, $min -->\n";
					print "<!-- sec $val, $sec -->\n";
					$hour = (string)$hour;
					$min = (string)$min;
					$sec = (string)$sec;
					
					if (strlen($sec) == 1) { $sec = "0$sec";};
					if (strlen($min) == 1) { $min = "0$min";};
					
					$status = "paused at $hour$min:$sec";
					
					$status = "paused";
				}
				
				
				$dbruntime = (int)$dbruntime;
				$dbruntime = $dbruntime*60;
				
				//bug fix for an error that has been fixed in chromecast_check.py
				$runtime = str_replace ("</length>" ,"" , $runtime);
				
				$mute = "<input type=\"image\" src=\"http://$server/htdb/mute.png\" onclick=\"javascript:sendTVCmd('pl_mute');\" title=\"Mute\" width=\"$icon_size\" height=\"$icon_size\" >";
				$volumeup = "<input type=\"image\" src=\"http://$server/htdb/volumeup.png\" onclick=\"javascript:sendTVCmd('pl_volume_up');\" title=\"Volume Up\" width=\"$icon_size\" height=\"$icon_size\" >";
				$volumedown = "<input type=\"image\" src=\"http://$server/htdb/volumedown.png\" onclick=\"javascript:sendTVCmd('pl_volume_down');\" title=\"Volume Down\" width=\"$icon_size\" height=\"$icon_size\" >";
				
				$play = "<input type=\"image\" src=\"http://$server/htdb/play.png\" onclick=\"javascript:sendTVCmd('pl_play');\" title=\"Play\" width=\"$icon_size\" height=\"$icon_size\" >";
				$pause = "<input type=\"image\" src=\"http://$server/htdb/pause.png\" onclick=\"javascript:sendTVCmd('pl_pause');\" title=\"Pause\" width=\"$icon_size\" height=\"$icon_size\" >";
				$stop = "<input type=\"image\" src=\"http://$server/htdb/stop.png\" onclick=\"javascript:sendTVCmd('pl_stop');\" title=\"Stop\" width=\"$icon_size\" height=\"$icon_size\" >";
				
				print "<p><font size=\"5px\" face=\"sans-serif\" color=\"black\">Now playing on $label</font></p>\n";
				print "<hr>\n";
				print "<table  style=\"table-layout:fixed;\" width=100% align=left>\n<tr>";
				print "<td width=95% height=\"$cellheight\" align=middle valign=top>$image_path<br><font face=\"sans-serif\" size=4 color=>$item_data (<span id=\"vidState\">$status</span>)</font></td></tr>\n";
				if ($has_slider == "yes") {
					print "<tr><td width=95% align=middle>$mute&nbsp;$volumedown&nbsp;$volumeup&nbsp;&nbsp;$play&nbsp;&nbsp;$pause&nbsp;&nbsp;$stop</td></tr>\n";
					print "<tr><td width=95% align=middle><div class=\"slidecontainer\"><input type=\"range\" min=\"0\" max=\"$runtime\" value=\"$currenttime\" oninput=\"javascript:sendSliderCmd()\" class=\"slider\" id=\"vidSlider\" alt=\"time\"></div></td>";
				} else {
					print "<tr><td width=95% align=middle>$mute&nbsp;$volumedown&nbsp;$volumeup&nbsp;&nbsp;$stop</td></tr>\n";
				}
				print "</tr>\n</table>\n";
				print "<p>&nbsp;</p>\n";
				
				print "\n<!-- state = '$status', repeat = '$repeat', loop '$loop', current time '$currenttime', runtime '$runtime', runtime '$dbruntime' -->\n";
			}
		}
	}
}

if ($has_slider == "yes") {
	print "<script>\n";
	print "	setTimeout(sendStatusCmd(), 3000);\n";
	print "</script>\n";
}


$recent_ttype = "";
$recent_id = 0;
$query = "select ttype, id from client_playing where client = '$client' order by stamp desc limit 5;";
if (strlen($cookie_client) > 0) {
	$query = "select ttype, id from client_playing where client = '$cookie_client' order by stamp desc limit 5;";
}
print "<!-- recents = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
print "<!-- recents count = '$rows' -->\n";
if ($rows > 0) {
	print "<p><font size=\"5px\" face=\"sans-serif\" color=\"black\">Recently Watched</font></p>\n";
	print "<hr>\n";
	
	print "<table style=\"table-layout:fixed;\" width=100% align=left>\n<tr>";
	
	for ($row = 0; $row < $rows; $row++ ){	
		$record = pg_fetch_row($result, $row);
		$recent_ttype = $record[0];
		$recent_id = $record[1];
		
		$query_data = "";
		$epilabel = "";
		$castlabel = "";
		$viewer = "";
		print "<!-- recents = '$recent_ttype', '$recent_id' -->\n";
		
		if ($recent_ttype == "movie") {
			$query_data = "select mf.primarytitle, mi.movie_dims, mf.tconst, mi.tconst from movie_info as mi, movie_files as mf where mf.id = $recent_id and mf.tconst = mi.tconst limit 1;";
			$castlabel = "mvcastID";
			$viewer = "htdbvideo.php?ttype=movie&ID=";
			$info = "htdbvideoinfo.php?movie=";
		}
		if ($recent_ttype == "music") {
		}
		if ($recent_ttype == "tvshow") {
			$query_data = "select tf.primarytitle, mi.movie_dims, tf.tconst, tf.season, tf.episode, mi.tconst from movie_info as mi, tv_files as tf where tf.id = $recent_id and tf.tconst = mi.tconst limit 1;";
			$castlabel = "tvcastID";
			$viewer = "htdbvideo.php?ttype=tvshow&ID=";
			$info = "htdbvideoinfo.php?tvshow=";
		}
		if ($recent_ttype == "tvstation") {
			$query_data = "select callname, 'width=\"120\" height=\"90\"', 'nada', channelid, '0', callname||'.png' from tv_channel_info where channelid = $recent_id;";
			$castlabel = "stcastID";
			$viewer = "htdbtvlive.php?station=";
			$info = $viewer;
		}
		
		print "<!-- recents info = '$query_data' -->\n";
		if (strlen($query_data) > 0) {
			$result_data = pg_query($htdb_conn, $query_data);
			$rows_data = pg_num_rows($result_data);
			if ($rows_data == 0) {
				if ($recent_ttype == "tvshow") {
					$query_data = "select tf.primarytitle, '', tf.tconst, tf.season, tf.episode, te.poster from tv_files as tf, tv_episodes as te where tf.id = $recent_id and te.showtconst = tf.tconst and te.episode = tf.episode and te.season = tf.season;";
					print "<!-- recents second info = '$query_data' -->\n";
					$result_data = pg_query($htdb_conn, $query_data);
				}
			}
			
			$record_data = pg_fetch_row($result_data, 0);
			$title = $record_data[0];
			$movie_dims = $record_data[1];
			$tconst = $record_data[2];
			
			$image_path = "&nbsp;";
			$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
			
			if ($recent_ttype == "tvshow") {
				$season = $record_data[3];
				$episode = $record_data[4];
				$poster = $record_data[5];
				
				if ($season > 0) {
					$epilabel = " - s$season"."e$episode";
				} else { 
					if ($episode > 0) {
						$epilabel = " - ep$episode";
					}
				}
				if ($tconst == "nada") {
					$image_path = "<image src=\"http://$server/htdb/htdb-posters/$poster\" >";
				} else {
					$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
				}
			}
			
			if ($recent_ttype == "tvstation") {
				$channelid = $record_data[3];
				$poster = $record_data[5];
				$epilabel = " - $channelid";
				$image_path = "<image src=\"http://$server/htdb/htdb-posters/$poster\" $movie_dims >";
			}
			
			print "<!-- movie dims = '$movie_dims' '$tconst' '$poster' -->\n";
			
			$tvcast_path = "<a href=\"http://$server/htdb/htdbhome.php?$castlabel=$recent_id\"><image src=\"http://$server/htdb/tvcast.png\" width=$icon_size height=$icon_size></a>";
			$tvshowref = "<a href=\"http://$server/htdb/$viewer$recent_id&current=yes\" $link_target>";
			$tvinforef = "<a href=\"http://$server/htdb/$info$recent_id&current=yes\" target=\"_blank\">";
			print "<td width=\"140px\" height=\"200px\" align=middle valign=top>$tvshowref$image_path</a><br><font face=\"sans-serif\" size=4 color=>$tvinforef$title$epilabel</a></font><br>$tvcast_path</td>";
		}
	}
	print "</tr>\n</table>\n";
	print "<p>&nbsp;</p>\n";
}

if ($has_movies == "yes") {
	print "<p><font size=\"5px\" face=\"sans-serif\" color=\"black\">New Movies</font></p>\n";
	print "<hr>\n";

	$query = "select id, primarytitle, tconst from movie_files order by stamp desc limit 5;";
	print "<!-- query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);

	print "<table  style=\"table-layout:fixed;\" width=100% align=left>\n<tr>";
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result, $row);

		$id = $record[0];
		$title = $record[1];
		$tconst = $record[2];

		$query_data = "select movie_dims from movie_info where tconst = '$tconst';";
		$result_data = pg_query($htdb_conn, $query_data);
		$record_data = pg_fetch_row($result_data, 0);
		$movie_dims = $record_data[0];

		print "<!-- movie dims = '$movie_dims' -->\n";

		$image_path = "&nbsp;";
		if ($tconst == "nada") {
			$image_path = "&nbsp;";
		}
		else {
			$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
		}

		$mvcast_path = "<a href=\"http://$server/htdb/htdbhome.php?mvcastID=$id\"><image src=\"http://$server/htdb/tvcast.png\" width=$icon_size height=$icon_size></a>";
		$movieref = "<a href=\"http://$server/htdb/htdbvideo.php?ttype=movie&ID=$id\" $link_target>";
		$inforef = "<a href=\"http://$server/htdb/htdbvideoinfo.php?movie=$id\" target=\"_blank\">";
		print "<td width=\"140px\" height=\"200px\" align=middle valign=top>$movieref$image_path</a><br><font face=\"sans-serif\" size=4 color=>$inforef$title</a></font><br>$mvcast_path</td>";
	}


	print "</tr>\n</table>\n";

	print "<p>&nbsp;</p>\n";
}

if ($has_tvshows == "yes") {
	print "<p><font size=\"5px\" face=\"sans-serif\" color=\"black\">New TV Shows</font></p>\n";
	print "<hr>\n";

	$query = "select id, primarytitle, tconst, season, episode, stamp from tv_files order by stamp desc limit 5;";
	print "<!-- query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);

	print "<table  style=\"table-layout:fixed;\" width=100% align=left>\n<tr>";
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result, $row);

		$id = $record[0];
		$title = $record[1];
		$tconst = $record[2];
		$season = $record[3];
		$episode = $record[4];

		$epilabel = "";
		if ($season > 0) {
			$epilabel = " - s$season"."e$episode";
		} else {
			$epilabel = " - ep$episode";
		}

		$query_data = "select movie_dims from movie_info where tconst = '$tconst';";
		$result_data = pg_query($htdb_conn, $query_data);
		$record_data = pg_fetch_row($result_data, 0);
		$movie_dims = $record_data[0];

		print "<!-- movie dims = '$movie_dims' -->\n";

		$image_path = "&nbsp;";
		if ($tconst == "nada") {
			$image_path = "&nbsp;";
		}
		else {
			$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
		}

		$tvcast_path = "<a href=\"http://$server/htdb/htdbhome.php?tvcastID=$id\"><image src=\"http://$server/htdb/tvcast.png\" width=$icon_size height=$icon_size></a>";
		$tvshowref = "<a href=\"http://$server/htdb/htdbvideo.php?ttype=tvshow&ID=$id\" $link_target>";
		$inforef = "<a href=\"http://$server/htdb/htdbvideoinfo.php?tvshow=$id\" target=\"_blank\">";
		print "<td width=\"140px\" height=\"200px\" align=middle valign=top>$tvshowref$image_path</a><br><font face=\"sans-serif\" size=4 color=>$inforef$title$epilabel</a></font><br>$tvcast_path</td>";
	}

	print "</tr></table>\n";
}
pg_close($htdb_conn);

?>

</body>
</html>