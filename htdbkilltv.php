<?php
//header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
//header("Pragma: no-cache"); //HTTP 1.0
//header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$id = $_REQUEST['id'];

$kill_command = "/var/www/html/htdb/kill_live_tv.py $id";
$output = shell_exec($kill_command);
print "<!-- output = '$output' -->\n";
?>
