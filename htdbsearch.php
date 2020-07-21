<!DOCTYPE html>
<html>
<head>
<script language="JavaScript">
function tvCastItem(unique_id) {
	document.form1.tvcastID.value=unique_id;
	document.form1.submit();
    return false;
}
function movieCastItem(unique_id) {
	document.form1.mvcastID.value=unique_id;
	document.form1.submit();
    return false;
}
</script>
<style>
a { color: black; font-family: sans-serif; text-decoration: none;} /* CSS link color */
</style>
<?php

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$cookie_client = $_COOKIE["htdb-client"];

$address = $_SERVER['REMOTE_ADDR'];

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

print "</head>\n";
print "<body bgcolor=#FFFFFF forecolor=#000000>\n";

print "<script>\n	top.document.title=\"HTDB\"\n</script>\n";

$search_term = $_REQUEST['term'];
$tvcastID = $_REQUEST['tvcastID'];
$mvcastID = $_REQUEST['mvcastID'];

if (strlen($tvcastID) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $tvcastID tvshow 0 0 0 $cookie_client";
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

print "<!-- search term '$search_term' -->\n";
print "<!-- tv cast '$tvcastID' -->\n";
print "<!-- movie cast '$mvcastID' -->\n";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

//print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\">search term = '$search_term'</font></p>\n";

$castlabel = "mvcastID";
$viewer = "htdbvideo.php?ttype=movie&ID=";
$info = "htdbvideoinfo.php?movie=";
$search_label = "Movies found containing '$search_term':";
$query = "(select mi.tconst, mf.tconst, mf.primarytitle, mf.id, mi.movie_dims from movie_files as mf, movie_info as mi where mi.description ~* '$search_term' and mi.tconst = mf.tconst) union (select tb.tconst, mf.tconst, mf.primarytitle, mf.id, mi.movie_dims from movie_info as mi, movie_files as mf, title_basics as tb where tb.title_genres ~* '$search_term' and tb.tconst = mf.tconst and mi.tconst = mf.tconst) union (select mf.tconst, mi.tconst, mf.primarytitle, mf.id, mi.movie_dims from movie_files as mf, movie_info as mi where mf.primarytitle ~* '$search_term' and mi.tconst = mf.tconst)  union (select mi.tconst, mf.tconst, mf.primarytitle, mf.id, mi.movie_dims from movie_files as mf, movie_info as mi, name_basics as nb, movie_credits as mc where nb.normalname ~* '$search_term' and mc.nconst = nb.nconst and mc.tconst = mf.tconst and mi.tconst = mf.tconst and (mc.profession = 'actor' or mc.profession = 'director') ) order by primarytitle;";
if ($search_term == "new") {
	$search_label = "Newest Movies:";
	$query = "select distinct mi.tconst, mf.tconst, mf.primarytitle, mf.id, mi.movie_dims, mf.stamp from movie_files as mf, movie_info as mi where mi.tconst = mf.tconst order by mf.stamp desc limit 45;";
}
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
print "<!-- rows = '$rows' -->\n";
print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>$search_label</b></font></p>\n";
print "<hr>\n";
if ($rows > 0) {
	print "<table width=100%>\n";
	print "<tr>";
	$counter = 0;
	for ($row = 0; $row < $rows; $row++ ) {
		$record = pg_fetch_row($result, $row);
		
		$tconst = $record[1];
		$title = $record[2];
		$movieid = $record[3];
		$movie_dims = $record[4];
		
		$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
//		$tvcast_path = "<a href=\"http://$server/htdb/htdbsearch.php?$castlabel=$movieid\"><image src=\"http://$server/htdb/tvcast.png\" width=$icon_size height=$icon_size></a>";
		$tvcast_path = "<input type=\"image\" src=\"http://$server/htdb/tvcast.png\" title=\"Cast Movie\" onclick=\"javascript:movieCastItem('$movieid');\" name=\"Cast Movie\" width=$icon_size height=$icon_size>";
		$tvshowref = "<a href=\"http://$server/htdb/$viewer$movieid\" $link_target>";
		$tvinforef = "<a href=\"http://$server/htdb/$info$movieid\" target=\"_blank\">";
		print "<td width=\"140px\" height=\"200px\" align=middle valign=top>$tvshowref$image_path</a><br><font face=\"sans-serif\" size=4 color=>$tvinforef$title</a></font>&nbsp; $tvcast_path</td>";
		$counter = $counter+1;
		if ($counter == 5) {
			$counter = 0;
			print "</tr>\n";
			print "<tr>";
		}
	}
	print "</tr>\n";
	print "</table>\n";
}

$castlabel = "tvcastID";
$viewer = "htdbvideo.php?ttype=tvshow&ID=";
$info = "htdbvideoinfo.php?tvshow=";

$search_label = "TV Shows found containing '$search_term':";
$query = "(select distinct tf.tconst, tf.primarytitle||' - s'||tf.season||'e'||tf.episode as label, mi.movie_dims, tf.id from tv_files as tf, movie_info as mi where mi.description ~* '$search_term' and mi.tconst = tf.tconst) union (select distinct tf.tconst, tf.primarytitle||' - s'||tf.season||'e'||tf.episode as label, mi.movie_dims, tf.id from tv_files as tf, movie_info as mi where tf.primarytitle ~* '$search_term' and mi.tconst = tf.tconst) union (select distinct tf.tconst, tf.primarytitle||' - s'||tf.season||'e'||tf.episode as label, mi.movie_dims, tf.id from movie_info as mi, tv_files as tf, title_basics as tb where tb.title_genres ~* '$search_term' and tb.tconst = tf.tconst and mi.tconst = tf.tconst) order by label";

if ($search_term == "new") {
	$search_label = "Newest TV Shows:";
	$query = "select distinct tf.tconst, tf.primarytitle||' - s'||tf.season||'e'||tf.episode as label, mi.movie_dims, tf.id, tf.tconst, tf.stamp from tv_files as tf, movie_info as mi where mi.tconst = tf.tconst order by tf.stamp desc limit 45;";
}
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
print "<!-- rows = '$rows' -->\n";
print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>$search_label</b></font></p>\n";
print "<hr>\n";
if ($rows > 0) {
	print "<table width=100%>\n";
	print "<tr>";
	$counter = 0;
	for ($row = 0; $row < $rows; $row++ ) {
		$record = pg_fetch_row($result, $row);
		
		$tconst = $record[0];
		$title = $record[1];
		$movie_dims = $record[2];
		$showid = $record[3];
		
		$image_path = "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >";
		$tvcast_path = "<input type=\"image\" src=\"http://$server/htdb/tvcast.png\" title=\"Cast TV Show\" onclick=\"javascript:tvCastItem('$showid');\" name=\"Cast TV Show\" width=$icon_size height=$icon_size>";
		$tvshowref = "<a href=\"http://$server/htdb/$viewer$showid\" >";
		$tvinforef = "<a href=\"http://$server/htdb/$info$showid\" target=\"_blank\">";
		print "<td width=\"140px\" height=\"200px\" align=middle valign=top>$tvshowref$image_path</a><br><font face=\"sans-serif\" size=4 color=>$tvinforef$title</a></font>&nbsp; $tvcast_path</td>";
		$counter = $counter+1;
		if ($counter == 5) {
			$counter = 0;
			print "</tr>\n";
			print "<tr>";
		}
	}
	print "</tr>\n";
	print "</table>\n";
}

$viewer="htdbtvshowinfo.php?tvshow=";
$castlabel = "";


if ($search_term != "new") {
	$query = "(select ti.starttime, ti.endtime, ti.channelid, ti.title, ti.id, tci.callname from tv_info as ti, tv_channel_info as tci where (ti.endtime > now() and ti.starttime <= now()+'168 hours') and ti.title ~* '$search_term' and ti.channelid = tci.channelid) union (select ti.starttime, ti.endtime, ti.channelid, ti.title, ti.id, tci.callname from tv_info as ti, tv_channel_info as tci where (ti.endtime > now() and ti.starttime <= now()+'168 hours') and ti.description ~* '$search_term' and ti.channelid = tci.channelid) union (select ti.starttime, ti.endtime, ti.channelid, ti.title, ti.id, tci.callname from tv_info as ti, tv_channel_info as tci where (ti.endtime > now() and ti.starttime <= now()+'168 hours') and ti.category ~* '$search_term' and ti.channelid = tci.channelid) order by starttime, channelid;";
	print "<!-- query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	print "<!-- rows = '$rows' -->\n";
	print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>TV Schedule items found containing '$search_term':</b></font></p>\n";
	print "<hr>\n";
	if ($rows > 0) {
		print "<table width=100%>\n";
		print "<tr>";
		$counter = 0;
		for ($row = 0; $row < $rows; $row++ ) {
			$record = pg_fetch_row($result, $row);

			$starttime = $record[0];
			$endtime = $record[1];
			$channelid = $record[2];
			$title = $record[3];
			$tvshowID = $record[4];
			$poster = $record[5];
			$movie_dims = "width=\"120\" height=\"90\"";

			$image_path = "<image src=\"http://$server/htdb/htdb-posters/$poster.png\" $movie_dims >";
			$tvcast_path = "&nbsp;";
			$tvshowref = "<a href=\"http://$server/htdb/$viewer$tvshowID\" target=\"_blank\">";
			print "<td width=\"140px\" height=\"200px\" align=middle valign=top >$tvshowref$image_path</a><br><font face=\"sans-serif\" size=4 color=>$tvshowref$title<br>$poster $channelid<br>$starttime</a></font>&nbsp; $tvcast_path</td>";
			$counter = $counter+1;
			if ($counter == 5) {
				$counter = 0;
				print "</tr>\n";
				print "<tr>";
			}
		}
		print "</tr>\n";
		print "</table>\n";
	}
}

print "<form method=\"post\" name=\"form1\" id=\"form1\">\n";
print "<input type=\"hidden\" id=\"term\" name=\"term\" value=\"$search_term\">\n";
print "<input type=\"hidden\" id=\"tvcastID\" name=\"tvcastID\" value=\"\">\n";
print "<input type=\"hidden\" id=\"mvcastID\" name=\"mvcastID\" value=\"\">\n";
print "</form>\n";

pg_close($htdb_conn);

?>
</body>
</html>