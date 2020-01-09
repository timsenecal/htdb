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
table {
	font-family: sans-serif;
	font-size: 12px;
	border-collapse: collapse;
	width: 95%;
}

th {
	font-family: sans-serif;
	font-size: 30px;
	background-color: #dddddd;
	padding: 3px;
}

tr:nth-child(odd) {
	background-color: #dddddd;
}

a {
	font-family: sans-serif;
	font-size: 12px;
}

a:link {
  color: black;
}
</style>

<?php

$server = $_SERVER['SERVER_ADDR'];
$server_addr = $server;
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$client = $_SERVER['HTTP_USER_AGENT'];
$cookie_client = $_COOKIE['htdb-client'];

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

$save_val = $_REQUEST['save'];
$save_label = $_REQUEST['save_label'];
$edit_val = $_REQUEST['edit'];
$play_val = $_REQUEST['play'];
$pause_val = $_REQUEST['pause'];
$stop_val = $_REQUEST['stop'];
$trash_val = $_REQUEST['trash'];
$trash_item = $_REQUEST['deleteID'];
$mode_val = $_REQUEST['modeID'];

print "<!-- deleteID value '$trash_item' -->\n";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");


if (strlen($mode_val) > 0) {
	$query = "select fileid, ttype, mode, tconst, season, episode from playlist where id = '$mode_val' and client = '$cookie_client';";
	print "<!-- mode query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	print "<!-- rows = '$rows' -->\n";
	for ($row=0;$row<$rows;$row++) {
		$record = pg_fetch_row($result, $row);
		$fileid = $record[0];
		$ttype = $record[1];
		$playmode = $record[2];
		$tconst = $record[3];
		$season = $record[4];
		$episode = $record[5];
		
		if ($playmode == "sequence") {
			$query = "select id from tv_files where tconst = '$tconst' and season = '$season' and episode = '$episode';";
			print "<!-- mode query = '$query' -->\n";
			$result = pg_query($htdb_conn, $query);
			$rows = pg_num_rows($result);
			print "<!-- rows = '$rows' -->\n";
			for ($row=0;$row<$rows;$row++) {
				$record = pg_fetch_row($result, $row);
				$fileid = $record[0];
				
				$query = "update playlist set fileid = '$fileid', mode = 'item' where id = '$mode_val' and client = '$cookie_client';";
				print "<!-- mode item query = '$query' -->\n";
				$result = pg_query($htdb_conn, $query);
			}
		}
		if ($playmode == "item") {
			$query = "select tconst, season, episode from tv_files where id = '$fileid';";
			print "<!-- mode query = '$query' -->\n";
			$result = pg_query($htdb_conn, $query);
			$rows = pg_num_rows($result);
			print "<!-- rows = '$rows' -->\n";
			for ($row=0;$row<$rows;$row++) {
				$record = pg_fetch_row($result, $row);
				$tconst = $record[0];
				$season = $record[1];
				$episode = $record[2];
				
				$query = "update playlist set fileid = 0, tconst = '$tconst', season = '$season', episode = '$episode', mode = 'sequence' where id = '$mode_val' and client = '$cookie_client';";
				print "<!-- mode item query = '$query' -->\n";
				$result = pg_query($htdb_conn, $query);
			}
		}
	}
}
if (strlen($trash_item) > 0) {
	$query = "update playlist set status = 'delete' where id = '$trash_item' and client = '$cookie_client';";
	print "<!-- trash item query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}
if (strlen($trash_val) > 0) {
	$trash_val = str_replace("%20", " ", $trash_val);
	$query = "update playlist set status = 'delete' where label = '$trash_val' and client = '$cookie_client';";
	print "<!-- trash list query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}

if (strlen($save_label) > 0) {
	$save_val = str_replace("%20", " ", $save_val);
	$query = "update playlist set label = '$save_label' where label = '$save_val' and client = '$cookie_client';";
	print "<!-- save label query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}

if (strlen($play_val) > 0) {
	$clear_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=pl_empty\"";
	print "<!-- clear command = '$clear_cmd' -->\n";
	$output = `$clear_cmd`;
	$query = "select fileid, ttype, mode, tconst, season, episode from paylist where label = '$play_val' and client = '$cookie_client';";
	print "<!-- query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	print "<!-- rows = '$rows' -->\n";
	for ($row=0;$row<$rows;$row++) {
		$record = pg_fetch_row($result, $row);
		$fileid = $record[0];
		$ttype = $record[1];
		$playmode = $record[2];
		$tconst = $record[3];
		$season = $record[4];
		$episode = $record[5];
		if ($ttype == "music") {
			$query2 = "select f.folder_path||mf.folder||mf.filename as filename, pl.label, pl.fileid from music_files as mf, folders as f, playlist as pl where pl.label = '$play_val' and pl.fileid = '$fileid' and pl.fileid = mf.id and mf.folder_id = f.id order by pl.id;";
			print "<!-- query = '$query2' -->\n";
			$result2 = pg_query($htdb_conn, $query2);
			$record2 = pg_fetch_row($result2, 0);
			$filename = $record2[0];
			$filename = str_replace(" ", "%20", $filename);
			$add_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=in_enqueue&input=$filename\"";
			print "<!-- add command = '$add_cmd' -->\n";
			$output = `$add_cmd`;
		}
		if ($ttype == "movie") {
			$query2 = "select f.folder_path||mf.filename as filename, pl.label, pl.fileid from movie_files as mf, folders as f, playlist as pl where pl.label = '$play_val' and pl.fileid = '$fileid' and pl.fileid = mf.id and mf.folder_id = f.id order by pl.id;";
			print "<!-- query = '$query2' -->\n";
			$result2 = pg_query($htdb_conn, $query2);
			$record2 = pg_fetch_row($result2, 0);
			$filename = $record2[0];
			$filename = str_replace(" ", "%20", $filename);
			$add_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=in_enqueue&input=$filename\"";
			print "<!-- add command = '$add_cmd' -->\n";
			$output = `$add_cmd`;
		}
		if ($ttype == "tvshow") {
			if ($fileid == 0) {
				$query2 = "select id from tv_files where tconst = '$tconst' and season = '$season' and episode = '$episode';";
				$result2 = pg_query($htdb_conn, $query2);
				$record2 = pg_fetch_row($result2, 0);
				$fileid = $record2[0];
			}
			$query2 = "select f.folder_path||tf.folder||tf.filename as filename from tv_files as tf, folders as f where tf.id = $fileid and tf.folder_id = f.id;";
			print "<!-- query = '$query2' -->\n";
			$result2 = pg_query($htdb_conn, $query2);
			$record2 = pg_fetch_row($result2, 0);
			$filename = $record2[0];
			$filename = str_replace(" ", "%20", $filename);
			$add_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=in_enqueue&input=$filename\"";
			print "<!-- add command = '$add_cmd' -->\n";
			$output = `$add_cmd`;
		}
	}
	$play_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=pl_play\"";
	print "<!-- play command = '$play_cmd' -->\n";
	$output = `$play_cmd`;
	
	$update_cmd = "/var/www/html/htdb/update_playlist.py '$play_val' '$cookie_client' ";
	print "<!-- update command = '$update_cmd' -->\n";
	$output = `$update_cmd`;
}

if (strlen($pause_val) > 0) {
	$pause_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=pl_pause\"";
	$output = `$pause_cmd`;
}

if (strlen($stop_val) > 0) {
	$stop_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=pl_stop\"";
	$output = `$stop_cmd`;
}

print "</head>\n";
print "<body bgcolor=#FFFFFF forecolor=#000000>\n";

print "<script>\n	top.document.title=\"HTDB - Playlists\"\n</script>\n";

$query = "select distinct label from playlist where client = '$cookie_client' and status != 'delete' order by label;";
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
print "<!-- rows = '$rows' -->\n";

print "<p><font size=\"6px\" face=\"sans-serif\" color=\"black\">Playlists for $cookie_client</p>\n";
print "<hr>\n";
print "<form name=\"form3\" method=\"post\">\n";

if (strlen($edit_val) > 0) {
	print "	<INPUT TYPE=\"hidden\" NAME=\"save\" VALUE=\"$edit_val\">\n";	
}

if ($rows > 0) {
	for ($row=0;$row<$rows;$row++) {
		$record = pg_fetch_row($result, $row);
		$label = $record[0];
		
		if ($row > 0) {
			print "</table>\n";
			print "<br>\n";
		}
		$urllabel = str_replace(" ", "%20", $label);
		print "<table width=80%>\n";
		$play = "<a href=\"http://$server/htdb/htdbplaylist.php?play=$urllabel\"><image src=\"http://$server/htdb/play.png\" width=\"$icon_size\" height=\"$icon_size\" title=\"Play\"></a>";
		$pause = "<a href=\"http://$server/htdb/htdbplaylist.php?pause=$urllabel\"><image src=\"http://$server/htdb/pause.png\" width=\"$icon_size\" height=\"$icon_size\" title=\"Pause\"></a>";
		$stop = "<a href=\"http://$server/htdb/htdbplaylist.php?stop=$urllabel\"><image src=\"http://$server/htdb/stop.png\" width=\"$icon_size\" height=\"$icon_size\" title=\"Stop\"></a>";
		$pencil = "<a href=\"http://$server/htdb/htdbplaylist.php?edit=$urllabel\"><image src=\"http://$server/htdb/pencil.png\" width=\"$icon_size\" height=\"$icon_size\" title=\"Edit\"></a>";
		$trashcan = "<a href=\"http://$server/htdb/htdbplaylist.php?trash=$urllabel\"><image src=\"http://$server/htdb/trashcan.png\" width=\"$icon_size\" height=\"$icon_size\" title=\"Delete\"></a>";
		$save = "<input type=\"submit\" value=\"Save Changes\">";
		
		if ($edit_val == $label) {
			print "<tr><th colspan=2 align=left><input type=\"text\" size=\"20\" name=\"save_label\" value=\"$label\">&nbsp;$save&nbsp;$trashcan</th><th align=right colspan=2 width=6%>$play$pause$stop</th></tr>\n";
		} else {
			print "<tr><th colspan=2 align=left>$label&nbsp;$pencil&nbsp;$trashcan</th><th align=right>$play$pause$stop</th></tr>\n";
		}
		
		$query1 = "select label, client, fileid, ttype, channelid, runtime, starttime, id, season, episode, tconst, mode from playlist where client = '$cookie_client' and label = '$label' and status != 'delete' order by id;";
		print "<!-- query = '$query1' -->\n";
		$result1 = pg_query($htdb_conn, $query1);
		$rows1 = pg_num_rows($result1);
		
		print "<!-- rows = '$rows1' -->\n";
		for ($row1 = 0; $row1 < $rows1; $row1++ ) {
			$record1 = pg_fetch_row($result1, $row1);
			
//			$label = $record1[0];
			$fileid = $record1[2];
			$ttype = $record1[3];
			$itemid = $record1[7];
			$season = $record1[8];
			$episode = $record1[9];
			$tconst = $record1[10];
			$playmode = $record1[11];
			
			$modeurl = "";
			
			$item_title = "";
			if ($ttype == "music") {
				$query2 = "select artist, albumtitle, songtitle from music_files where id = $fileid;";
				print "<!-- query = '$query2' -->\n";
				$result2 = pg_query($htdb_conn, $query2);
				$rows2 = pg_num_rows($result2);
				
				if ($rows2 > 0) {
					for ($row2 = 0; $row2 < $rows2; $row2++ ) {
						$record2 = pg_fetch_row($result2, $row2);
						
						$artist = $record2[0];
						$album = $record2[1];
						$title = $record2[2];
					}
					$item_title = "$artist - $album - $title";
				}
			}
			if ($ttype == "tvshow") {
				if ($playmode == "sequence") {
					$query2 = "select id from tv_files where tconst = '$tconst' and season = $season and episode = $episode;";
					print "<!-- query = '$query2' -->\n";
					$result2 = pg_query($htdb_conn, $query2);
					$rows2 = pg_num_rows($result2);
					for ($row2 = 0; $row2 < $rows2; $row2++ ) {
						$record2 = pg_fetch_row($result2, $row2);
						$fileid = $record2[0];
					}
				}
				$query2 = "select tf.primarytitle, te.title, te.episodenum from tv_files as tf, tv_episodes as te where tf.tconst = te.showtconst and tf.season = te.season and tf.episode = te.episode and tf.id = $fileid;";
				print "<!-- query = '$query2' -->\n";
				$result2 = pg_query($htdb_conn, $query2);
				$rows2 = pg_num_rows($result2);
				
				if ($rows2 > 0) {
					for ($row2 = 0; $row2 < $rows2; $row2++ ) {
						$record2 = pg_fetch_row($result2, $row2);
						
						$show = $record2[0];
						$title = $record2[1];
						$season = $record2[2];
					}
					if ($season == $title) {
						$item_title = "$show - $season";
					} else {
						$item_title = "$show - $season - $title";
					}
				}
				
				if ($playmode == "item") {
					$modeurl = "<a href=\"http://$server/htdb/htdbplaylist.php?modeID=$itemid\"><image src=\"http://$server/htdb/modeoff.png\" width=\"$icon_size\" height=\"$icon_size\" alt=\"Mode\"></a>";
				} else {
					$modeurl = "<a href=\"http://$server/htdb/htdbplaylist.php?modeID=$itemid\"><image src=\"http://$server/htdb/modeon.png\" width=\"$icon_size\" height=\"$icon_size\" alt=\"Mode\"></a>";
				}
			}
			if ($ttype == "movie") {
				$query2 = "select primarytitle from movie_files where id = $fileid;";
				print "<!-- query = '$query2' -->\n";
				$result2 = pg_query($htdb_conn, $query2);
				$rows2 = pg_num_rows($result2);
				
				if ($rows2 > 0) {
					for ($row2 = 0; $row2 < $rows2; $row2++ ) {
						$record2 = pg_fetch_row($result2, $row2);
						
						$item_title = $record2[0];
					}
				}
			}
			
			$trashurl = "<a href=\"http://$server/htdb/htdbplaylist.php?deleteID=$itemid\"><image src=\"http://$server/htdb/trashcan.png\" width=\"$icon_size\" height=\"$icon_size\" alt=\"Delete\"></a>";
			print "<tr><td>$ttype</td><td>$item_title</td><td align=right>$modeurl $trashurl</td></tr>\n";
		}
		print "</table>\n";
	}
}
print "</form>\n";

print "<form name=\"form1\" method=\"post\">\n";
print "	<INPUT TYPE=\"hidden\" NAME=\"deleteID\" VALUE=\"\">\n";
print "	<INPUT TYPE=\"hidden\" NAME=\"deleteThing\" VALUE=\"\">\n";
print "</form>\n";
pg_close($htdb_conn);

?>
</div>
</body>
</html>
