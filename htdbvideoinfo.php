<!DOCTYPE html>
<html>
<head>
<link rel="apple-touch-icon" sizes="128x128" href="htdb.png"> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="mobile-web-app-capable" content="yes">
<style>
a {
	font-family: sans-serif;
	font-size: 2;
	text-decoration: none;	
}
a:visited {
  color: #888888;
}
a:link {
  color: #888888;
}
</style>
<?php

$server = $_SERVER['SERVER_ADDR'];

$trashcan = isset($_REQUEST['trashcan']) ? $_REQUEST['trashcan'] : '';

$tvshowID = isset($_REQUEST['tvshow']) ? $_REQUEST['tvshow'] : '';
$movieID = isset($_REQUEST['movie']) ? $_REQUEST['movie'] : '';
$dvdID = isset($_REQUEST['dvd']) ? $_REQUEST['dvd'] : '';
$edit = isset($_REQUEST['edit']) ? $_REQUEST['edit'] : 'no';

$tconst = $_REQUEST['tconst'];
$category = $_REQUEST['category'];
$season = $_REQUEST['season'];
$episode = $_REQUEST['episode'];
$title = $_REQUEST['title'];

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

if (strlen($category) > 0) {
	$query = "update title_basics set title_genres = '$category' where tconst = '$tconst';";
	print "<!-- '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}

if (strlen($trashcan) > 0) {
	if (strlen($movieID) > 0) {
		$query = "delete from movie_files where id = '$movieID';";
		$query_home = "delete from client_playing where id = '$movieID';";
	} else {
		$query = "delete from tv_files where id = '$tvshowID';";
		$query_home = "delete from client_playing where id = '$tvshowID';";
	}
	print "<!-- '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	print "<!-- '$query_home' -->\n";
	$result = pg_query($htdb_conn, $query_home);
}

if (strlen($tconst) > 0) {
	if (strlen($tvshowID) > 0) {
		$query = "update tv_files set tconst = '$tconst', data_collected = 'no' where id = '$tvshowID';";
		print "<!-- '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
		
		//fix the tv episode and tv series info based on the new tconst
		$tv_fix_cmd = "/var/www/html/htdb/names_build_tv.py; /var/www/html/htdb/grab_tv_episodes.py; /var/www/html/htdb/tv_series_grab.py &";
		$output = shell_exec($tv_fix_cmd);
	}
	if (strlen($movieID) > 0) {
		$query = "update movie_files set tconst = '$tconst', data_collected = 'no' where id = '$movieID';";
		print "<!-- '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
		
		//grab the movie credits info for the new tconst
		$movie_fix_cmd = "/var/www/html/htdb/names_build.py; /var/www/html/htdb/movies_grab.py &";
		$output = shell_exec($movie_fix_cmd);
	}
	if (strlen($dvdID) > 0) {
		$query = "update dvd_rips set tconst = '$tconst' where id = $dvdID;";
		print "<!-- '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
	}
}

if (strlen($title) > 0) {
	if (strpos($title, "'") > 0) {
		$title = str_replace("'", "''", $title);
	}
	if (strlen($tvshowID) > 0) {
		$query = "update tv_files set primarytitle = '$title' where id = '$tvshowID';";
		print "<!-- '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
	}
	if (strlen($movieID) > 0) {
		$query = "update movie_files set primarytitle = '$title' where id = '$movieID';";
		print "<!-- '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
	}
	if (strlen($dvdID) > 0) {
		$query = "update dvd_rips set primarytitle = '$title' where id = $dvdID;";
		print "<!-- '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
	}
}

if (strlen($season) > 0) {
	if (strlen($tvshowID) > 0) {
		$query = "update tv_files set season = '$season', data_collected = 'no' where id = '$tvshowID';";
		print "<!-- '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
	}
}

if (strlen($episode) > 0) {
	if (strlen($tvshowID) > 0) {
		$query = "update tv_files set episode = '$episode', data_collected = 'no' where id = '$tvshowID';";
		print "<!-- '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
	}
}

$query = "";
if (strlen($tvshowID) > 0) {
	$query = "select tconst, filename, primarytitle, folder_id, folder, runtime, season, episode, stamp from tv_files where id = '$tvshowID';";
}

if (strlen($movieID) > 0) {
	$query = "select tconst, filename, primarytitle, folder_id, '', runtime, '', '', stamp from movie_files where id = '$movieID';";
}

if (strlen($dvdID) > 0) {
	$query = "select tconst, filename, primarytitle, '', '', '', '', '', stamp from dvd_rips where id = '$dvdID';";
}

$tconst = "nada";
$season = "0";
$episode = "0";
$folder_id = "0";
$epititle = "";
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
$num_folders = $rows;
for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);

	$tconst = $record[0];
	$filename = $record[1];
	$title = $record[2];
	$folder_id = $record[3];
	$folder = $record[4];
	$runtime = $record[5];
	$season = $record[6];
	$episode = $record[7];
	$stamp = $record[8];
}

if ($tconst != "nada") {
	if (strlen($tvshowID) > 0) {
		$query2 = "select title, description from tv_episodes where showtconst = '$tconst' and season = $season and episode = $episode;";
		print "<!-- query = '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
		$rows2 = pg_num_rows($result2);
		for ($row2 = 0; $row2 < $rows2; $row2++ ){
			$record2 = pg_fetch_row($result2, $row2);

			$epititle = $record2[0];
			$description = $record2[1];
		}
	}
	else {
		$query2 = "select description from movie_info where tconst = '$tconst';";
		print "<!-- query = '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
		$rows2 = pg_num_rows($result2);
		for ($row2 = 0; $row2 < $rows2; $row2++ ){
			$record2 = pg_fetch_row($result2, $row2);
			$description = $record2[0];
		}
	}
	
	$query3 = "select title_genres, startyear, endyear from title_basics where tconst = '$tconst';";
	print "<!-- query = '$query3' -->\n";
	$result3 = pg_query($htdb_conn, $query3);
	$rows3 = pg_num_rows($result3);
	for ($row3 = 0; $row3 < $rows3; $row3++ ){
		$record3 = pg_fetch_row($result3, $row3);
		
		$category = $record3[0];
		$startyear = $record3[1];
		$endyear = $record3[2];
	}
	
	$query4 = "select web_path from folders where id = '$folder_id';";
	print "<!-- query = '$query4' -->\n";
	$result4 = pg_query($htdb_conn, $query4);
	$rows4 = pg_num_rows($result4);
	for ($row4 = 0; $row4 < $rows4; $row4++ ){
		$record4 = pg_fetch_row($result4, $row4);
		
		$basefolder = $record4[0];
	}
}
	
$mininame = $title;
$mininame = str_replace("_", "+", $mininame);
$mininame = str_replace(" ", "+", $mininame);
$imdb_url = "https://www.imdb.com/find?ref_=nv_sr_fn&q=$mininame&s=all";

print "<title>$title</title>\n";
print "</head>\n";
print "<body bgcolor=#FFFFFF forecolor=#000000>\n";

print "<form name=\"form1\" method=\"post\">\n";

if ($edit == "yes"){
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Title: </font><font face=\"sans-serif\" size=4 color=#888888 ><input type=\"text\" style=\"font-family: sans-serif; font-color: #888888;font-size:20px;\" size=\"30\" name=\"title\" value=\"$title\"></p>\n";
} else {
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Title: </font><font face=\"sans-serif\" size=4 color=#888888 >$title</p>\n";
}
if (strlen($epititle) > 0) {
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Episode: </font><font face=\"sans-serif\" size=4 color=#888888 >$epititle - S$season"."E$episode</p>\n";
}
if (strlen($description) > 0) {
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Description: </font><font face=\"sans-serif\" size=4 color=#888888 >$description</font></p>\n";
}
if (strlen($movieID) > 0) {
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Released: </font>$startyear</p>\n";
}
print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Runtime: </font><font face=\"sans-serif\" size=4 color=#888888 >$runtime minutes</font></p>\n";
if ($edit == "yes"){
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Category: </font><font face=\"sans-serif\" size=4 color=#888888 ><input type=\"text\" size=\"40\" name=\"category\" value=\"$category\"></font></p>\n";
} else {
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Category: </font><font face=\"sans-serif\" size=4 color=#888888 >$category</font></p>\n";
}
print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Filename: </font><a href=\"http://$server/$basefolder$folder$filename\" target=\"_blank\">$filename</a></p>\n";
list($stamp,$filler) = explode(".", $stamp);
print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Created: </font>$stamp</p>\n";
if ($edit == "yes"){
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >IMDB ID: </font><font face=\"sans-serif\" size=4 color=#888888 ><input type=\"text\" size=\"10\" name=\"tconst\" value=\"$tconst\"></font>&nbsp;<input type=\"button\" value=\"IMDB Page\" onclick=\"window.open('$imdb_url');\"/></p>\n";
}
if (strlen($tvshowID) > 0) {
	if ($edit == "yes") {
		print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Season #: </font><font face=\"sans-serif\" size=4 color=#888888 ><input type=\"text\" size=\"2\" name=\"season\" value=\"$season\"></font></p>\n";
		print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Episode #: </font><font face=\"sans-serif\" size=4 color=#888888 ><input type=\"text\" size=\"2\" name=\"episode\" value=\"$episode\"></font></p>\n";
	} else {
		print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Season #: </font><font face=\"sans-serif\" size=4 color=#888888 >$season</p>\n";
		print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Episode #: </font><font face=\"sans-serif\" size=4 color=#888888 >$episode</font></p>\n";
	}
}


if (strlen($tvshowID) > 0) {
	if ($edit == "yes") {
		$editicon = "<a href=\"http://$server/htdb/htdbvideoinfo.php?tvshow=$tvshowID\"><image src=\"http://$server/htdb/pencil.png\" width=\"32\" height=\"32\" title=\"Delete\"></a>";
	} else {
		$editicon = "<a href=\"http://$server/htdb/htdbvideoinfo.php?edit=yes&tvshow=$tvshowID\"><image src=\"http://$server/htdb/pencil.png\" width=\"32\" height=\"32\" title=\"Delete\"></a>";
	}
} else {
	if ($edit == "yes") {
		$editicon = "<a href=\"http://$server/htdb/htdbvideoinfo.php?movie=$movieID\"><image src=\"http://$server/htdb/pencil.png\" width=\"32\" height=\"32\" title=\"Delete\"></a>";
	} else {
		$editicon = "<a href=\"http://$server/htdb/htdbvideoinfo.php?edit=yes&movie=$movieID\"><image src=\"http://$server/htdb/pencil.png\" width=\"32\" height=\"32\" title=\"Delete\"></a>";
	}
}
print "<p>$editicon</p>\n";

if ($edit == "yes"){
	print "<input type=\"submit\" value=\"Save Changes\">\n";
	print "<p></p>\n";
	if (strlen($tvshowID) > 0) {
		$query3 = "select poster, epitconst from tv_episodes where showtconst = '$tconst' and season = $season and episode = $episode;";
		print "<!-- query '$query3' -->\n";
		$result3 = pg_query($htdb_conn, $query3);
		$rows3 = pg_num_rows($result3);
		if ($rows3 > 0) {
			$record3 = pg_fetch_row($result3, 0);
			$poster = $record3[0];
			$epitconst = $record3[1];
//			print "<a href=\"http://$server/htdb/htdbuploadposter.php?tvid=$epitconst\" target=\"_blank\" ><image src=\"http://$server/htdb/htdb-posters/$poster\" ></a>\n";
			print "<image src=\"http://$server/htdb/htdb-posters/$poster\" >\n";
		} else {
		}
	} else {
		$query_data = "select description, dims from movie_info where tconst = '$tconst';";
		$result_data = pg_query($htdb_conn, $query_data);
		$record_data = pg_fetch_row($result_data, 0);
		$description = $record_data[0];
		$movie_dims = $record_data[1];
		
		list($width, $height) = explode(" ", $movie_dims);
		$width = $width/4;
		$height = $height/4;
		$movie_dims = "width=\"$width\" height=\"$height\"";
		
//		print "<a href=\"http://$server/htdb/htdbuploadposter.php?movieid=$tconst\" target=\"_blank\" ><image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims ></a>\n";
		print "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >\n";
	}
	print "<p></p>\n";
}
else {
	if (strlen($tvshowID) > 0) {
		$query3 = "select poster from tv_episodes where showtconst = '$tconst' and season = $season and episode = $episode;";
		print "<!-- query '$query3' -->\n";
		$result3 = pg_query($htdb_conn, $query3);
		$rows3 = pg_num_rows($result3);
		if ($rows3 > 0) {
			$record3 = pg_fetch_row($result3, 0);
			$poster = $record3[0];
			
			print "<image src=\"http://$server/htdb/htdb-posters/$poster\" >\n";
		} else {
		}
	} else {
		$query_data = "select description, dims from movie_info where tconst = '$tconst';";
		$result_data = pg_query($htdb_conn, $query_data);
		$record_data = pg_fetch_row($result_data, 0);
		$description = $record_data[0];
		$movie_dims = $record_data[1];
		
		print "<!-- dims = '$movie_dims' -->\n";
		
		list($width, $height) = explode(" ", $movie_dims);
		print "<!-- dims = '$width, $height' -->\n";
		$width = $width/4;
		$height = $height/4;
		
		print "<!-- dims = '$width, $height' -->\n";
		
		$movie_dims = "width=\"$width\" height=\"$height\"";
		
		print "<a href=\"http://$server/htdb/htdb-posters/$tconst.jpg\" target=\"_blank\" ><image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims ></a>\n";
		//print "<image src=\"http://$server/htdb/htdb-posters/$tconst.jpg\" $movie_dims >\n";
	}
	print "<p></p>\n";
}

if (strlen($tvshowID) > 0) {
	$trashcan = "<a href=\"http://$server/htdb/htdbvideoinfo.php?trashcan=yes&tvshow=$tvshowID\"><image src=\"http://$server/htdb/trashcan.png\" width=\"32\" height=\"32\" title=\"Delete\"></a>";
} else {
	$trashcan = "<a href=\"http://$server/htdb/htdbvideoinfo.php?trashcan=yes&movie=$movieID\"><image src=\"http://$server/htdb/trashcan.png\" width=\"32\" height=\"32\" title=\"Delete\"></a>";
}

print "<!-- dvdID = '$dvdID' -->\n";

if (strlen($dvdID) == 0) {
	print "<p>$trashcan</p>\n";

	print "<table width=80%>\n";
	print "<tr><td align=left colspan=4><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Cast:</font></td></tr>\n";
	$query2 = "select nb.primaryname, mc.profession, mc.role, nb.nconst from movie_credits as mc, name_basics as nb where mc.tconst = '$tconst' and (mc.profession='actor' or mc.profession='director') and mc.nconst=nb.nconst order by mc.creditsnum;";
	print "<!-- query = '$query2' -->\n";
	$result2 = pg_query($htdb_conn, $query2);
	$rows2 = pg_num_rows($result2);
	print "<!-- number of rows: '$rows2' -->\n";
	for ($row2 = 0; $row2 < $rows2; $row2++ ){
		$record2 = pg_fetch_row($result2, $row2);
		$name = $record2[0];
		$profession = $record2[1];
		$role = $record2[2];
		$nbconst = $record2[3];
		print "<tr><td width=20>&nbsp;</td><td align=left><font face=\"sans-serif\" size=4 color=#888888 ><a href=\"http://$server/htdb/htdbmovielist.php?nbconst=$nbconst\" target=\"_blank\">$name</a></font></td><td align=left><font face=\"sans-serif\" size=4 color=#888888 >&nbsp;$profession</font></td><td align=left><font face=\"sans-serif\" size=4 color=#888888 >&nbsp;$role</font></td></tr>\n";
	}
	print "</table>\n";

	if (strlen($tvshowID) > 0) {
		print "<input type=\"hidden\" name=\"tvshow\" value=\"$tvshowID\">\n";
	}

	if (strlen($movieID) > 0) {
		print "<input type=\"hidden\" name=\"movie\" value=\"$movieID\">\n";
	}
}
else {
	print "<input type=\"hidden\" name=\"dvd\" value=\"$dvdID\">\n";
}

print "<input type=\"hidden\" name=\"edit\" value=\"$edit\">\n";

print "</form>\n";

pg_close($htdb_conn);

?>
</body>
</html>
