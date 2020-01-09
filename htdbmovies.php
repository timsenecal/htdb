<!DOCTYPE html>
<html>
<head>
<style>
	a { color: black; font-family: sans-serif; text-decoration: none;} /* CSS link color */
</style>

</head>
<body bgcolor=#FFFFFF forecolor=#000000>

<?php 
print "<script>\n	top.document.title=\"HTDB - Movies\"\n</script>\n";
	
$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$mvcastID = $_REQUEST['mvcastID'];
//print "<!-- mvcastID = '$mvcastID' -->\n";
$playshow = $_REQUEST['playlistshow'];
$cookie_client = $_COOKIE["htdb-client"];

$has_pencil = "no";

if (strlen($mvcastID) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $mvcastID movie 0 0 0 $cookie_client";
	print "<!-- command = '$cast_cmd' -->\n";
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

$link_target = "";
$icon_size = "24";
if (strpos($client, "Android") > 0) {
	$link_target = "target=\"_blank\" ";
	$icon_size = "32";
}

$prev_spot = "9";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

if (strlen($playshow) > 0) {
	$query = "select folder_id, id from movie_files where id = $playshow order by id; ";
	print "<!-- playlist movie = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){	
		$record = pg_fetch_row($result, $row);
		$plfolderid = $record[0];
		$plmovieid = $record[2];
		
		$query2 = "insert into playlist(label, client, ttype, fileid, folderid) values ('Movies', '$cookie_client', 'movie', '$playshow', '$plfolderid');";
		
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

$query = "select filename, tconst, video, id, runtime, primarytitle from movie_files order by primarytitle;";
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);

$rows = pg_num_rows($result);

//print "<p><font color=#FF0000>number of rows: $rows</font></p>\n";

print "<table  style=\"table-layout:fixed;\" width=100%>\n";
for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);
	
//	$description = $record[4];
	$title = $record[5];
	$runtime = $record[4];
	$id = $record[3];
	$video = $record[2];
	$tconst = $record[1];
	$filename = $record[0];
	$color = "lightgreen";
	
	$run_hour = intdiv($runtime, 60);
	$run_minute = $runtime % 60;
	$run_time = "$run_hour:$run_minute";
	if ($run_minute < 10) {
		$run_time = "$run_hour:0$run_minute";
	};
	
	$query_data = "select description, movie_dims from movie_info where tconst = '$tconst';";
	$result_data = pg_query($htdb_conn, $query_data);
	$record_data = pg_fetch_row($result_data, 0);
	$description = $record_data[0];
	$movie_dims = $record_data[1];
	
	print "<!-- movie dims = '$movie_dims' -->\n";
	
	$tag_title = "";
	$new_spot = substr($filename, 0, 1);
	if ($new_spot >= "A") {
		if ($new_spot != $prev_spot) {
			$tag_title = "id=\"$new_spot\"";
			$prev_spot = $new_spot;
		};
	};
	
	if (strpos($video, "mpeg4") > 0) {
		$color = "red";
	};
	
	$image_path = "&nbsp;";
	if ($tconst == "nada") {
		$color = "lightblue";
		$mininame = str_replace(".mp4", "", $filename);
		$mininame = str_replace("_", "+", $mininame);
		$mininame = str_replace(" ", "+", $mininame);
		$imdbsearch = "<a href=\"https://www.imdb.com/find?ref_=nv_sr_fn&q=$mininame&s=all\" target=\"_blank\" >$tconst</a>";
	}
	else
	{
		$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
		
		$label = "";
		$partition = "";
//		$query_data = "select genre_label from title_genre where tconst = '$tconst';";
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
	}
	
	$playlist_image = "<image src=\"http://$server/htdb/add.png\" width=\"$icon_size\" height=\"$icon_size\" >";
	$playlist_link = "<a title=\"add movie to playlist\" href=\"http://$server/htdb/htdbmovies.php?id=$tconst&playlistshow=$id\">";
	
	if ($has_pencil == "yes") {
		$pencil_path = "<a href=\"http://$server/htdb/htdbvideoinfo.php?edit=yes&movie=$id\" target=\"_blank\"><image src=\"http://$server/htdb/pencil.png\" width=$icon_size height=$icon_size></a>";
	} else {
		$pencil_path = "<a href=\"http://$server/htdb/htdbvideoinfo.php?movie=$id\" target=\"_blank\"><image src=\"http://$server/htdb/info.png\" width=$icon_size height=$icon_size></a>";
	}
	$mvcast_path = "<a href=\"http://$server/htdb/htdbmovies.php?mvcastID=$id\"><image src=\"http://$server/htdb/tvcast.png\" width=$icon_size height=$icon_size></a>";
	
	$movieref = "<a href=\"http://$server/htdb/htdbvideo.php?ttype=movie&ID=$id\" $link_target>";
	print "<tr $tag_title ><td width=\"140px\" height=\"170px\" align=middle>$movieref$image_path</a></td><td align=left width=80%><font face=\"sans-serif\" size=6 color=$color>$movieref$title</a>&nbsp;</font>$pencil_path&nbsp;$mvcast_path&nbsp;$playlist_link$playlist_image</a><br><font face=\"sans-serif\" size=4 color=#888888 >$description - $run_time</font></td></tr>\n";
}

print "</table>\n";
pg_close($htdb_conn);

?>

</body>
</html>