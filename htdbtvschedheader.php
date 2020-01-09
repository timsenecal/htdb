<!DOCTYPE html>
<html>
<head>
</head>
<body bgcolor=#FFFFFF forecolor=#000000>

<svg width=7292 height=24 xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" >
<?php

$h_start = date("g")+1;
if ($h_start == 0) {
	$h_start = 12;
}
$tag = date("a");
$time_offset = 60 - date("i");
$box_left = 2+$time_offset * 5;
$time_thing = date("g:i a");
if ($box_left < 2) {
	$box_left = $box_left+300;
	$h_start = $h_start+1;
}


print "<!-- time '$time_thing' main offset '$time_offset' box_start = '$box_left' -->\n";

print "<rect x=\"2\" y=\"-2\" width=\"300\" height=\"24\" style=\"fill:white;stroke:black;stroke-width:2;opacity:1.0\" />\n";
print "<text x=\"10\" y=\"15\" style=\"font-family: sans-serif; font-weight: bold; font-style: bold\" fill=\"Black\" >$time_thing</text>\n";

for ($i=0;$i<24;$i++) {
	$text_left = $box_left+8;
	if ($h_start == 12) {
		if ($tag == "pm") {
			$tag = "am";
		} else {
			$tag = "pm";
		}
	}
	print "<rect x=\"$box_left\" y=\"-2\" width=\"300\" height=\"24\" style=\"fill:white;stroke:black;stroke-width:2;opacity:1.0\" />\n";
	print "<text x=\"$text_left\"y=\"15\" style=\"font-family: sans-serif; font-weight: bold; font-style: bold\" fill=\"Black\" >$h_start:00 $tag</text>\n";
	$h_start = $h_start+1;
	if($h_start > 12) {
		$h_start = 1;
	}
	$box_left = $box_left+300;
}

?>
</svg>

</body>
</html>
