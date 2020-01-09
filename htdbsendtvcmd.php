<?php
//header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
//header("Pragma: no-cache"); //HTTP 1.0
//header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$cmd = $_REQUEST['cmd'];
		
$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$dump_cmd = "/bin/echo 'orig $cmd\n' > /var/www/html/htdb/tvcmd.dump"; 
$output = `$dump_cmd`;

if (strlen($cmd) == 0) {
	$cmd = "pl_status";
}

$dump_cmd = "/bin/echo 'fixed $cmd\n' >> /var/www/html/htdb/tvcmd.dump"; 
$output = `$dump_cmd`;

if (is_numeric($cmd)) {
	$cmd = "seek&val=$cmd"."s";
}

if ($cmd == "pl_mute") {
	$query = "select ipaddress, volume, mute from chromecast_devices;";
	$result_data = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result_data);
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result_data, $row);
		$ip_addr = $record[0];
		$volume = $record[1];
		$mute = $record[2];
		
		$cast_cmd = "/usr/local/bin/cast --host $ip_addr --port 8009 volume 0.0";
		$query = "update chromecast_devices set mute = 'yes' where ipaddress = '$ip_addr';";
		if ($mute == "yes") {
			$cast_cmd = "/usr/local/bin/cast --host $ip_addr --port 8009 volume $volume";
			$query = "update chromecast_devices set mute = 'no' where ipaddress = '$ip_addr';";
		}
		exec ($cast_cmd, $lines, $return_var);
		$result_data = pg_query($htdb_conn, $query);
		//$output = `$vlc_cmd`;
		
		$dump_cmd = "/bin/echo '$cast_cmd \n $lines[2], $lines[3]' > /var/www/html/htdb/tvcmd_$cmd.dump"; 
		$output = `$dump_cmd`;
	}
	
	$cmd = "pl_status";
}

if ($cmd == "pl_volume_down") {
	$query = "select ipaddress from chromecast_devices;";
	$result_data = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result_data);
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result_data, $row);
		$ip_addr = $record[0];
		
		$cast_cmd = "/usr/local/bin/cast --host $ip_addr --port 8009 status";
		exec ($cast_cmd, $lines, $return_var);
		$current = $lines[3];
		$current = str_replace("Volume: ", "", $current);
		$new_vol = $current - 0.1;
		$dump_cmd = "/bin/echo '$cast_cmd \n $current $new_vol' > /var/www/html/htdb/tvcmd_$cmd.dump"; 
		$output = `$dump_cmd`;
		
		$mute = "no";
		if ($new_vol == "0.0") {
			$mute = "yes";
		}
		$query = "update chromecast_devices set volume = '$new_vol', mute = '$mute' where ipaddress = '$ip_addr';";
		$result_data = pg_query($htdb_conn, $query);
		
		$cast_cmd = "/usr/local/bin/cast --host $ip_addr --port 8009 volume $new_vol";
		//$output = `$vlc_cmd`;
		exec ($cast_cmd, $lines, $return_var);
		$dump_cmd = "/bin/echo '$cast_cmd \n $output' >> /var/www/html/htdb/tvcmd_$cmd.dump"; 
		$output = `$dump_cmd`;
	}
	
	$cmd = "pl_status";
}

if ($cmd == "pl_volume_up") {
	$query = "select ipaddress from chromecast_devices;";
	$result_data = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result_data);
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result_data, $row);
		$ip_addr = $record[0];
		
		$cast_cmd = "/usr/local/bin/cast --host $ip_addr --port 8009 status";
		exec ($cast_cmd, $lines, $return_var);
		$current = $lines[3];
		$current = str_replace("Volume: ", "", $current);
		$new_vol = $current + 0.1;
		$dump_cmd = "/bin/echo '$cast_cmd \n $current $new_vol' > /var/www/html/htdb/tvcmd_$cmd.dump"; 
		$output = `$dump_cmd`;
		
		$query = "update chromecast_devices set volume = '$new_vol', mute = 'no' where ipaddress = '$ip_addr';";
		$result_data = pg_query($htdb_conn, $query);
		
		$cast_cmd = "/usr/local/bin/cast --host $ip_addr --port 8009 volume $new_vol";
		//$output = `$vlc_cmd`;
		exec ($cast_cmd, $lines, $return_var);
		$dump_cmd = "/bin/echo '$cast_cmd \n $output' >> /var/www/html/htdb/tvcmd_$cmd.dump"; 
		$output = `$dump_cmd`;
	}
	
	$cmd = "pl_status";
}

if ($cmd == "pl_volume") {
	$query = "select ipaddress from chromecast_devices;";
	$result_data = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result_data);
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result_data, $row);
		$ip_addr = $record[0];
		
		$cast_cmd = "/usr/local/bin/cast --host $ip_addr --port 8009 volume 1.0";
		//$output = `$vlc_cmd`;
		exec ($cast_cmd, $lines, $return_var);
		
		$query = "update chromecast_devices set volume = '1.0', mute = 'no' where ipaddress = '$ip_addr';";
		$result_data = pg_query($htdb_conn, $query);
		
		$dump_cmd = "/bin/echo '$cast_cmd \n $output' > /var/www/html/htdb/tvcmd_$cmd.dump"; 
		$output = `$dump_cmd`;
	}
	
	$cmd = "pl_status";
}

//pl_status doesn't actually send a command, it just sends a status
if ($cmd != "pl_status") {
	$vlc_cmd = "/usr/bin/curl -s -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=$cmd\"";
	//$output = `$vlc_cmd`;
	exec ($vlc_cmd, $lines, $return_var);
	$dump_cmd = "/bin/echo '$vlc_cmd \n $output' > /var/www/html/htdb/tvcmd_$cmd.dump"; 
	$output = `$dump_cmd`;

	usleep (100);
}

//now that the command has been sent, get the current status, update the database, and reply
$vlc_cmd = "/usr/bin/curl -s -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml\"";
//$output = `$vlc_cmd`;
exec ($vlc_cmd, $lines, $return_var);

$x = count($lines);
//print "number of lines: $x";
//print $output;	
//print $lines;
$result = "";
$state = "";
$ctime = "";
foreach ($lines as &$line) {
//	print $line;
	$x = strpos( $line, "</state>" );
	if ($x > 0) {
		$thing = substr($line, 7, $x-7);
		$state = $thing;
		$dump_cmd = "/bin/echo '$line\n' >> /var/www/html/htdb/tvcmd.dump";
		$output = `$dump_cmd`;
	}
	$x = strpos( $line, "</time>" );
	if ($x > 0) {
		$thing = substr($line, 6, $x-6);
		$ctime = $thing;
		$dump_cmd = "/bin/echo '$line\n' >> /var/www/html/htdb/tvcmd.dump"; 
		$output = `$dump_cmd`;
	}
}

$dump_cmd = "/bin/echo '$state:$ctime\n' >> /var/www/html/htdb/tvcmd.dump"; 
$output = `$dump_cmd`;

$query = "select ttype, file_id from chromecast_playing;";
$result_data = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result_data);
for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result_data, $row);
	$ttype = $record[0];
	$fileid = $record[1];
	if ($fileid != 0) {
		if ($ctime > 0) {
			$query = "update client_playing set stamp = now(), currenttime = '$ctime' where ttype = '$ttype' and id = '$fileid';";
			$result_data = pg_query($htdb_conn, $query);
		}
	}
}

if ($ctime > 0)
{
	$query = "update chromecast_playing set ctime = '$ctime', status = '$state';";
	$result_data = pg_query($htdb_conn, $query);
}

if ($cmd == "pl_stop") {
	$query = "delete from chromecast_playing;";
	$result_data = pg_query($htdb_conn, $query);
}

//$result = "$result:$state";
$result = json_encode(array("state"=>$state, "ctime"=>$ctime));

print $result;
?>
