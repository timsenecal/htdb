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
	font-size: 5px;
	border-collapse: collapse;
	width: 100%;
}

td, th {
	border: 1px solid #dddddd;
	text-align: left;
	padding: 3px;
}

tr:nth-child(even) {
	background-color: #dddddd;
}

a {
	font-family: sans-serif;
	font-size: 5px;
}

a:visited {
  color: gray;
}

a:link {
  color: black;
}
</style>
<title>Settings</title>
</head>
<body bgcolor=#FFFFFF forecolor=#000000>
<?php

print "<script>\n	top.document.title=\"HTDB - Settings\"\n</script>\n";

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$address = $_SERVER['REMOTE_ADDR'];
$cookie_client = $_COOKIE["htdb-client"];

$client = $_SERVER["HTTP_USER_AGENT"];
$start = strpos($client, "(");
$end = strpos($client, ")");
$len = $end-$start;
$client = substr($client, $start, $len);
$start = strpos($client, ";");
$client = substr($client, $start+1);
print "<!-- client = '$client' -->\n";
print "<!-- cookie = '$cookie_client' -->\n";

$settingsID = $_REQUEST['settingsID'];
$label = $_REQUEST['label'];

$has_movies = isset($_POST['has_movies']) ? $_POST['has_movies'] : 'no';
$has_tvshows = isset($_POST['has_tvshows']) ? $_POST['has_tvshows'] : 'no';
$has_tvsched = isset($_POST['has_tvsched']) ? $_POST['has_tvsched'] : 'no';
$has_music = isset($_POST['has_music']) ? $_POST['has_music'] : 'no';
$has_pics = isset($_POST['has_pics']) ? $_POST['has_pics'] : 'no';
$has_maint = isset($_POST['has_maintenance']) ? $_POST['has_maintenance'] : 'no';
$has_edit = isset($_POST['has_edit']) ? $_POST['has_edit'] : 'no';
$has_list = isset($_POST['has_playlist']) ? $_POST['has_playlist'] : 'no';
$has_camera = isset($_POST['has_camera']) ? $_POST['has_camera'] : 'no';
$has_cast = isset($_POST['has_cast']) ? $_POST['has_cast'] : 'no';
$has_vids = isset($_POST['has_vids']) ? $_POST['has_vids'] : 'yes';

//print "<!-- $settingsID, $label, $has_tvshows, $has_tvsched, $has_movies, $has_pics, $has_music, $has_maint, $has_list, $has_vids, $has_camera -->\n";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$error_flag = "no";
if (strlen($label) > 0) {
	if ($label != "New User") {
		if ($settingsID == 1) {
			$values = "'$label', '$client', '$has_movies', '$has_tvshows', '$has_tvsched', '$has_music', '$has_pics', '$has_maint', '$has_list', '$has_camera', '$has_cast', '$has_edit', '$has_vids'";
			$query = "insert into settings (label, client, has_movies, has_tvshows, has_tvsched, has_music, has_pics, has_maint, has_playlist, has_camera, has_chromecast, can_edit, embed_vids) values ($values);";
		} else {
			$query = "update settings set label = '$label', has_movies = '$has_movies', has_tvshows = '$has_tvshows', has_tvsched = '$has_tvsched', has_music = '$has_music', has_pics = '$has_pics', has_maint = '$has_maint', has_playlist = '$has_list', has_camera = '$has_camera', has_chromecast = '$has_cast', can_edit = '$has_edit', embed_vids = '$has_vids' where id = $settingsID;";
		}
		print "<!-- save query = '$query' -->\n";
		$result = pg_query($htdb_conn, $query);
		
		// expires in 2038...
		$expire = 2147483640;
		
		setcookie("htdb-client", $label, $expire, "/htdb/");
		$cookie_client = $label;
		
		print "<script>\n parent.menu.location = \"http://$server/htdb/htdbmenu.php\"\n</script>\n";
	} else {
		print "<!-- tried to save new client with name 'New User' -->\n";
		$error_flag = "yes";
	}
}

$has_tvshows = "yes";
$has_tvsched = "yes";
$has_movies = "yes";
$has_pics = "no";
$has_music = "no";
$has_maintenance = "no";
$has_playlist = "yes";
$has_camera = "no";
$has_cast = "no";

$movies_check = "";
$tv_shows_check = "";
$tv_sched_check = "";
$music_check = "";
$pics_check = "";
$maint_check = "";
$edit_check = "";
$vids_check = "";
$cast_check = "";

$label = "default";

$button_label = "Save Settings";

$query = "select distinct label from settings;";
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
print "<!-- ";
for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);
	$client_item = $record[0];
	print("'$client_item', ");
}
print (" -->\n");
$query = "select has_tvshows, has_tvsched, has_movies, has_pics, has_music, has_maint, has_playlist, has_camera, has_chromecast, can_edit, embed_vids, label, id from settings where client = '$client';";

if (strlen($cookie_client) > 0) {
	$query = "select has_tvshows, has_tvsched, has_movies, has_pics, has_music, has_maint, has_playlist, has_camera, has_chromecast, can_edit, embed_vids, label, id from settings where label = '$cookie_client';";
}

print "<!-- query = '$query' -->\n";

$result = pg_query($htdb_conn, $query);

$rows = pg_num_rows($result);
if ($rows > 0) {
	$button_label = "Save Settings";
} else {
	$query = "select has_tvshows, has_tvsched, has_movies, has_pics, has_music, has_maint, has_playlist, has_camera, has_chromecast, can_edit, embed_vids, label, id from settings where client = 'all';";

	print "<!-- query = '$query' -->\n";

	$result = pg_query($htdb_conn, $query);

	$rows = pg_num_rows($result);

	$button_label = "Create Settings";
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
	$has_cast = $record[8];
	$can_edit = $record[9];
	$embed_vids = $record[10];
	$label = $record[11];
	$settingsID = $record[12];
}

if ($has_movies == "yes") {
	$movies_check = "checked";
}
if ($has_tvshows == "yes") {
	$tv_shows_check = "checked";
}
if ($has_tvsched == "yes") {
	$tv_sched_check = "checked";
}
if ($has_music == "yes") {
	$music_check = "checked";
}
if ($has_pics == "yes") {
	$pics_check = "checked";
}
if ($has_maintenance == "yes") {
	$maint_check = "checked";
}
if ($has_playlist == "yes") {
	$list_check = "checked";
}
if ($has_camera == "yes") {
	$camera_check = "checked";
}
if ($has_cast == "yes") {
	$cast_check = "checked";
}
if ($can_edit == "yes") {
	$edit_check = "checked";
}
if ($embed_vids == "no") {
	$vids_check = "checked";
}

$fontface = "<font face=\"sans-serif\" size=4 color=black >";
$errorface = "<font face=\"sans-serif\" size=4 color=red >";


print "<form name=\"form1\" method=\"post\">\n";

if ($error_flag == "yes") {
	print "<p>$errorface"."Client Name: </font>$errorface<input type=\"text\" size=\"20\" name=\"label\" value=\"$label\"></font></p>\n";
} else {
	print "<p>$fontface"."Client Name: </font>$fontface<input type=\"text\" size=\"20\" name=\"label\" value=\"$label\"></font></p>\n";
}

print "<p><input type=\"checkbox\" name=\"has_movies\" id=\"has_movies\" value=\"yes\" $movies_check ><label for=\"has_movies\">$fontface"."Show Movies</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_tvshows\" id=\"has_tvshows\" value=\"yes\" $tv_shows_check ><label for=\"has_tvshows\">$fontface"."Show TV Shows</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_tvsched\" id=\"has_tvsched\" value=\"yes\" $tv_sched_check ><label for=\"has_tvsched\">$fontface"."Show TV Schedule</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_music\" id=\"has_music\" value=\"yes\" $music_check ><label for=\"has_music\">$fontface"."Show Music</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_pics\" id=\"has_pics\" value=\"yes\" $pics_check ><label for=\"has_pics\">$fontface"."Show Pictures</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_maintenance\" id=\"has_maintenance\" value=\"yes\" $maint_check ><label for=\"has_maintenance\">$fontface"."Show Maintenance</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_playlist\" id=\"has_playlist\" value=\"yes\" $list_check ><label for=\"has_playlist\">$fontface"."Show Playlists</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_camera\" id=\"has_camera\" value=\"yes\" $camera_check ><label for=\"has_camera\">$fontface"."Show Camera</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_cast\" id=\"has_cast\" value=\"yes\" $cast_check ><label for=\"has_cast\">$fontface"."Show TV status on Home page</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_vids\" id=\"has_vids\" value=\"no\" $vids_check ><label for=\"has_vids\">$fontface"."Show videos in separate window</font></label></p>\n";
print "<p><input type=\"checkbox\" name=\"has_edit\" id=\"has_edit\" value=\"yes\" $edit_check ><label for=\"has_edit\">$fontface"."Show Pencil to edit data</font></label></p>\n";

print "<input type=\"hidden\" name=\"settingsID\" value=\"$settingsID\">\n";

print "<input name=\"save_all\" type=\"submit\" value=\"$button_label\" onclick=\"submit()\" >\n";

print "</form>\n";

pg_close($htdb_conn);

?>
</body>
</html>
