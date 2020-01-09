<!DOCTYPE html>
<html>
<head>
<script language="JavaScript">
function deleteItem(unique_id) {
	document.form1.deleteID.value=unique_id;
	document.form1.submit();
    return false;
}
function selectItem(unique_id) {
	document.form1.selectID.value=unique_id;
	document.form1.submit();
    return false;
}
function deleteHint(unique_id) {
	document.form1.deleteHint.value=unique_id;
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
	text-align: left;
	padding: 3px;
}

td {
	padding: 3px;
}

tr:nth-child(even) {
	background-color: #dddddd;
}

a {
	font-family: sans-serif;
	font-size: 12px;
}

a:visited {
  color: gray;
}

a:link {
  color: black;
}
</style>

<?php

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
$client = $_SERVER['HTTP_USER_AGENT'];
$cookie_client = $_COOKIE['htdb-client'];

print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}
$icon_size = "24";
if (strpos($client, "Android") > 0) {
	$link_target = "target=\"_blank\" ";
	$icon_size = "32";
}

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$query = "";
$reload_page = "no";

//print_r($_POST);

$delete_hint = $_REQUEST['deleteHint'];
if (strlen($delete_hint) > 0) {
	$query = "update tv_recording_hint set state = 'delete' where id = $delete_hint;";
	print "<!-- delete hint = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}

$delete_item = $_REQUEST['deleteItem'];
if (strlen($delete_item) > 0) {
	$query = "update dvd_data set title_rip = 'delete' where titlenum = $delete_item;";
	print "<!-- delete hint = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}

$delete_item = $_REQUEST['deleteRip'];
if (strlen($delete_item) > 0) {
	$query = "update dvd_rips set status = 'delete' where id = $delete_item;";
	print "<!-- delete hint = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}

foreach ($_POST as $key => $value) {
//	print "<p>key = '$key', value = '$value'</p>";
	if ($key == "deleteID") {
		if ($value > 0) {
			$query = "update dvd_rips set status = 'delete' where id = $value;";
//			print "<p>$query</p>";
			$result = pg_query($htdb_conn, $query);
		}
	} elseif ($key == "selectID") {
		if ($value > 0) {
			$query = "update dvd_data set title_rip = 'delete' where titlenum = $value;";
//			print "<p>$query</p>";
			$result = pg_query($htdb_conn, $query);
		}
	} elseif ($key == "rip_all") {
		$rip_cmd = "/usr/bin/sudo /var/www/html/htdb/dvd_rip.py rip > /var/www/html/htdb/dvd_rip.log &";
//		print "<p>$rip_cmd</p>";
		$result = exec($rip_cmd, $output, $return_value);
//		print "<p>result = '$result', value = '$return_value'</p>";
//		print_r($output);
		$reload_page = "yes";
	} elseif ($key == "save_all") {
		$mv_cmd = "/var/www/html/htdb/dvd_move.py > /var/www/html/htdb/dvd_move.log &";
//		print "<p>$rip_cmd</p>";
		$result = exec($mv_cmd, $output, $return_value);
//		print "<p>result = '$result', value = '$return_value'</p>";
//		print_r($output);
		$reload_page = "yes";
	} elseif ($key == "read") {
		$rip_cmd = "/usr/bin/sudo /var/www/html/htdb/dvd_rip.py read > /var/www/html/htdb/dvd_rip.log &";
//		print "<p>$rip_cmd</p>";
		$result = exec($rip_cmd, $output, $return_value);
//		print "<p>result = '$result', value = '$return_value'</p>";
//		print_r($output);
		$reload_page = "yes";
	} else {
		if ($value > 0) {
			if (strpos($key, "E") === false) {
				$dvdid = str_replace("S","", $key);
				$query = "update dvd_rips set season = '$value' where id = '$dvdid';";
//				print "<p>$query</p>";
				$result = pg_query($htdb_conn, $query);
			}
			if (strpos($key, "S") === false) {
				$dvdid = str_replace("E","", $key);
				
				$query = "select folder_path from folders where folder_type = 'TV';";
				$result = pg_query($htdb_conn, $query);
				$rows = pg_num_rows($result);
				for ($row = 0; $row < $rows; $row++ ){
					$record = pg_fetch_row($result, $row);
					$dest_path = $record[0];
				}

				$query = "select filename, season, episode, primarytitle from dvd_rips where id = '$dvdid';";
				$result = pg_query($htdb_conn, $query);
				$rows = pg_num_rows($result);
				for ($row = 0; $row < $rows; $row++ ){
					$record = pg_fetch_row($result, $row);
					$source_name = $record[0];
					$season = $record[1];
		//			$episode = $record[2];
					$folder_name = $record[3];
					$title = $record[3];
				}

				if (strlen($season) == 1) {
					$season = "0$season";
				}

				$episode = $value;
				if (strlen($episode) == 1) {
					$episode = "0$episode";
				}
				$episodenum = "S$season"."E$episode";

				$season_path = "/Season_$season/";

				$folder_name = str_replace(" ", "_", $folder_name);

				$newfilename = "$dest_path$folder_name$season_path$title - $episodenum".".mp4";

				$query = "update dvd_rips set newfilename = '$newfilename', episode = '$value', episodenum = '$episodenum', status = 'move' where id = '$dvdid';";
	//			print "<p>$query</p>";
				$result = pg_query($htdb_conn, $query);
			}
		}
	}
}

if ($reload_page == "yes" ) {
	print "<script language=\"JavaScript\">\n";
	print "setTimeout(function() { window.location=window.location;},5000);\n";
	print "</script>\n";
}
print "</head>\n";
print "<body bgcolor=#FFFFFF forecolor=#000000>\n";

print "<script>\n	top.document.title=\"HTDB - Maintenance\"\n</script>\n";

function build_seas_popup($name, $value) {
	$buffer = "";
	$buffer = "$buffer<select name=\"S$name\">\n";
	for($i=0;$i<30;$i++){
//		if ($value == $i){
//			$buffer = "$buffer<option selected value=\"$i\">$i</option>\n";
//		}
//		else {
			$buffer = "$buffer<option value=\"$i\">$i</option>\n";
//		}
	}
	$buffer = "$buffer</select>\n";
	
	return $buffer;
}

function build_epi_popup($name, $value) {
	$buffer = "";
	$buffer = "$buffer<select name=\"E$name\">\n";
	for($i=0;$i<30;$i++){
//		if ($value == $i){
//			$buffer = "$buffer<option selected value=\"$i\">$i</option>\n";
//		}
//		else {
			$buffer = "$buffer<option value=\"$i\">$i</option>\n";
//		}
	}
	$buffer = "$buffer</select>\n";
	
	return $buffer;
}

$ripping = "no";
$rip_cmd = "/bin/ps -efw | /bin/grep \"HandBrakeCLI --no-dvdnav -i '/media/\"";
$buffer = `$rip_cmd`;
list($buffone, $bufftwo, $buffthree) = explode("\n", $buffer);
$bone = strlen($buffone);
$btwo = strlen($bufftwo);
$bthree = strlen($buffthree);
$blen = $bone;
$buff = $buffone;
if ($btwo > $blen) {
	$blen = $btwo;
	$buff = $bufftwo;
}
if ($bthree > $blen) {
	$blen = $bthree;
	$buff = $buffthree;
}

print "<div align=center>\n";
print "<!-- buffer = '$buff' -->\n";
$buff_pos = strpos($buff, "/usr/bin/HandBrakeCLI");
print "<!-- buffer offset = '$buff_pos' -->\n";
if ($buff_pos > 5) {
	$buff_pos = strpos($buff, "sh -c");
//	print "<p>buffpos = $buff_pos</p>\n";
	if ($buff_pos > 5) {
		$ripping = "yes";
		$buff_pos = $buff_pos+5;
		$buff = substr($buff, $buff_pos);
		print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>DVD title currently ripping</b></p>\n";
		print "<hr>\n";
		
		$buff = str_replace("<", "&lt;", $buff);
		$buff = str_replace(">", "&gt;", $buff);
		print "<p>$buff</p>\n";
		
		//add code here to get last line of "/var/www/html/htdb/dvd_rip.log"
		
		$myfile = fopen("/var/www/html/htdb/dvd_rip.log", "r");
		$buffer = fread($myfile,filesize("/var/www/html/htdb/dvd_rip.log"));
		fclose($myfile);
		$buffparts = explode("\r", $buffer);
		$line = end($buffparts);
		
		print "<p>$line</p>\n";
		print "<p>&nbsp;</p>\n";
		if ($reload_page == "no" ) {
			print "<script language=\"JavaScript\">\n";
			print "setTimeout(function() { window.location=window.location;},30000);\n";
			print "</script>\n";
		}
	}
}

if ($ripping == "no") {
	$dvd_cmd = "/bin/df -h | /bin/grep /dev/sr";
	$buffer = `$dvd_cmd`;
	if (strlen($buffer) > 5) {
		print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>DVDs awaiting read</b></p>\n";
		print "<hr>\n";
		print "<form method=\"post\" name=\"read_dvds\" id=\"read_dvds\">\n";
		print "<input name=\"read\" type=\"submit\" value=\"Read DVD\" onclick=\"submit()\" >\n";
		print "</form>\n";
		$buffer = str_replace("<", "&lt;", $buffer);
		$buffer = str_replace(">", "&gt;", $buffer);
		print "<p>$buffer</p>\n";
		print "<p>&nbsp;</p>\n";
	}
}

$query = "select distinct dvd_title, titlenum, titlelen, title_rip from dvd_data where title_rip != 'delete' order by titlenum;";
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
print "<!-- rows = '$rows' -->\n";
if ($rows > 0) {
	print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>DVD titles in rip queue</b></p>\n";
	print "<hr>\n";
	$label = "";
	if ($ripping == "no") {
		print "<form method=\"post\" name=\"rip_dvds\" >\n";
		print "<input name=\"rip_all\" type=\"submit\" value=\"Rip DVD Titles\" onclick=\"submit()\" >\n<p></p>\n";
//		$label = "<th>Delete</th>";
	}
	print "<table width=80% >\n";
	print "<tr><th>Disc Title</th><th>Title #</th><th>Run Time</th>$label</tr>\n";
	for ($row = 0; $row < $rows; $row++ ) {
		$record = pg_fetch_row($result, $row);
		
		$dvd_title = $record[0];
		$title_num = $record[1];
		$title_len = $record[2];
		$title_rip = $record[3];
		
		$accenton = "";
		$accentoff = "";
		if ($title_rip == "yes") {
			$accenton = "<b>";
			$accentoff = "</b>";
		}
		$button = "";
		if ($ripping == "no") {
//			$button = "<td><input type=\"button\" onclick=\"javascript:selectItem('$title_num');\" name=\"selectit\" value=\"Delete\">";
			$button = "<a href=\"http://$server/htdb/htdbmaintenance.php?deleteItem=$title_num\"><image src=\"http://$server/htdb/trashcan.png\" width=\"$icon_size\" height=\"$icon_size\" alt=\"Delete\"></a>";
		}
		print "<tr><td>$accenton$dvd_title$accentoff</td><td>$accenton$title_num$accentoff</td><td>$accenton$title_len$accentoff</td><td align=\"right\">$button</td></tr>\n";

	}
	print "</table>\n";
	if ($ripping == "no") {
		print "</form>\n";
	}
	print "<p>&nbsp;</p>\n";
}

$dvd_web_path = "";
$query = "select web_path from folders where folder_type = 'temp';";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
for ($row = 0; $row < $rows; $row++ ) {
	$record = pg_fetch_row($result, $row);

	$dvd_web_path = $record[0];
}

//$query = "select filename, primarytitle, season, episode, disc, status, id, runtime from dvd_rips where status = 'ripped' order by primarytitle, season, disc, episode;";
$query = "select filename, primarytitle, season, episode, disc, status, id, runtime from dvd_rips where (status = 'ripped' or status = 'move') order by primarytitle, season, disc, episode;";
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);

if ($rows > 0) {
	print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>Videos awaiting assignment/saving</b></p>\n";
	print "<hr>\n";
	print "<form name=\"form2\" method=\"post\">\n";
	print "<input name=\"update_all\" type=\"submit\" value=\"Update Videos\" onclick=\"submit()\" >&nbsp;";
	print "<input name=\"save_all\" type=\"submit\" value=\"Save Videos\" onclick=\"submit()\" >\n<p></p>\n";
	print "<table width=80%>\n";
	print "<tr><th>Title</th><th>Filename</th><th>Run Time</th><th>Season</th><th>Episode</th><th>Delete</th></tr>\n";
	print "<!-- rows = '$rows' -->\n";
	for ($row = 0; $row < $rows; $row++ ) {
		$record = pg_fetch_row($result, $row);

		$dvd_filename = $record[0];
		$dvd_title = $record[1];
		$dvd_season = $record[2];
		$dvd_episode = $record[3];
		$dvd_disc = $record[4];
		$dvd_status = $record[5];
		$dvd_id = $record[6];
		$runtime = $record[7];

		$epi_popup = build_epi_popup($dvd_id, $dvd_episode);
		$seas_popup = build_seas_popup($dvd_id, $dvd_episode);
		
		$trashurl = "<a href=\"http://$server/htdb/htdbmaintenance.php?deleteRip=$dvd_id\"><image src=\"http://$server/htdb/trashcan.png\" width=\"$icon_size\" height=\"$icon_size\" alt=\"Delete\"></a>";
//		print "<tr><td>$dvd_title</td><td><a href=\"http://$server$dvd_web_path$dvd_filename\" target=\"_blank\">$dvd_filename</a></td><td>$runtime</td><td>$dvd_season&nbsp;$seas_popup</td><td>$dvd_episode&nbsp;$epi_popup</td><td><input type=\"button\" onclick=\"javascript:deleteItem('$dvd_id');\" name=\"deleteit\" value=\"Delete\"></td></tr>\n";
		print "<tr><td>$dvd_title</td><td><a href=\"http://$server$dvd_web_path$dvd_filename\" target=\"_blank\">$dvd_filename</a></td><td>$runtime</td><td>$dvd_season&nbsp;$seas_popup</td><td>$dvd_episode&nbsp;$epi_popup</td><td>$trashurl</td></tr>\n";
	}
	print "</table>\n";
	
	print "</form>\n";
}

$query = "select starttime, runtime, channelid, title, episodetitle, episodenum, recordstatus from tv_recording where endtime > now() and (recordstatus = 'pending' or recordstatus = 'started' or recordstatus = 'recording') order by starttime;";
print "<!-- query = '$query' -->\n";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);

if ($rows > 0) {
	print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>Video Recordings Pending</b></p>\n";
	print "<hr>\n";
	print "<form name=\"form3\" method=\"post\">\n";
	
	print "<table width=80%>\n";
	print "<tr><th>Start Time</th><th>Run Time</th><th>Title</th><th>Episode</th><th>TV Channel</th></tr>\n";
	print "<!-- rows = '$rows' -->\n";
	for ($row = 0; $row < $rows; $row++ ) {
		$record = pg_fetch_row($result, $row);

		$starttime = $record[0];
		$runtime = $record[1];
		$channelid = $record[2];
		$title = $record[3];
		$episodetitle = $record[4];
		$episodenum = $record[5];
		$status = $record[6];
		
		$startb = "";
		$endb = "";
		
		print "<!-- status = '$status' -->\n";
		if (strpos($status, "cording") > 0) {
			$startb = "<b>";
			$endb = "</b>";
		}
		if (strlen($episodetitle) > 0) {
			if (strlen($episodenum) > 0) {
				$episodetitle = "$episodetitle - ";
			}
		}
		
		print "<tr><td>$startb$starttime$endb</td><td>$startb$runtime$endb</td><td>$startb$title$endb</td><td>$startb$episodetitle$episodenum$endb</td><td>$startb$channelid$endb</tr>\n";
	}
	print "</table>\n";
	
	print "</form>\n";
}


$query = "select title, channelid, id from tv_recording_hint where state = 'active' order by title;";

$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);

if ($rows > 0) {
	print "<p><font size=\"4px\" face=\"sans-serif\" color=\"black\"><b>Video Recording Hints</b></p>\n";
	print "<hr>\n";

	print "<form name=\"form4\" method=\"post\">\n";
	print "<table width=80%>\n";
	print "<tr><th>Title</th><th>TV Channel</th><th>&nbsp;</th></tr>\n";
	print "<!-- rows = '$rows' -->\n";
	for ($row = 0; $row < $rows; $row++ ) {
		$record = pg_fetch_row($result, $row);

		$title = $record[0];
		$channelid = $record[1];
		$rec_id = $record[2];
		
		if($channelid == "0.0") {
			$channelid = "any";
		}
		
		$trashurl = "<a href=\"http://$server/htdb/htdbmaintenance.php?deleteHint=$rec_id\"><image src=\"http://$server/htdb/trashcan.png\" width=\"$icon_size\" height=\"$icon_size\" alt=\"Delete\"></a>";
		print "<tr><td>$title</td><td>$channelid</td><td align=\"right\">$trashurl</td></tr>\n";
	}
	print "</table>\n";
	print "</form>\n";
}

print "<form name=\"form1\" method=\"post\">\n";
print "	<INPUT TYPE=\"hidden\" NAME=\"deleteID\" VALUE=\"\">\n";
print "	<INPUT TYPE=\"hidden\" NAME=\"selectID\" VALUE=\"\">\n";
print "	<INPUT TYPE=\"hidden\" NAME=\"deleteHint\" VALUE=\"\">\n";
print "</form>\n";
pg_close($htdb_conn);

?>
</div>
</body>
</html>
