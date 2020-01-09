<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
	a { color: black; font-size: 40px; font-family: sans-serif; text-decoration: none;} /* CSS link color */

	form.example input[type=text] {
	  padding: 10px;
	  font-family: sans-serif;
	  font-size: 30px;
	  border: 1px solid grey;
	  float: left;
	  width: 160px;
	  background: #FFFFFF;
	}

	form.example button {
	  float: left;
	  width: 50px;
	  padding: 10px;
	  background: #888888;
	  color: white;
	  font-size: 30px;
	  border: 1px solid grey;
	  border-left: none;
	  cursor: pointer;
	}

	form.example button:hover {
	  background: #666666;
	}
</style>

</head>
<body bgcolor=#FFFFFF forecolor=#000000>

<?php 

print "<script>\n	top.document.title=\"HTDB\"\n</script>\n";

$search_term = $_REQUEST['search_term'];

print "<!-- search term = '$search_term' -->\n";

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

if (strlen($search_term) > 0) {
	print "<script>top.window.frames['middle'].location=\"http://$server/htdb/htdbsearch.php?term=$search_term\"</script>\n";
}

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

$has_tvshows = "yes";
$has_tvsched = "yes";
$has_movies = "yes";
$has_pics = "yes";
$has_music = "yes";
$has_maintenance = "yes";
$has_playlist = "yes";
$has_camera = "yes";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$query = "delete from visitors where client = '$client';";
//print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$query = "insert into visitors (client, address, cookie_client) values ('$client', '$address', '$cookie_client');";
//print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);

$query = "select has_tvshows, has_tvsched, has_movies, has_pics, has_music, has_maint, has_playlist, has_camera from settings where client = '$client';";
if (strlen($cookie_client) > 0) {
	$query = "select has_tvshows, has_tvsched, has_movies, has_pics, has_music, has_maint, has_playlist, has_camera from settings where label = '$cookie_client';";
}

print "<!-- query = '$query' -->\n";

$result = pg_query($htdb_conn, $query);

$rows = pg_num_rows($result);

if ($rows == 0) {
	$query = "select has_tvshows, has_tvsched, has_movies, has_pics, has_music, has_maint, has_playlist, has_camera from settings where client = 'all';";
	print "<!-- query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
}

for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);
	
	$has_tvshows = $record[0];
	$has_tvsched = $record[1];
	$has_movies = $record[2];
	$has_pics = $record[3];
	$has_music = $record[4];
	$has_maintenance = $record[5];
	$has_playlist = $record[6];
	$has_camera = $record[7];
}

print "<!-- $has_movies, $has_tvshows, $has_tvsched, $has_pics, $has_music -->\n";

print "	<p><a href=\"http://$server/htdb/htdbhome.php\" target=\"middle\">Home</a></p>\n";

if ($has_movies == "yes") {
	print "	<p><a href=\"http://$server/htdb/htdbmovies.php\" target=\"middle\">Movies</a></p>\n";
}

if ($has_tvshows == "yes") {
	print "	<p><a href=\"http://$server/htdb/htdbtvshows.php\" target=\"middle\">TV Shows</a></p>\n";
}

if ($has_tvsched == "yes") {
	print "	<p><a href=\"http://$server/htdb/htdbtvsched.php\" target=\"middle\">TV Schedule</a></p>\n";
}

if ($has_music == "yes") {
	print "	<p><a href=\"http://$server/htdb/htdbmusic.php\" target=\"middle\">Music</a></p>\n";
}

if ($has_pics == "yes") {
	print "	<p><a href=\"http://$server/htdb/htdbpics.php\" target=\"middle\">Pictures</a></p>\n";
}

if ($has_maintenance == "yes") {
	print "	<p><a href=\"http://$server/htdb/htdbmaintenance.php\" target=\"middle\">Maintenance</a></p>\n";
}

if ($has_playlist == "yes") {
	print "	<p><a href=\"http://$server/htdb/htdbplaylist.php\" target=\"middle\">Playlists</a></p>\n";
}

if ($has_camera == "yes") {
	print "	<p><a href=\"http://$server/htdb/htdbcamera.php\" target=\"middle\">Cameras</a></p>\n";
}

print "	<p><a href=\"http://$server/htdb/htdbsettings.php\" target=\"middle\">Settings</a></p>\n";

//print "	<p><a href=\"http://$server/htdb/htdbsearch.php\" target=\"middle\">Search</a></p>\n";

print "	<form class=\"example\" method=\"post\" style=\"align:left\">\n";
print "	  <input type=\"text\" placeholder=\"Search...\" name=\"search_term\" >\n";
print "	  <button type=\"submit\"><i class=\"fa fa-search\"></i></button>\n";
print "	</form>\n";
?>

</body>
</html>