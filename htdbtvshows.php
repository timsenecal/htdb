<!DOCTYPE html>
<html>
<head>
<script language="JavaScript">
function deleteItem(unique_id) {
	document.form1.deleteID.value=unique_id;
	document.form1.submit();
	return false;
}
</script>
<style>
a {
	font-family: sans-serif;
	font-size: 6;
	text-decoration: none;	
}
a:visited {
  color: black;
}
a:link {
  color: black;
}
</style>
</head>
<body bgcolor=#FFFFFF forecolor=#000000>
<?php
print "<script>\n	top.document.title=\"HTDB - TV Shows\"\n</script>\n";
$has_pencil = "yes";
$embed_vids = "yes";
	
$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$start = strpos($client, "(");
$end = strpos($client, ")");
$len = $end-$start;
$client = substr($client, $start, $len);
$start = strpos($client, ";");
$client = substr($client, $start+1);
print "<!-- client = '$client' -->\n";

$cookie_client = $_COOKIE["htdb-client"];

$id = $_GET['id'];
$season = $_GET['season'];
$episode = $_GET['episode'];

$playseason = $_GET['playlistseason'];
$playshow = $_GET['playlistshow'];


$tvcastID = $_GET['tvcastID'];
print "<!-- tvcastID = '$tvcastID' -->\n";
$cast_len = strlen($tvcastID);
print "<!-- tvcastID length = '$cast_len' -->\n";

if (strlen($tvcastID) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $tvcastID tvshow 0 0 0 $cookie_client";
//	$cast_cmd = "/usr/bin/vlc --intf http --http-host 127.0.0.1 --http-port 9010 --http-password=\"vlchtdb\" \"/var/www/html/htdb/htdb-tvshows/TV_shows/Chuck/Season_01/Chuck - S01E02.mp4\" --sout \"#chromecast\" --sout-chromecast-ip=192.168.0.134 --demux-filter=demux_chromecast --play-and-exit > /var/www/html/htdb/chromecast.log 2>&1 &";
	print "<!-- cast cmd = '$cast_cmd' -->\n";
	$output = "";
	$output = shell_exec($cast_cmd);
	print "<!-- cast output = '$output' -->\n";
}

$link_target = "";
$icon_size = "24";
if (strpos($client, "Android") > 0) {
	$link_target = "target=\"_blank\" ";
	$icon_size = "32";
}

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

if (strlen($playseason) > 0) {
	list($pltconst, $plseason) = explode("-", $playseason);
	$query = "select folder_id, folder, id from tv_files where tconst = '$pltconst' and season = $plseason order by id; ";
	print "<!-- playlist season = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){	
		$record = pg_fetch_row($result, $row);
		$plfolderid = $record[0];
		$plfolder = $record[1];
		$plshowid = $record[2];
		
		$query2 = "insert into playlist(label, client, ttype, fileid, folderid, folder) values ('TV Shows', '$cookie_client', 'tvshow', '$plshowid', '$plfolderid', '$plfolder');";
		
		print "<!-- playlist add = '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
	}
}

if (strlen($playshow) > 0) {
	$query = "select folder_id, folder, id from tv_files where id = $playshow order by id; ";
	print "<!-- playlist song = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){	
		$record = pg_fetch_row($result, $row);
		$plfolderid = $record[0];
		$plfolder = $record[1];
		$plTVid = $record[2];
		
		$query2 = "insert into playlist(label, client, ttype, fileid, folderid, folder) values ('TV Shows', '$cookie_client', 'tvshow', '$playshow', '$plfolderid', '$plfolder');";
		
		print "<!-- playlist add = '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
	}
}

$query = "select can_edit, embed_vids from settings where client = '$client';";
if (strlen($cookie_client) > 0) {
	$query = "select can_edit, embed_vids from settings where label = '$cookie_client';";
}
//print "<p> query = '$query' </p>\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
$num_folders = $rows;
for ($row = 0; $row < $rows; $row++ ){	
	$record = pg_fetch_row($result, $row);
	$has_pencil = $record[0];
	$embed_vids = $record[1];
}

$query = "select folder_path, web_path from folders where folder_type = 'TV';";

//print "<p> query = '$query' </p>\n";

$result = pg_query($htdb_conn, $query);

$rows = pg_num_rows($result);

//print "<p> rows = '$rows' </p>\n";

$num_folders = $rows;

for ($row = 0; $row < $rows; $row++ ){
	
	
	$record = pg_fetch_row($result, $row);

	$folder = $record[0];
	$href = $record[1];
}

if (strlen($id) > 0) {
	if (strlen($season) == 0) {
		$query = "select distinct season from tv_files where tconst = '$id' order by season;";
		
		$result = pg_query($htdb_conn, $query);
		
		$rows = pg_num_rows($result);
		if ($rows == 1) {
			$record = pg_fetch_row($result, 0);
			$season = $record[0];
		} else {
			$row = 0;
			$record = pg_fetch_row($result, $row);

			if ($rows > 1) {
				$query2 = "select distinct tconst, primarytitle, season from tv_files where tconst = '$id' order by season;";
				print "<!-- query '$query2' -->\n";
				$result2 = pg_query($htdb_conn, $query2);

				$rows2 = pg_num_rows($result2);
				if ($rows2 > 0) {
					print "<table>\n";
					for ($row = 0; $row < $rows2; $row++ ){
						$record2 = pg_fetch_row($result2, $row);

						$tconst = $record2[0];
						$showtitle = $record2[1];
						$showseason = $record2[2];
						
						$season_label = "";
						if ($showseason > 0) {
							$season_label = " - Season $showseason";
							
							$query_count = "select count(tconst) from tv_files where tconst = '$tconst' and season = '$showseason';";
							print "<!-- query = '$query_count' -->\n";
							$result_data = pg_query($htdb_conn, $query_count);
							$record_data = pg_fetch_row($result_data, 0);
							$num_files = $record_data[0];
							if ($num_files == 1) {
								$files_label = "<font face=\"sans-serif\" size=3 color=#888888> ($num_files show)</font>";
							} else {
								$files_label = "<font face=\"sans-serif\" size=3 color=#888888> ($num_files shows)</font>";
							}
						}

						$query_data = "select movie_dims from movie_info where tconst = '$tconst';";
						$result_data = pg_query($htdb_conn, $query_data);
						$record_data = pg_fetch_row($result_data, 0);
						$movie_dims = $record_data[0];
						$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
						$href_link = "<a href=\"http://$server/htdb/htdbtvshows.php?id=$tconst&season=$showseason\">";
						
						$playlist_image = "<image src=\"http://$server/htdb/add.png\" width=\"$icon_size\" height=\"$icon_size\" >";
						$playlist_link = "<a title=\"add tv season to playlist\" href=\"http://$server/htdb/htdbtvshows.php?id=$tconst&playlistseason=$tconst-$showseason\">";
						
//						print "<tr><td width=\"140px\" height=\"170px\" align=middle>$hreflin$image_path</a></td><td align=left width=80%><font face=\"sans-serif\" size=6 >$href_link$showtitle$season_label$files_label</a></td></tr>\n";
						print "<tr><td width=\"140px\" height=\"170px\" align=middle>$hreflin$image_path</a></td><td align=left><font face=\"sans-serif\" size=6 >$href_link$showtitle$season_label$files_label</a>&nbsp;$playlist_link$playlist_image</a></td></tr>\n";
					}
					print "</table>\n";
				}
			}
		}
	}
	
	if (strlen($season) > 0) {
		$query2 = "select tconst, primarytitle, folder_id, folder, filename, season, episode, id from tv_files where tconst = '$id' and season = $season order by season, episode;";
		print "<!-- query '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
		
		$rows2 = pg_num_rows($result2);
		if ($rows2 > 0) {
			if ($season == 0) {
				$record2 = pg_fetch_row($result2, 0);
				$ptitle = $record2[1];
				$title = "$ptitle";
				print "<p style=\"font-family: sans-serif; font-size: 36px\" fill=\"Black\">$title</p>\n";
			} else {
				$record2 = pg_fetch_row($result2, 0);
				$ptitle = $record2[1];
				$title = "$ptitle - Season $season";
				print "<p style=\"font-family: sans-serif; font-size: 36px\" fill=\"Black\">$title</p>\n";
			}
			print "<table>\n";
			for ($row = 0; $row < $rows2; $row++ ){
				$record2 = pg_fetch_row($result2, $row);

				$tconst = $record2[0];
				$showtitle = $record2[1];
				$folder_id = $record2[2];
				$folder = $record2[3];
				$filename = $record2[4];
				$season = $record2[5];
				$episode = $record2[6];
				$file_id = $record2[7];
				
				$epititle = "";
				$epiplot = "";
				
				if ($showtitle == "") {
					$showtitle = "Miscellaneous";
				}
				
				$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst"."s"."$season"."e"."$episode.jpg\" >";
				
				$query3 = "select title, poster, description, episode from tv_episodes where showtconst = '$tconst' and season = $season and episode = $episode;";
				print "<!-- query '$query3' -->\n";
				$result3 = pg_query($htdb_conn, $query3);
				$rows3 = pg_num_rows($result3);
				if ($rows3 > 0) {
					$record3 = pg_fetch_row($result3, 0);
					$epititle = $record3[0];
					$poster = $record3[1];
					$epiplot = $record3[2];
					$epinum = $record3[3];
					if (strlen($epiplot) > 0) {
						$epiplot = "<br><font face=\"sans-serif\" size=4 color=#AAAAAA >$epiplot</font>";
					}
					if (strlen($epititle) > 0) {
						if ($epinum > 0) {
							$epititle = "$epititle - ep$epinum";
						}
					}
					$image_path = "<image src=\"http://$server/htdb/htdb-posters/$poster\" >";
				}
				if (strlen($epititle) == 0) {
					$epinum = $episode;
					$epinum = "s"."$season"."e"."$episode";
					$epititle = "$showtitle - episode $epinum";
				}
				
				$playlist_image = "<image src=\"http://$server/htdb/add.png\" width=\"$icon_size\" height=\"$icon_size\" >";
				$playlist_link = "<a title=\"add tv show to playlist\" href=\"http://$server/htdb/htdbtvshows.php?id=$tconst&season=$season&playlistshow=$file_id\">";
				
				if ($embed_vids == "yes") {
					$link_target = "";
				} else {
					$link_target = "target=\"_blank\" ";
				}
				
				if ($has_pencil == "yes") {
					$pencil_path = "<a href=\"http://$server/htdb/htdbvideoinfo.php?edit=yes&tvshow=$file_id\" target=\"_blank\"><image src=\"http://$server/htdb/pencil.png\" width=$icon_size height=$icon_size></a>";
				} else {
					$pencil_path = "<a href=\"http://$server/htdb/htdbvideoinfo.php?tvshow=$file_id\" target=\"_blank\"><image src=\"http://$server/htdb/info.png\" width=$icon_size height=$icon_size></a>";
				}
				$tvcast_path = "<a href=\"http://$server/htdb/htdbtvshows.php?id=$tconst&season=$season&tvcastID=$file_id\"><image src=\"http://$server/htdb/tvcast.png\" width=$icon_size height=$icon_size></a>";
				
				$href_link = "<a href=\"http://$server/htdb/htdbvideo.php?ttype=tvshow&ID=$file_id\" $link_target>";
				
				print "<tr><td width=\"140px\" height=\"170px\" align=middle>$href_link$image_path</a></td><td align=left width=80%><font face=\"sans-serif\" size=6 >$href_link$epititle</a>&nbsp;$pencil_path&nbsp;$tvcast_path&nbsp;$playlist_link$playlist_image</a></font>$epiplot</td></tr>\n";
				
			}

			print "</table>\n";
		}
	}
}
else {

	$query = "select distinct tconst, primarytitle from tv_files order by primarytitle;";

	print "<!-- query = '$query' -->\n";

	$result = pg_query($htdb_conn, $query);

	$rows = pg_num_rows($result);

	//print "<p> rows = '$rows' </p>\n";

	$num_folders = $rows;

	print "<table>\n";
	for ($row = 0; $row < $rows; $row++ ){	
		
		$record = pg_fetch_row($result, $row);
		
		$tconst = $record[0];
		$showtitle = $record[1];
		
		$query_count = "select count(tconst) from tv_files where tconst = '$tconst';";
		print "<!-- query = '$query_count' -->\n";
		$result_data = pg_query($htdb_conn, $query_count);
		$record_data = pg_fetch_row($result_data, 0);
		$num_files = $record_data[0];
		
		$query_count = "select count( distinct season) from tv_files where tconst = '$tconst';";
		print "<!-- query = '$query_count' -->\n";
		$result_data = pg_query($htdb_conn, $query_count);
		$record_data = pg_fetch_row($result_data, 0);
		$num_seasons = $record_data[0];
		
		$query_data = "select description, movie_dims from movie_info where tconst = '$tconst';";
		print "<!-- query = '$query_data' -->\n";
		$result_data = pg_query($htdb_conn, $query_data);
		$record_data = pg_fetch_row($result_data, 0);
		$description = $record_data[0];
		$movie_dims = $record_data[1];

		if (strlen($showtitle) == 0) {
			$showtitle = "Miscellaneous";
		}
		
		if (strlen($description) > 0) {
			$label = "";
			$partition = "";
//			$query_data = "select genre_label from title_genre where tconst = '$tconst';";
			$query_data = "select title_genres from title_basics where tconst = '$tconst';";
			$result_data = pg_query($htdb_conn, $query_data);
			$rows_data = pg_num_rows($result_data);
			for ($rowd = 0; $rowd < $rows_data; $rowd++ ){
				$record_data = pg_fetch_row($result_data, $rowd);
				$genre = $record_data[0];
				if (strpos($genre, ",") > 0) {
					$genre = str_replace(",", ", ", $genre);
				}
				$label = "$label$partition$genre";
				$partition = ", ";
			}
			
			if (strlen($label) > 0) {
				$description = "$description - $label";
			}
			
			$description = "<br><font face=\"sans-serif\" size=4 color=#888888 >$description</font>";
		}

		$image_path = "&nbsp;";
		if ($tconst != "nada") {
			$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
		}
		
		if ($num_files == 1) {
			$files_label = "<font face=\"sans-serif\" size=3 color=#888888 >$num_files show)</font>";
		}
		else {
			$files_label = "<font face=\"sans-serif\" size=3 color=#888888 >$num_files shows)</font>";
		}
		
		if ($num_seasons == 1) {
			$season_label = "<font face=\"sans-serif\" size=3 color=#888888 >($num_seasons season, </font>";
		}
		else {
			$season_label = "<font face=\"sans-serif\" size=3 color=#888888 >($num_seasons seasons, </font>";
		}
		
//		$href_link = "<a href=\"http://$server/htdb/htdbtvshows.php?id=$tconst\" $link_target>";
		$href_link = "<a href=\"http://$server/htdb/htdbtvshows.php?id=$tconst\">";
		print "<tr><td width=\"140px\" height=\"170px\" align=middle>$href_link$image_path</a></td><td align=left width=80%><font face=\"sans-serif\" size=6 color=$color>$href_link$showtitle</a></font>$description $season_label$files_label</td></tr>\n";
	}
	print "</table>\n";
}
pg_close($htdb_conn);

?>
</body>
</html>
