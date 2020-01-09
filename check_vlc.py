#!/usr/bin/python

import _pg
import os
import sys
import time
import commands
import string
import socket
import os.path


#each chromecast gets its own instance of vlc to support it, with ports starting at 9010
http_port = 9010

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

query = "select ipaddress from chromecast_devices;"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())
for record in result_list:
	relaunch_vlc = "yes"
	
	device_ip = str(record['ipaddress'])

	print "chromecast ip:", device_ip
	
	http_port_str = str(int(http_port))

	check_cmd = "/bin/ps -ewf | /bin/grep vlc"
	print check_cmd
	device_dump = commands.getoutput(check_cmd)
	#print device_dump
	lines = string.split(device_dump, "\n")
	for line in lines:
		print line
		if string.find(line, " /usr/bin/vlc") > 0:
			if string.find(line, "chromecast") > 0:
				if string.find(line, http_port_str) > 0:
					print "vlc is running"
					relaunch_vlc = "no"

	if relaunch_vlc == "yes":
		print "vlc is not running"
		vlc_cmd = "/usr/bin/vlc --intf http --http-host 127.0.0.1 --http-port "+http_port_str+" --http-password=\"vlchtdb\" --sout \"#chromecast\" --sout-chromecast-ip="+device_ip+"  --demux-filter=demux_chromecast 2>&1 > /var/www/html/htdb/vlcdaemon.log &"
		print vlc_cmd
		os.system(vlc_cmd)
	
	#increment port for next chromecast
	http_port = http_port+1

htdb_db.close()