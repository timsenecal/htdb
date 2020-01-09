<!DOCTYPE html>
<html>
<head>
<link rel="apple-touch-icon" sizes="128x128" href="htdb.png"> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="mobile-web-app-capable" content="yes">
<style>
table {
	font-family: sans-serif;
	font-size: 5px;
	border-collapse: collapse;
//	width: 100%;
}

td, th {
//	border: 1px solid #dddddd;
	text-align: middle;
	padding: 3px;
}

//tr:nth-child(even) {
//	background-color: #dddddd;
//}

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
<?php

$hide_hint = "no";
$hide_show = "no";
$hide_tune = "no";

$server = $_SERVER['SERVER_ADDR'];
$cookie_client = $_COOKIE["htdb-client"];
$showID = $_REQUEST['tvshow'];

$tune_show = $_REQUEST['tune_show'];
$record_show = $_REQUEST['record_show'];
$record_hint = $_REQUEST['record_hint'];
$show_title = $_REQUEST['title'];
$show_channel = $_REQUEST['channel'];

//print_r($_REQUEST);

//print "<p>show = '$record_show', hint = '$record_hint', '$show_title', '$show_channel'</p>\n";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

if (strlen($record_hint) > 0) {
	$show_title_fixed = str_replace("'", "''", $show_title);
	$query2 = "select * from tv_recording_hint where channelid = '$show_channel' and title = '$show_title_fixed';";
	print "<!-- '$query2' -->\n";
	$result2 = pg_query($htdb_conn, $query2);
	$rows2 = pg_num_rows($result2);
	if ($rows2 == 0) {
		$query2 = "insert into tv_recording_hint (channelid, title) values ('$show_channel', '$show_title_fixed');";
		print "<!-- record hint '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
		
		//run the tool to check any hints against the schedule to add recordings, then run the tool to start any new recordings
		$recording_cmd = "/var/www/html/htdb/recordings_hints.py > /var/www/html/htdb/hints.log; /var/www/html/htdb/recordings_check.py >> /var/www/html/htdb/recordings.log &";
		//print "<!-- exec:$kill_cmd -->\n";
		$output = shell_exec($recording_cmd);
	}
	
	$hide_hint = "yes";
}

$timeoffset = "15";
$query = "select channelid from tv_info where id = '$showID';";
print "<!-- '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);

	$channelid = $record[0];
	$query = "select timeoffset from tv_channel_info where channelid = $channelid;";
	print "<!-- '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result, $row);
		
		$timeoffset = $record[0];
	}
}

$query = "select starttime, endtime, title, episodetitle, description, length, episodenumc, rating, repeat, channelid, category, episodenumdd, starttime::timestamp - interval '$timeoffset seconds' as recordtime, (endtime-starttime) as runtime from tv_info where id = '$showID';";
print "<!-- '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);

	$starttime = $record[0];
	$endtime = $record[1];
	$title = $record[2];
	$epititle = $record[3];
	$description = $record[4];
	$length = $record[5];
	$episode = $record[6];
	$rating = $record[7];
	$repeat = $record[8];
	$channel = $record[9];
	$category = $record[10];
	$episodedd = $record[11];
	$record_start = $record[12];
	$runtime = $record[13];
}

$channel_label = $channel;
$query2 = "select callname from tv_channel_info where channelid = '$channel';";
print "<!-- '$query2' -->\n";
$result2 = pg_query($htdb_conn, $query2);
$rows2 = pg_num_rows($result);
for ($row2 = 0; $row2 < $rows2; $row2++ ){
	$record2 = pg_fetch_row($result2, $row2);
	$channel_label = $record2[0];
	$channel_label = "$channel_label $channel";
}

if (strlen($record_show) > 0) {
	$query2 = "select * from tv_recording where channelid = '$show_channel' and title = '$show_title' and endtime = '$endtime';";
	print "<!-- '$query2' -->\n";
	$result2 = pg_query($htdb_conn, $query2);
	$rows2 = pg_num_rows($result2);
	if ($rows2 == 0) {
		$values = "'$record_start', '$endtime', '$channel', '$title', '$epititle', '$episode', 'pending', now(), '$runtime', '$episodedd', 'any'";
		$query2 = "insert into tv_recording (starttime, endtime, channelid, title, episodetitle, episodenum, recordstatus, stamp, runtime, episodenumdd, tuner) values ($values);";
		print "<!-- record show '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
		
		//the tool to start any new recordings
		$recording_cmd = "/var/www/html/htdb/recordings_check.py &";
		//print "<!-- exec:$kill_cmd -->\n";
		$output = shell_exec($recording_cmd);
	}
	
	$hide_show = "yes";
}

if (strlen($tune_show) > 0) {
	$query2 = "select * from chromecast_tune where channelid = '$show_channel' and title = '$show_title' and starttime = '$record_start';";
	print "<!-- '$query2' -->\n";
	$result2 = pg_query($htdb_conn, $query2);
	$rows2 = pg_num_rows($result2);
	if ($rows2 == 0) {
		$values = "'$record_start', '$endtime', '$channel', '$title', '$epititle', '$episode', 'pending', now(), '$runtime', '$episodedd', 'any', '$cookie_client'";
		$query2 = "insert into chromecast_tune (starttime, endtime, channelid, title, episodetitle, episodenum, tunestatus, stamp, runtime, episodenumdd, tuner, client) values ($values);";
		print "<!-- record show '$query2' -->\n";
		$result2 = pg_query($htdb_conn, $query2);
		
		//the tool to start any new recordings
		$recording_cmd = "/var/www/html/htdb/tvtune_check.py &";
		//print "<!-- exec:$kill_cmd -->\n";
		$output = shell_exec($recording_cmd);
	}
	
	$hide_show = "yes";
}

if (strlen($repeat) > 0) {
	if ($repeat == "no") {
		$repeat = "";
	} else {
		$repeat = " - Repeat Showing";
	}
}

print "<title>$title</title>\n";
print "</head>\n";
print "<body bgcolor=#FFFFFF forecolor=#000000>\n";

print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Title: </font><font face=\"sans-serif\" size=4 color=#888888 >$title</p>\n";
if (strlen($epititle) > 0) {
	if (strlen($episode) > 0) {
		print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Episode: </font><font face=\"sans-serif\" size=4 color=#888888 >$epititle - $episode</p>\n";
	} else {
		print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Episode: </font><font face=\"sans-serif\" size=4 color=#888888 >$epititle</p>\n";
	}
}
if (strlen($description) > 0) {
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Description: </font><font face=\"sans-serif\" size=4 color=#888888 >$description$repeat</font></p>\n";
}
print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Start Time: </font><font face=\"sans-serif\" size=4 color=#888888 >$starttime</p>\n";
print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >End Time: </font><font face=\"sans-serif\" size=4 color=#888888 >$endtime</p>\n";
print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Runtime: </font><font face=\"sans-serif\" size=4 color=#888888 >$length minutes</font></p>\n";
if (strlen($rating) > 0) {
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Rating: </font><font face=\"sans-serif\" size=4 color=#888888 >$rating</font></p>\n";
}
print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >TV Channel: </font><font face=\"sans-serif\" size=4 color=#888888 >$channel_label</font></p>\n";
if (strlen($category) > 0) {
	print "<p><font face=\"sans-serif\" weight=\"bold\" size=4 color=black >Category: </font><font face=\"sans-serif\" size=4 color=#888888 >$category</font></p>\n";
}

$query2 = "select * from chromecast_tune where channelid = '$channel' and title = '$title' and starttime = '$record_start';";
print "<!-- tune show '$query2' -->\n";
$result2 = pg_query($htdb_conn, $query2);
$rows2 = pg_num_rows($result2);
if ($rows2 > 0) {
	$hide_tune = "yes";
}

$query2 = "select * from tv_recording where channelid = '$channel' and title = '$title' and endtime = '$endtime';";
print "<!-- record show '$query2' -->\n";
$result2 = pg_query($htdb_conn, $query2);
$rows2 = pg_num_rows($result2);
if ($rows2 > 0) {
	$hide_show = "yes";
}

$query2 = "select * from tv_recording_hint where title = '$title';";
print "<!-- recording hint '$query2' -->\n";
$result2 = pg_query($htdb_conn, $query2);
$rows2 = pg_num_rows($result2);
if ($rows2 > 0) {
	$hide_hint = "yes";
}

print "<form name=\"form1\" method=\"post\">\n";
if ($hide_tune == "no") {
	print "<hr>";
	print "<p><input name=\"tune_show\" type=\"submit\" value=\"Watch this Show on TV\" onclick=\"submit()\" ></p>\n";
	print "<p></p>\n";
}
if ($hide_show == "no") {
	print "<hr>";
	print "<p><input name=\"record_show\" type=\"submit\" value=\"Record This Show\" onclick=\"submit()\" ></p>\n";
	print "<p></p>\n";
}
if ($hide_hint == "no") {
	print "<hr>";
	print "<input name=\"record_hint\" type=\"submit\" value=\"Record Shows Like This\" onclick=\"submit()\" >\n<p></p>\n";
	print "<p>$fontface"."Show Title: </font>$fontface<input type=\"text\" size=\"30\" name=\"title\" value=\"$title\"></font></p>\n";
	print "<p>$fontface"."Show Channel (0 for any channel): </font>$fontface<input type=\"text\" size=\"3\" name=\"channel\" value=\"$channel\"></font></p>\n";
}

print "	<INPUT TYPE=\"hidden\" NAME=\"tvshow\" VALUE=\"$showID\">\n";
print "</form>\n";

pg_close($htdb_conn);

?>
</body>
</html>
