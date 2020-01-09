<!DOCTYPE html>
<html>


<?php

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$tvID = $_GET['tvID'];

$client = $_SERVER["HTTP_USER_AGENT"];
$start = strpos($client, "(");
$end = strpos($client, ")");
$len = $end-$start;
$client = substr($client, $start, $len);
$start = strpos($client, ";");
$client = substr($client, $start+1);
print "<!-- client = '$client' -->\n";

$use_geometry = "yes";
if (strpos($client, "Android") > 0) {
	$use_geometry = "no";
}

//$command = "/usr/bin/whoami";
//$me = exec($command);
//print "<!-- who am i '$me' -->\n";

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");

//$query = "select filename, tconst, video, id, folder_id from movie_files where id = $ID;";
$query = "select a.filename, a.tconst, a.video, a.id, a.folder_id, a.folder, b.web_path, a.season, a.episode from tv_files as a, folders as b where a.id = $tvID and b.id = a.folder_id;";

//print "<!-- query = '$query' -->\n";

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
	$title = $filename;
	$epititle = "";
	
	$season = $record[7];
	$episode = $record[8];
	
	$movie_path = "$movie_path$folder_path";
	
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
		
		$query3 = "select title from tv_episodes where showtconst = '$tconst' and season = $season and episode = $episode;";
		print "<!-- query = '$query3' -->\n";
		
		$result3 = pg_query($htdb_conn, $query3);
		
		$rows3 = pg_num_rows($result3);
		
		for ($row3 = 0; $row3 < $rows3; $row3++ ){
			$record3 = pg_fetch_row($result3, $row3);
			$epititle = $record3[0];
		}
	}
	
	if (strlen($epititle) > 0) {
		$title = "$title - $epititle";
	}
	
	$geometry = "1440x712";
	$width = "1440";
	$height = "712";
	$video = trim($video);
	print "<!-- video = '$video' -->\n";
	
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
	
	$calc = $height*1.5;
	print "<!-- geometrya = '$geometrya', geometryb = '$geometryb', width='$width', height='$height' calc = '$calc' -->\n";
	
	$geometry = "width=95%";
	if ($width < ($height*1.49)) {
		$geometry = "width=70%";
	} else {
		$geometry = "width=95%";
	}
}

print "<!-- filename = '$filename', tconst = '$tconst', mtype = '$mtype' -->\n";

//if ($use_geometry == "no") {
//	$geometry = "";
//}

//$geometry = "width=95%";

print "<head>\n";
print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
print "<meta name=\"mobile-web-app-capable\" content=\"yes\">\n";
//print "<style>\n";
//print "	.contain {\n";
//print "  object-fit: contain;\n";
//print "}\n";
//print "</style>\n";
print "<title>$title</title>\n";
print "</head>\n";
print "<body bgcolor=#000000>\n";
print "<table width=100% >\n";
print "	<tr>\n";
print "		<td valign=center align=center>\n";
print "			<video controls $geometry preload=\"auto\" autoplay >\n";
print "				<source src=\"http://$server"."$movie_path"."$filename\" type=\"video/$mtype\">\n";
print "			</video>\n";
print "		</td>\n";
print "	</tr>\n";
print "</table>\n";

pg_close($htdb_conn);
?>

</body>
</html>
