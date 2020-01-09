<?php
//header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
//header("Pragma: no-cache"); //HTTP 1.0
//header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$ID = $_REQUEST['id'];
$currenttime = $_REQUEST['t'];
$cookie_client = $_REQUEST["c"];


if (strlen($currenttime) <= 0) {
	$request_body = file_get_contents('php://input');
	
	$parts = explode("&",$request_body);
	$cookie_client = $parts[0];
	$ID = $parts[1];
	$currenttime = $parts[2];
	
	$cookie_client = str_replace("c=", "", $cookie_client);
	$ID = str_replace("id=", "", $ID);
	$currenttime = str_replace("t=", "", $currenttime);
}

if ($currenttime > 0) {
	$query = "update client_playing set currenttime = '$currenttime', stamp = now() where client = '$cookie_client' and id = '$ID';";
	print "<!-- recent query = '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
	$rows = pg_num_rows($result);
	for ($row = 0; $row < $rows; $row++ ){
		$record = pg_fetch_row($result, $row);
		$currenttime = $record[0];
	}
}

pg_close($htdb_conn);

?>
