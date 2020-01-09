<!DOCTYPE html>
<html>

<?php
	
$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$ttype = $_GET['ttype'];
$ID = $_GET['ID'];
$tvID = $_GET['tvID'];
$currentval = $_GET['current'];
$cookie_client = $_COOKIE["htdb-client"];

$client = $_SERVER["HTTP_USER_AGENT"];
$start = strpos($client, "(");
$end = strpos($client, ")");
$len = $end-$start;
$client = substr($client, $start, $len);
$start = strpos($client, ";");
$client = substr($client, $start+1);
print "<!-- client = '$client' -->\n";
print "<!-- currentval = '$currentval' -->\n";
$use_geometry = "yes";
if (strpos($client, "Android") > 0) {
	$use_geometry = "no";
}

if (strlen($ttype) == 0) {
	$ttype = "movie";
}
if (strlen($tvID) > 0) {
	$ttype = "tvshow";
	$ID = $tvID;
}

//$command = "/usr/bin/whoami";
//$me = exec($command);
//print "<!-- who am i '$me' -->\n";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");


$query = "";
if ($ttype == "movie") {
	$query = "select a.filename, a.tconst, a.video, a.id, a.folder_id, '', b.web_path, '', '' from movie_files as a, folders as b where a.id = $ID and b.id = a.folder_id;";
} else {
	$query = "select a.filename, a.tconst, a.video, a.id, a.folder_id, a.folder, b.web_path, a.season, a.episode from tv_files as a, folders as b where a.id = $ID and b.id = a.folder_id;";
}
//print "<!-- query = '$query' -->\n";
$currenttime = 0;
//$currentval = "yes";
if ($currentval == "yes") {
	$query2 = "select currenttime from client_playing where client = '$cookie_client' and id = '$ID';";
	print "<!-- recently played query = '$query2' -->\n";
	$result2 = pg_query($htdb_conn, $query2);
	$rows2 = pg_num_rows($result2);
	for ($row2 = 0; $row2 < $rows2; $row2++ ){
		$record2 = pg_fetch_row($result2, $row2);
		$currenttime = $record2[0];
	}
	print "<!-- recently played time = '$currenttime' -->\n";
}

$result = pg_query($htdb_conn, $query);

$rows = pg_num_rows($result);

for ($row = 0; $row < $rows; $row++ ){
	$record = pg_fetch_row($result, $row);
	
	$movie_path = $record[6];
	$folder_path = $record[5];
	$folder_id = $record[4];
	$id = $record[3];
	$video = $record[2];
	$tconst = $record[1];
	$filename = $record[0];
	
	$season = $record[7];
	$episode = $record[8];
	
	$movie_path = "$movie_path$folder_path";
	
	$title = $filename;
	$epititle = "";
	
	print "<!-- mpath = '$movie_path' -->\n";
	
	$mtype = "mp4";
	if (strpos($video, "mpeg4") > 0) {
		$mtype = "mp4";
	};
	
	if ($tconst != "nada")
	{
		$query2 = "select primarytitle from title_basics where tconst = '$tconst';";
		print "<!-- query = '$query' -->\n";
		
		$result2 = pg_query($htdb_conn, $query2);
		
		$rows2 = pg_num_rows($result2);
		
		for ($row2 = 0; $row2 < $rows2; $row2++ ){
			$record2 = pg_fetch_row($result2, $row2);
			$title = $record2[0];
		}
		
		if (strlen($season) > 0) {
			$query3 = "select title from tv_episodes where showtconst = '$tconst' and season = $season and episode = $episode;";
			print "<!-- query = '$query3' -->\n";
			
			$result3 = pg_query($htdb_conn, $query3);
		
			$rows3 = pg_num_rows($result3);
			
			for ($row3 = 0; $row3 < $rows3; $row3++ ){
				$record3 = pg_fetch_row($result3, $row3);
				$epititle = $record3[0];
			}
			
			if (strlen($epititle) > 0) {
				$title = "$title - $epititle s$season"."e$episode";
			}
		}
	}
	
	$geometry = "width=95%";
	$width = "1440";
	$height = "712";
	$video = trim($video);
	print "<!-- video = '$video' -->\n";
	
	if (strlen($video) > 0) {
		list($nada_one, $nada_two, $var_three, $var_four, $nada_five) = explode(',', $video);
		$x = strpos($var_four, "x");
		print "<!-- offset = '$x' -->\n";
		if ($x > 0) {
			$geometrya = $var_four;
		} 
		$x = strpos($var_three, "x");
		print "<!-- offset2 = '$x' -->\n";
		if ($x > 0) {
			$geometrya = $var_three;
		}
		list($nada_zero, $geometryb, $nada_one, $nada_two) = explode(" ", $geometrya);
		print "<!--  geometryb = '$geometryb' -->\n";

		list($width, $height) = explode("x", $geometryb);
		print "<!--  width='$width', height='$height'  -->\n";

		$calc = $height*1.45;
		print "<!-- geometrya = '$geometrya', geometryb = '$geometryb', height='$height', width='$width', calc = '$calc' -->\n";

		$geometry = "width=95%";
		if ($width <= ($height*1.45)) {
			$geometry = "width=60%";
		} else {
			$geometry = "width=95%";
		}
	}
}

print "<!-- filename = '$filename', tconst = '$tconst', mtype = '$mtype' -->\n";
$fullpath = "$movie_path$filename";
$fullpath = str_replace(" ", "%20", $fullpath);
$fullpath = str_replace(";", "%3B", $fullpath);

print "<!-- fullpath = '$fullpath', tconst = '$tconst', mtype = '$mtype' -->\n";

if ($use_geometry == "no") {
	$geometry = "";
}

print "<head>\n";
print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
print "<meta name=\"mobile-web-app-capable\" content=\"yes\">\n";
print "<title>$title</title>\n";
print "<style>\n";
print ".container {\n";
print "  position: relative;\n";
print "  object-fit: contain;\n";
print "  text-align: center;\n";
print "  color: white;\n";
print "}\n";
print ".titletext {\n";
print "  font-family: sans-serif;\n";
print "  font-size: 30px;\n";
print "  position: absolute;\n";
print "  color: rgba(255, 255, 255, 0.5);\n";
print "  opacity: 0;\n";
print "  top: 8px;\n";
print "  left: 16px;\n";
print "  -webkit-animation: cssAnimation 15s forwards;\n";
print "}\n";
print "@-webkit-keyframes cssAnimation {\n";
print "    0%   {opacity: 0.5;}\n";
print "    90%  {opacity: 0.5;}\n";
print "    100% {opacity: 0;}\n";
print "}\n";
print "</style>\n";
print "</head>\n";
if ($currenttime > 0) {
	print "<body bgcolor=#000000 onload=\"setCurTime();\">\n";
} else {
	print "<body bgcolor=#000000 >\n";
	$currenttime = "0";
}
//$geometry = "";
//print "<p>&nbsp;</p>\n";
//print "<p></p>\n";
print "<div class=\"container\">\n";
//print "<table width=100% >\n";
//print "	<tr>\n";
//print "		<td valign=center align=center>\n";
print "			<video  id=\"htdbVideo\" controls $geometry preload=\"auto\" autoplay alt-text=\"$title\">\n";
print "				<source src=\"http://$server"."$fullpath\" type=\"video/$mtype\">\n";
print "			</video>\n";
//print "		</td>\n";
//print "	</tr>\n";
//print "</table>\n";
print "<div class=\"titletext\">$title</div>\n";
print "</div>\n";
print "<script>\n";
print "	top.document.title=\"HTDB - $title\"\n";
print "</script>\n";
print "<script>\n";
print "\n";
print "function getCurTime() {\n";
print "	var vid = document.getElementById(\"htdbVideo\");\n";
//print "	var xmlhttp = new XMLHttpRequest();\n";
//print "	xmlhttp.open(\"POST\", \"http://$server/htdb/sendcurrentplay.php?c=$cookie_client&id=$ID&t=\" + vid.currentTime, false);\n";
//print "	xmlhttp.send();\n";
print "		var url = \"http://$server/htdb/sendcurrentplay.php\";\n";
print "		var data = \"c=$cookie_client&id=$ID&t=\" + vid.currentTime;\n";
print "		var status = navigator.sendBeacon(url, data);\n";
print "}\n";
print "\n";
if ($currenttime > 0) {
	print "function setCurTime() {\n";
	print "	var vid = document.getElementById(\"htdbVideo\");\n";
	print "	vid.currentTime=$currenttime;\n";
	print "}\n";
}
print "</script>\n";
print "<script>\n";
print "	window.onbeforeunload=function (){getCurTime();};\n";
print "</script>\n";
print "<script>\n";
print "	window.onunload=function (){getCurTime();};\n";
print "</script>\n";

print "<!-- cookie '$cookie_client' -->\n";
if (strlen($cookie_client) > 0) {
	$query = "insert into client_playing (client, ttype, id) values ('$cookie_client', '$ttype', '$ID');";
	print "<!-- query '$query' -->\n";
	$result = pg_query($htdb_conn, $query);
}

pg_close($htdb_conn);

//if (strpos($client, "Android") > 0) {
//	print "<br>\n";
//	print "<form name=\"form1\" method=\"post\">\n";
//	print "<button onclick=\"getCurTime()\" type=\"button\">Save Current time</button>\n";
//	print "</form>\n";
//}
?>
</body>
</html>
