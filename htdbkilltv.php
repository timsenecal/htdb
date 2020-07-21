<?php
//header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
//header("Pragma: no-cache"); //HTTP 1.0
//header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$id = $_REQUEST['id'];

if (strlen($id) <= 0) {
	$request_body = file_get_contents('php://input');
	
	$parts = explode("&",$request_body);
	
	$id = $parts[0];
	$id = str_replace("id=", "", $id);
}


$kill_command = "/var/www/html/htdb/kill_live_tv.py $id > /var/www/html/htdb/kill_cmd.log";
$output = shell_exec($kill_command);
print "<!-- output = '$output' -->\n";
?>
