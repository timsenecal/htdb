<?php
//header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
//header("Pragma: no-cache"); //HTTP 1.0
//header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past


$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$cookie_client = $_COOKIE["htdb-client"];

$station = $_GET['station'];

if (strlen($station) == 0) {
	$station = "20.2";
}
if (strlen($server) == 0) {
	$server = "127.0.0.1";
}

$unique = time();

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$kill_command = "/var/www/html/htdb/kill_live_tv.py";
$output = shell_exec($kill_command);

$query = "select hd.ipaddress from hdhomerun_channels as hc, hdhomerun_devices as hd where hc.channelid = $station and hc.deviceid = hd.deviceid and hd.tuner_used < hd.tunercount;";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);
	$ip_addr = $record[0];
}

$query = "update hdhomerun_devices set tuner_used = tuner_used+1 where ipdaddress = '$ip_addr';";
//$result = pg_query($htdb_conn, $query);

$live_cmd = "/var/www/html/htdb/htdbtvlive.py $ip_addr tuner1 $station $unique  > /dev/null 2>/dev/null &";
//print "<!-- exec:$live_cmd -->\n";
$output = shell_exec($live_cmd);
//print "<!-- shell exec output = '$output' -->\n";
//exec($live_cmd);

$file_path = "/var/www/html/htdb/htdb-tvlive/hdhomerun_"."$station"."_"."$unique".".m3u8";
$url_path = "http://$server/htdb/htdb-tvlive/hdhomerun_"."$station"."_"."$unique".".m3u8";

$showtitle = "Channel $station";
$query = "select title, starttime, endtime from tv_info where channelid = $station and endtime > now() order by starttime limit 1;";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
for ($row = 0; $row < $rows; $row++ ) {
	$record = pg_fetch_row($result, $row);
	$showtitle = $record[0];
}

$check = file_exists($file_path);
while ($check == 0) {
	sleep(1);
	$check = file_exists($file_path);
}

$query = "select nextval('tvliveid');";
$result = pg_query($htdb_conn, $query);
$record = pg_fetch_row($result, 0);
$id = $record[0];

$query = "insert into tv_live (station, filename, id) values ('$station', '$file_path', '$id');";
$result = pg_query($htdb_conn, $query);

pg_close($htdb_conn);

$old_file = "/var/www/html/htdb/htdbtvlive_template.template";
$myfile = fopen($old_file, "r");
$buffer = fread($myfile, filesize($old_file));
fclose($myfile);

$buffer = str_replace("<server>", $server, $buffer);
$buffer = str_replace("<mvfile>", $url_path, $buffer);
$buffer = str_replace("<showtitle>", $showtitle, $buffer);
$buffer = str_replace("<id>", $id, $buffer);

$new_file = "/var/www/html/htdb/htdb-tvlive/htdbtvlive_$station".".html";
$del_cmd = "/bin/rm -rf $new_file";
//print "<!-- exec:$del_cmd -->\n";
$output = shell_exec($del_cmd);

$myfile = fopen($new_file, "w");
fwrite($myfile,$buffer);
fclose($myfile);

if (strlen($cookie_client) > 0) {
	$query = "insert into client_playing (client, ttype, id) values ('$cookie_client', 'tvchannel', '$station');";
	print "<!-- query '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}

$header_url = "http://$server/htdb/htdb-tvlive/htdbtvlive_$station".".html";

//print "<!-- new url '$header_url' -->\n";
header("Location: $header_url");

?>
