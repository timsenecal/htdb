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

print "<script>\n	top.document.title=\"HTDB - Music\"\n</script>\n";

$has_pencil = "yes";

$client = $_SERVER["HTTP_USER_AGENT"];
$start = strpos($client, "(");
$end = strpos($client, ")");
$len = $end-$start;
$client = substr($client, $start, $len);
$start = strpos($client, ";");
$client = substr($client, $start+1);
print "<!-- client = '$client' -->\n";

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$cookie_client = $_COOKIE["htdb-client"];

$artistid = $_GET['artistid'];
$albumid = $_GET['albumid'];
$songid = $_GET['songid'];

$playartist = $_GET['playlistartist'];
$playalbum = $_GET['playlistalbum'];
$playsong = $_GET['playlistsong'];

print "<!-- artistid = '$artistid' -->\n";
print "<!-- albumid = '$albumid' -->\n";
print "<!-- songid = '$songid' -->\n";


$musiccastID = $_GET['musiccastID'];
print "<!-- musiccastid = '$musiccastid' -->\n";

if (strlen($musiccastID) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $musiccastID music";
//	print "<p>command = '$cast_cmd'</p>\n";
	$output = "";
	$return_value = "";
	$result = exec($cast_cmd, $output, $return_value);
//	print "<p>return = '$return_value'</p>\n";
//	print_r ($output);
}

$link_target = "";
$icon_size = "24";
if (strpos($client, "Android") > 0) {
	$link_target = "target=\"_blank\" ";
	$icon_size = "32";
}

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

if (strlen($playartist) > 0) {
	$query = "select folder_id, folder, id from music_files where artistid = $playartist order by id; ";
	print "<!-- playlist artist = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){	
		$record = pg_fetch_row($result, $row);
		$plfolderid = $record[0];
		$plfolder = $record[1];
		$plsongid = $record[2];
		
		$query2 = "insert into playlist(label, client, ttype, fileid, folderid, folder) values ('Music', '$cookie_client', 'music', '$plsongid', '$plfolderid', '$plfolder');";
		
		print "<!-- playlist add = '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
	}
}

if (strlen($playalbum) > 0) {
	$query = "select folder_id, folder, id from music_files where albumid = $playalbum order by id; ";
	print "<!-- playlist album = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){	
		$record = pg_fetch_row($result, $row);
		$plfolderid = $record[0];
		$plfolder = $record[1];
		$plsongid = $record[2];
		
		$query2 = "insert into playlist(label, client, ttype, fileid, folderid, folder) values ('Music', '$cookie_client', 'music', '$plsongid', '$plfolderid', '$plfolder');";
		
		print "<!-- playlist add = '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
	}
}

if (strlen($playsong) > 0) {
	$query = "select folder_id, folder, id from music_files where id = $playsong order by id; ";
	print "<!-- playlist song = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){	
		$record = pg_fetch_row($result, $row);
		$plfolderid = $record[0];
		$plfolder = $record[1];
		$plsongid = $record[2];
		
		$query2 = "insert into playlist(label, client, ttype, fileid, folderid, folder) values ('Music', '$cookie_client', 'music', '$plsongid', '$plfolderid', '$plfolder');";
		
		print "<!-- playlist add = '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
	}
}

$query = "select can_edit from settings where client = '$client';";
if (strlen($cookie_client) > 0) {
	$query = "select can_edit from settings where label = '$cookie_client';";
}
//print "<p> query = '$query' </p>\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
$num_folders = $rows;
for ($row = 0; $row < $rows; $row++ ){	
	$record = pg_fetch_row($result, $row);
	$has_pencil = $record[0];
}

if (strlen($artistid) > 0) {
	if (strlen($albumid) > 0) {
		if (strlen($songid) > 0) {
			$query = "select artist, albumtitle, songtitle, filename from music_files where id = '$songid' order by albumtitle;";
			print "<!-- song level query = '$query' -->\n";
			$result = pg_query($htdb_conn, $query);
			$rows = pg_num_rows($result);
			
			$record = pg_fetch_row($result, 0);
			$artist = $record[0];
			$albumtitle = $record[1];
			$songtitle = $record[2];
			$filename = $record[3];
			
			$query2 = "select folder_id, folder, poster from music_files where albumid = '$albumid' limit 1;";
			print "<!-- poster query '$query2' -->\n";
			$result2 = pg_query($htdb_conn, $query2);
			$record2 = pg_fetch_row($result2, 0);
			
			$folderid = $record2[0];
			$folder = $record2[1];
			$poster = $record2[2];
			
			$query_data = "select web_path from folders where id = '$folderid';";
			print "<!-- folder query = '$query_data' -->\n";
			$result_data = pg_query($htdb_conn, $query_data);
			$record_data = pg_fetch_row($result_data, 0);
			$basefolder = $record_data[0];
			print "<table>\n";
			
			$image_path = "<image src=\"http://$server$basefolder$folder$poster\" width=\"120\" height=\"120\" >";
			$href_link = "<a href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid\">";
			
			$audio_data = "<audio controls>\n<source src=\"http://$server$basefolder$folder$filename\" type=\"audio/mpeg\">\nYour browser does not support the audio element.</audio>";
			
			print "<tr><td width=\"140px\" height=\"170px\" align=middle>$href_link$image_path</a></td><td align=left width=95%><font face=\"sans-serif\" size=6 >$artist - $albumtitle - $songtitle</td></tr>\n";
			print "<tr><td width=\"140px\" height=\"170px\" align=middle>&nbsp;</td><td align=left width=95%><font face=\"sans-serif\" size=6 >$audio_data</td></tr>\n";
			print "</table>\n";
		
		} else {
			$query = "select distinct artist, albumtitle from music_files where artistid = '$artistid' and albumid = '$albumid' order by albumtitle;";
			print "<!-- first album level query = '$query' -->\n";
			$result = pg_query($htdb_conn, $query);
			$rows = pg_num_rows($result);
			print "<table>\n";
			for ($row = 0; $row < $rows; $row++ ){
				$record = pg_fetch_row($result, $row);
				$artist = $record[0];
				$albumtitle = $record[1];
			}

			$query2 = "select folder_id, folder, poster from music_files where albumid = '$albumid' limit 1;";
			print "<!-- poster query '$query2' -->\n";
			$result2 = pg_query($htdb_conn, $query2);
			$record2 = pg_fetch_row($result2, 0);

			$folderid = $record2[0];
			$folder = $record2[1];
			$poster = $record2[2];
			
			$query_data = "select web_path from folders where id = '$folderid';";
			print "<!-- folder query = '$query_data' -->\n";
			$result_data = pg_query($htdb_conn, $query_data);
			$record_data = pg_fetch_row($result_data, 0);
			$basefolder = $record_data[0];
			print "<table>\n";
			
			$image_path = "<image src=\"http://$server$basefolder$folder$poster\" width=\"120\" height=\"120\" >";
			$href_link = "<a href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid\">";
			
			$playlist_image = "<image src=\"http://$server/htdb/add.png\" width=\"32\" height=\"32\" >";
			$playlist_link = "&nbsp;&nbsp;<a title=\"add album to playlist\" href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid&albumid=$albumid&playlistalbum=$albumid\">";
			
			print "<tr><td width=\"140px\" height=\"170px\" align=middle>$href_link$image_path</a></td><td align=left><font face=\"sans-serif\" size=6 >$href_link$artist</a> - $albumtitle</td><td>$playlist_link$playlist_image</a></td></tr>\n";
			
			$query_album = "select id, track, songtitle, runtime, filename, folder from music_files where albumid = '$albumid' order by filename;";
			print "<!-- second album level query = '$query_album' -->\n";
			$result = pg_query($htdb_conn, $query_album);
			$rowsa = pg_num_rows($result);
			print "<table>\n";
			for ($rowa = 0; $rowa < $rowsa; $rowa++ ){
				$recorda = pg_fetch_row($result, $rowa);
				$songid = $recorda[0];
				$albumtrack = $recorda[1];
				$songtitle = $recorda[2];
				$runtime = $recorda[3];
				$filename = $recorda[4];
				$folder = $recorda[5];
				
				$playlist_image = "<image src=\"http://$server/htdb/add.png\" width=\"32\" height=\"32\" >";
				$playlist_link = "<a title=\"add song to playlist\" href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid&albumid=$albumid&playlistsong=$songid\">";
				
				$image_path = "&nbsp;";
				$href_link = "<a href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid\">";
				$audio_data = "<audio controls>\n<source src=\"http://$server$basefolder$folder$filename\" type=\"audio/mpeg\">\nYour browser does not support the audio element.</audio>";
				
//				print "<tr><td width=\"140px\" height=\"30px\" align=middle>$hreflink$image_path</a></td><td align=left><font face=\"sans-serif\" size=6 >$albumtrack - $songtitle</td><td>$audio_data</td><td>$playlist_link$playlist_image</a></td></tr>\n";
				print "<tr><td colspan=2 align=left><font face=\"sans-serif\" size=6 >&nbsp;$albumtrack - $songtitle</td><td>$audio_data</td><td>$playlist_link$playlist_image</a></td></tr>\n";
			}
		}
		
		print "</table>\n";
	} else {
		$query = "select distinct artist, albumtitle, albumid from music_files where artistid = '$artistid' order by albumtitle;";
		print "<!-- artist level query = '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
		$rows = pg_num_rows($result);
		print "<table>\n";
		for ($row = 0; $row < $rows; $row++ ){
			$record = pg_fetch_row($result, $row);
			$artist = $record[0];
			$albumtitle = $record[1];
			$albumid = $record[2];
			
			print "<!-- $row, $artist, $albumtitle, $albumid -->\n";

			$query2 = "select folder_id, folder, poster from music_files where albumid = '$albumid' limit 1;";
			print "<!-- poster query '$query2' -->\n";
			$result2 = pg_query($htdb_conn, $query2);
			$record2 = pg_fetch_row($result2, 0);
			
			$folderid = $record2[0];
			$folder = $record2[1];
			$poster = $record2[2];
			
			$query_data = "select web_path from folders where id = '$folderid';";
			print "<!-- folder query = '$query_data' -->\n";
			$result_data = pg_query($htdb_conn, $query_data);
			$record_data = pg_fetch_row($result_data, 0);
			$basefolder = $record_data[0];
			
			$playlist_image = "<image src=\"http://$server/htdb/add.png\" width=\"32\" height=\"32\" >";
			
			$image_path = "<image src=\"http://$server$basefolder$folder$poster\" width=\"120\" height=\"120\" >";
			if ($rows > 1) {
				$playlist_link = "&nbsp;&nbsp;<a title=\"add album to playlist\" href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid&playlistalbum=$albumid\">";
				$href_link = "<a href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid&albumid=$albumid\">";
//				print "<tr><td width=\"140px\" height=\"170px\" align=middle>$href_link$image_path</a></td><td align=left width=95%><font face=\"sans-serif\" size=6 >$href_link $artist - $albumtitle</a></td><td>$playlist_link$playlist_image</a></td></tr>\n";
				print "<tr><td width=\"140px\" height=\"170px\" align=middle>$href_link$image_path</a></td><td align=left><font face=\"sans-serif\" size=6 >$href_link $artist - $albumtitle</a></td><td>$playlist_link$playlist_image</a></td></tr>\n";
			} else {
				$playlist_link = "&nbsp;&nbsp;<a title=\"add album to playlist\" href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid&playlistalbum=$albumid\">";
			
				$href_link = "<a href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid\">";
				print "<tr><td width=\"140px\" height=\"170px\" align=middle>$href_link$image_path</a></td><td align=left><font face=\"sans-serif\" size=6 >$href_link $artist</a> - $albumtitle</td><td>$playlist_link$playlist_image</a></td></tr>\n";
			}
		}
		if ($rows == 1) {
			$query_album = "select id, track, songtitle, runtime, filename, folder from music_files where albumid = '$albumid' order by filename;";
			print "<!-- artist level only one album level query = '$query_album' -->\n";
			$result = pg_query($htdb_conn, $query_album);
			$rowsa = pg_num_rows($result);
			print "<table>\n";
			for ($rowa = 0; $rowa < $rowsa; $rowa++ ){
				$recorda = pg_fetch_row($result, $rowa);
				$songid = $recorda[0];
				$albumtrack = $recorda[1];
				$songtitle = $recorda[2];
				$runtime = $recorda[3];
				$filename = $recorda[4];
				$folder = $recorda[5];
				
				$playlist_image = "<image src=\"http://$server/htdb/add.png\" width=\"32\" height=\"32\" >";
				$playlist_link = "<a title=\"add song to playlist\" href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid&albumid=$albumid&playlistsong=$songid\">";
				
				$image_path = "&nbsp;";
				$href_link = "<a href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid\">";
				$audio_data = "<audio controls>\n<source src=\"http://$server$basefolder$folder$filename\" type=\"audio/mpeg\">\nYour browser does not support the audio element.</audio>";
				
//				print "<tr><td width=\"140px\" height=\"30px\" align=middle>$hreflink$image_path</a></td><td align=left ><font face=\"sans-serif\" size=6 >$albumtrack - $songtitle</td><td>$audio_data</td><td></a>$playlist_link$playlist_image</td></tr>\n";
				print "<tr><td colspan=2 align=left ><font face=\"sans-serif\" size=6 >&nbsp;$albumtrack - $songtitle</td><td>$audio_data</td><td></a>$playlist_link$playlist_image</td></tr>\n";
			}


		}
		print "</table>\n";
	}
}
else {

	$query = "select distinct artistid, artist from music_files order by artist;";
	print "<!-- bottom level query = '$query' -->\n";

	//print "<p> query = '$query' </p>\n";

	$result = pg_query($htdb_conn, $query);

	$rows = pg_num_rows($result);

	//print "<p> rows = '$rows' </p>\n";

	$num_folders = $rows;

	print "<table>\n";
	for ($row = 0; $row < $rows; $row++ ){	

		$record = pg_fetch_row($result, $row);

		$artistid = $record[0];
		$artist = $record[1];
		
		$query2 = "select f.web_path||mf.folder||mf.poster from folders as f, music_files as mf where mf.artistid = $artistid and mf.folder_id = f.id order by mf.albumtitle limit 1;;";
		print "<!-- poster query '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
		$record2 = pg_fetch_row($result2, 0);
		$poster = $record2[0];
		
		$playlist_image = "<image src=\"http://$server/htdb/add.png\" width=\"32\" height=\"32\" >";
		$playlist_link = "&nbsp;&nbsp;<a title=\"add artist to playlist\" href=\"http://$server/htdb/htdbmusic.php?playlistartist=$artistid\">";
		
		$image_path = "<image src=\"http://$server$poster\" width=\"90\" height=\"90\" >";
		$href_link = "<a href=\"http://$server/htdb/htdbmusic.php?artistid=$artistid\">";
		print "<tr><td width=\"140px\" height=\"30px\" align=middle>$hreflink$image_path</a></td><td align=left><font face=\"sans-serif\" size=6 color=black>$href_link$artist</a></font></td><td>$playlist_link$playlist_image</a></td></tr>\n";
	}
	print "</table>\n";
}
pg_close($htdb_conn);

?>
</body>
</html>
