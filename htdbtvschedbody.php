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

$link_xmltarget = "";
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

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

$query = "select channelid from tv_channel_info where active = 'yes' order by channelid;";

print "<!-- query = '$query' -->\n";

$result = pg_query($htdb_conn, $query);

$rows = pg_num_rows($result);

print "<!-- rows = '$rows' -->\n";

$height = ($rows * 50);
$width = 7270;

$top = -2;

print "<svg width=\"$width\" height=\"$height\" xmlns=\"http://www.w3.org/2000/svg\"  xmlns:xlink=\"http://www.w3.org/1999/xlink\" version=\"1.1\" >\n";
	
for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);
	
	$channelid = $record[0];
		
	$icon_url = "http://$server/htdb/htdb-posters/$callname".".png";
	$stream_url = "http://$server/htdb/tvcast.png";

	$query2 = "select title, to_char(starttime, 'hh:mi'), to_char(endtime, 'hh:mi'), episodetitle, extract(epoch from (endtime - starttime))/60 as length, round(extract(epoch from (starttime-now()))/60) as offset, episodenumc, repeat, rating, substr(episodenumdd,1,2), category, id from tv_info where channelid = '$channelid' and endtime > now() and starttime < (now()+ '26 hours') order by starttime;";
//	print "<!-- query2 = '$query2' -->\n";

	$result2 = pg_query($htdb_conn, $query2);

	$rows2 = pg_num_rows($result2);
	
	$offset = 0;
	
	//rows of records in databe query are columns in tv station schedule
	for ($row2 = 0; $row2 < $rows2; $row2++ ){
		$record2 = pg_fetch_row($result2, $row2);
		
		$title = $record2[0];
		$starttime = $record2[1];
		$endtime = $record2[2];
		$episodetitle = $record2[3];
		$length = $record2[4];
		$toffset = $record2[5];
		$episodenum = $record2[6];
		$repeat = $record2[7];
		$rating = $record2[8];
		$eptype = $record2[9];
		$category = $record2[10];
		$showID = $record2[11];
		
//		$description = str_replace("&apos;", "'", $description);
		
		if (strpos($repeat, 'yes') !== false) {
    		$repeat = "Repeat";
		} else {
			$repeat = "";
		}
		
		//no category listed
		$box_fill = "lightblue";
		//movie episode type
		if (strpos($eptype, 'MV') !== false) {
    		$box_fill = "lightgreen";
		}
		//movie category (should be same as 'MV00' above
		if (strpos($category, 'Movie') !== false) {
    		$box_fill = "lightgreen";
		}
		//series episode type
		if (strpos($eptype, 'EP') !== false) {
    		$box_fill = "lightgray";
		}
		//news category
		if (strpos($category, 'News') !== false) {
    		$box_fill = "salmon";
		}
		//talk category
		if (strpos($category, 'Talk') !== false) {
    		$box_fill = "lightpink";
		}
		//sports category
		if (strpos($category, 'Sports') !== false) {
    		$box_fill = "peru";
		}
		//family safe programming
		if (strpos($category, 'Family') !== false) {
    		$box_fill = "mediumturquoise";
		}
		
		if ($toffset < 0) {
			$length = $length+$toffset;
		}
		print "<!-- toffset = '$toffset', length = '$length' -->\n";
		
		$width = $length * 5;
		
		$start1 = $offset+2;
		$start2 = $offset+8;
		$start3 = $offset+125;
		$start4 = $offset+195;
		$line1 = $top+2;
		$line2 = $top+22;
		$line3 = $top+42;

		print "<g class=\"show\" cursor=\"auto\">\n";
		print "<a xlink:type=\"simple\" xlink:href=\"http://$server/htdb/htdbtvshowinfo.php?tvshow=$showID\" xlink:show=\"new\" cursor=\"pointer\">\n";
		print "<rect x=\"$start1\" y=\"$line1\" width=\"$width\" height=\"50\" style=\"fill:$box_fill;stroke:black;stroke-width:2;opacity:1.0\" />\n";
		
		print "<text x=\"$start2\" y=\"$line2\" style=\"font-family: sans-serif; font-weight: bold; font-style: bold\" fill=\"Black\" >$title</text>\n";
		print "<text x=\"$start2\" y=\"$line3\" style=\"font-family: sans-serif; font-weight: normal; font-style: normal\" fill=\"Gray\">$starttime- $endtime</text>\n";
		print "<text x=\"$start3\" y=\"$line3\" style=\"font-family: sans-serif; font-weight: normal; font-style: normal\" fill=\"Blue\">$repeat</text>\n";
		print "<text x=\"$start4\" y=\"$line3\" style=\"font-family: sans-serif; font-weight: normal; font-style: normal\" fill=\"Gray\">$episodenum</text>\n";
		print "</a>\n";
		print "</g>\n";
		
		$offset = $offset+$width;
		
	}
	
	$top = $top + 50;
	$offset = 0;

}

print "\n\n\tSorry, your browser does not support inline SVG.	\n</svg>";

pg_close($htdb_conn);

?>
</body>
</html>
