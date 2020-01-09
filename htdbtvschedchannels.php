<!DOCTYPE html>
<html>
<head>

<?php

$client = $_SERVER["HTTP_USER_AGENT"];
$cookie_client = $_COOKIE["htdb-client"];

$start = strpos($client, "(");
$end = strpos($client, ")");
$len = $end-$start;
$client = substr($client, $start, $len);
$start = strpos($client, ";");
$client = substr($client, $start+1);
print "<!-- client = '$client' -->\n";

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$link_xmltarget = "target=\"_parent\"";
$link_target = "";
$icon_size = "24";
$has_pencil = "yes";
if (strpos($client, "Android") > 0) {
	$link_target = "target=\"_blank\" ";
	$link_xmltarget = "xlink:show=\"new\"";
	$icon_size = "32";
	$has_pencil = "no";
}

print "</head>\n";
print "<body bgcolor=#FFFFFF forecolor=#000000>\n";


$caststation = $_GET['caststation'];
//print "<!-- caststation = '$caststation' -->\n

if (strlen($caststation) > 0) {
	$cast_cmd = "/var/www/html/htdb/chromecast_play.py $caststation tvstation 0 0 0 $cookie_client";
	print "<!-- tvcast command = '$cast_cmd' -->\n";
//	$output = "";
//	$return_value = "";
	$output = shell_exec($cast_cmd);
	print "<!-- tvcast output = '$output' -->\n";
//	$result = exec($cast_cmd, $output, $return_value);
//	print "<p>return = '$return_value'</p>\n";
//	print_r ($output);
}

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$query = "select fullname, channelid, icon, callname from tv_channel_info where active = 'yes' order by channelid;";

print "<!-- query = '$query' -->\n";

$result = pg_query($htdb_conn, $query);

$rows = pg_num_rows($result);

print "<!-- rows = '$rows' -->\n";

$num_channels = $rows;

$height = ($num_channels * 50);

$top = -2;

print "<svg width=\"150\" height=\"$height\" xmlns=\"http://www.w3.org/2000/svg\"  xmlns:xlink=\"http://www.w3.org/1999/xlink\" version=\"1.1\" >\n";

for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);
	
	$fullname = $record[0];
	$channelid = $record[1];
	$icon_url = $record[2];
	$callname = $record[3];
		
	$icon_url = "http://$server/htdb/htdb-posters/$callname".".png";
	$stream_url = "http://$server/htdb/tvcast.png";
	
	$line1 = $top+2;
	$line2 = $top+22;
	$line3 = $top+25;
	$line4 = ($top+48)-$icon_size;
	print "<g class=\"st\">\n";
	print "<a xlink:type=\"simple\" xlink:href=\"http://$server/htdb/htdbtvlive.php?station=$channelid\" $link_xmltarget>\n";
	print "<rect x=\"0\" y=\"$line1\" width=\"150\" height=\"50\" style=\"fill:white;stroke:black;stroke-width:2;opacity:1.0\" />\n";
	print "<text x=\"6\" y=\"$line2\" style=\"font-family: sans-serif; font-weight: bold; font-style: bold\" fill=\"Black\" >$fullname</text>\n";
	print "<image xlink:href=\"$icon_url\" x=\"0\" y=\"$line3\" height=\"25px\" width=\"50px\"/>\n";
	print "</a>\n";
	print "<a xlink:type=\"simple\" xlink:href=\"http://$server/htdb/htdbtvschedchannels.php?caststation=$channelid\" >\n";
	print "<image xlink:href=\"$stream_url\" x=\"120\" y=\"$line4\" height=\"$icon_size"."px\" width=\"$icon_size"."px\"/>\n";
	print "</a>\n";
	print "</g>\n";
	
	$top = $top + 50;
}

print "</svg>";

pg_close($htdb_conn);

?>
</body>
</html>
