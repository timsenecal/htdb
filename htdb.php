<!DOCTYPE html>
<html>
<head>
<link rel="apple-touch-icon" sizes="128x128" href="htdb.png"> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="mobile-web-app-capable" content="yes">
<title>HTDB</title>

<style>
a { color: black; font-family: sans-serif; text-decoration: none;} /* CSS link color */
* {
	height: 99%; /*this is for example, you can set your height like 100vh or 100%*/
	box-sizing: border-box; /*reset box sizing for all elements*/
}

.flex_container {
	height: 100%; /*this is for example, you can set your height like 100vh or 100%*/
	display: flex;
	flex-flow: column wrap;
}
.flex_child {
	height: 100%;
/*	border: 1px solid #012;*/
	width: 260px;
}
.mid {
	height: 50px;
	width: calc(100% - 260px);
}
.bot {
/*	height: calc(100% - 50px);*/
	height: 100%;
	width: calc(100% - 260px);
}
</style>

</head>
<body bgcolor=#FFFFFF forecolor=#000000 height=100%>

<?php 

$server = $_SERVER['SERVER_ADDR'];
$server_name = $_SERVER['SERVER_NAME'];
print "<!-- '$server', '$server_name' -->\n";
if ($server_name != $server) {$server = $server_name;}

print "<div class=\"flex_container\">\n";
print "<div class=\"flex_child\"><object name=\"menu\" id=\"menu\" type=\"text/html\" data=\"http://$server/htdb/htdbmenu.php\"></object></div>\n";
print "<!-- <div class=\"flex_child mid\">_2_</div> -->\n";
print "<div class=\"flex_child bot\"><object name=\"middle\" id=\"middle\" type=\"text/html\" data=\"http://$server/htdb/htdbhome.php\" width=\"100%\" height=\"100%\"></object></div>\n";
print "</div>\n";

?>

</body>
</html>