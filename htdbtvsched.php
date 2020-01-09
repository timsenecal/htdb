<!DOCTYPE html>
<html>
<head>
<link rel="apple-touch-icon" sizes="132x132" href="htdb.png"> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="mobile-web-app-capable" content="yes">
<title>HTDB</title>
<script>
function SyncScrollh() {
	var div = document.getElementById('header');
    var div1 = document.getElementById('schedule');
    div1.scrollLeft = div.scrollLeft;
}
function SyncScrollc() {
    var div = document.getElementById('channels');
	var div1 = document.getElementById('schedule');
    div1.scrollTop = div.scrollTop;
}
function SyncScrolls() {
	var div = document.getElementById('schedule');
    var div2 = document.getElementById('channels');
    div2.scrollTop = div.scrollTop;
    var div1 = document.getElementById('header');
    div1.scrollLeft = div.scrollLeft;
}
</script>
<style>
* {
  /* So 100% means 100% */
  box-sizing: border-box;
}
body, html {
    padding: 0;
    margin: 0;
    height: 100%;
}

.box-container {
    padding: 0;
    margin: 0;
    float: left;
    width: 100%;
    height: 100%;
	background: white;
	overflow: hidden;
}

.box-a {
    padding: 0;
    margin: 0;
    box-sizing: border-box;
    float: left;
    width: 160px;
    height: 32px;
	background: white;
}

.box-b {
    padding: 0;
    margin: 0;
    float: left;
	width: calc(100% - 160px);
	width: -moz-calc(100% - 160px);
	width: -webkit-calc(100% - 160px);
    height: 32px;
	background: white;
	overflow: auto;
}
.box-b::-webkit-scrollbar { 
	display: none; 
} 
.box-b::-scrollbar { 
	display: none; 
} 
.box-c {
    padding: 0;
    margin: 0;
    float: left;
	width: 160px;
	height: calc(100% - 50px);
	height: -moz-calc(100% - 50px);
	height: -webkit-calc(100% - 50px);
	background: white;
	overflow: auto;
}
.box-c::-webkit-scrollbar { 
	display: none; 
} 
.box-c::-scrollbar { 
	display: none; 
} 
.box-d {
    padding: 0;
    margin: 0;
    float: left;
	width: calc(100% - 160px);
	width: -moz-calc(100% - 160px);
	width: -webkit-calc(100% - 160px);
	height: calc(100% -32px);
	height: -moz-calc(100% - 32px);
	height: -webkit-calc(100% - 32px);
	background: white;
	overflow: auto;
}
</style>

</head>
<body bgcolor=#FFFFFF forecolor=#000000 height=100%>
<script>
	top.document.title="HTDB - TV Schedule"
</script>

<?php

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

$htdb_conn = pg_connect ("host=localhost dbname=htdb user=htdb password=htdb");
$query = "select channelid from tv_channel_info where active = 'yes' order by channelid;";
$result = pg_query($htdb_conn, $query);
$rows = pg_num_rows($result);
$height = ($rows * 50)+22;
print "<!-- rows='$rows', height='$height'-->\n";

print "<div class=\"box-container\">\n";
print "<div class=\"box-a\" id=\"corner\" ><object type=\"text/html\" width=158 height=50 data=\"http://$server/htdb/htdbtvschedcorner.php\"></object></div>\n";
print "<div class=\"box-b\" id=\"header\" onscroll=\"javascript:SyncScrollh()\" ><object type=\"text/html\" width=7304 height=50 data=\"http://$server/htdb/htdbtvschedheader.php\"></object></div>\n";
print "<div class=\"box-c\" id=\"channels\" onscroll=\"javascript:SyncScrollc()\" ><object type=\"text/html\" width=158 height=$height data=\"http://$server/htdb/htdbtvschedchannels.php\"></object></div>\n";
print "<div class=\"box-d\" id=\"schedule\" onscroll=\"javascript:SyncScrolls()\" ><object type=\"text/html\" width=7286 height=$height data=\"http://$server/htdb/htdbtvschedbody.php\" ></object></div>\n";
print "</div>";

pg_close($htdb_conn);

?>

</body>
</html>