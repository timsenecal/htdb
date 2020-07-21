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

$nbconst = isset($_REQUEST['nbconst']) ? $_REQUEST['nbconst'] : '';

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$id = "";
$name = "";
$castlabel = "mvcastID";
$viewer = "htdbvideo.php?ttype=movie&ID=";
$info = "htdbvideoinfo.php?movie=";
if (strlen($nbconst) > 0) {
	$query = "select distinct mf.primarytitle, mf.id, mc.tconst, mi.movie_dims, nb.primaryname, nb.nconst from movie_files as mf, movie_credits as mc, name_basics as nb, movie_info as mi where nb.nconst = '$nbconst' and mc.nconst=nb.nconst and mf.tconst = mc.tconst and mf.tconst = mi.tconst;";

	print "<!-- query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	
	if ($rows > 0) {
		$record = pg_fetch_row($result, 0);
		$actor_name = $record[4];
		print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>Movies starring $actor_name</b></font></p>\n";
		print "<hr>\n";
		print "<table width=100%>\n";
		print "<tr>";
		$counter = 0;
		for ($row = 0; $row < $rows; $row++ ) {
			$record = pg_fetch_row($result, $row);

			$title = $record[0];
			$movieid = $record[1];
			$tconst = $record[2];
			$movie_dims = $record[3];

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
}

pg_close($htdb_conn);

?>
</body>
</html>
