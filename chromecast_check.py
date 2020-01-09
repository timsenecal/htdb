#!/usr/bin/python

import _pg
import os
import commands
import string
import socket
import sys

skip = "no"
try:
	skip = sys.argv[1]
except:
	skip = "no"

print "skip", skip

cast_ip_address = "0.0.0.0"
cast_name = "nada"

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

def get_ip():
	s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	try:
		# doesn't even have to be reachable
		s.connect(('10.255.255.255', 1))
		IP = s.getsockname()[0]
	except:
		IP = '127.0.0.1'
	finally:
		s.close()
	
	return IP

my_ip = get_ip()

if skip == "no":
	cast_vol = "1.0"
	cast_cmd = "/usr/local/bin/cast --timeout 2s  discover"
	print cast_cmd
	buffer = commands.getoutput(cast_cmd)
	buff_parts = string.split(buffer, "\n")
	for line in buff_parts:
		if string.find(line, "Found: ") >= 0:
			temp_data = line
			temp_data = string.replace(temp_data, "Found: ", "")
			temp_parts = string.split(temp_data, " ")
			temp_ip = temp_parts[0]
			temp_name = temp_parts[1]
			offset = string.find(temp_ip, ":")
			temp_ip = temp_ip[:offset]
			cast_ip_address = temp_ip
			cast_name = string.replace(temp_name, "'", "")

			print cast_name, cast_ip_address
		
			cast_cmd = "/usr/local/bin/cast --host "+cast_ip_address+" --port 8009 status"
			print cast_cmd
			buffer2 = commands.getoutput(cast_cmd)
			buff_parts2 = string.split(buffer2, "\n")
			for line2 in buff_parts2:
				if string.find(line2, "Volume: ") >= 0:
					cast_vol = string.replace(line2, "Volume: ", "")

#			query = "delete from chromecast_devices;"
#			print query
#			result = htdb_db.query(query)

#			query = "update chromecast_devices (name, ipaddress, localip) values ('"+cast_name+"', '"+cast_ip_address+"', '"+my_ip+"');"
#			print query
#			result = htdb_db.query(query)

			query = "update chromecast_devices set ipaddress = '"+cast_ip_address+"', localip = '"+my_ip+"', volume = '"+cast_vol+"' where name = '"+cast_name+"';"
			print query
			result = htdb_db.query(query)


query2 = "select ipaddress, vlcport, localip from chromecast_devices;"
print query2
result2 = htdb_db.query(query2)
result2_list = list(result2.dictresult())
for record2 in result2_list:
	ipaddress = str(record2['ipaddress'])
	vlcport = str(record2['vlcport'])
	localip = str(record2['localip'])
	
	if vlcport == "None":
		vlcport = "9010"
	
	print ipaddress, vlcport, localip
	
	ctime = "0"
	runtime = "0"
	state = "n/a"
	repeat = "no"
	loop = "no"
	filename = ""
	
	status_cmd = "/usr/bin/curl -s -u :vlchtdb \"http://127.0.0.1:"+vlcport+"/requests/status.xml\""
	print status_cmd
	buffer = commands.getoutput(status_cmd)
	buff_parts = string.split(buffer, "\n")
	for line in buff_parts:
#		print line
		if string.find(line, "<time>") >= 0:
			print line
			ctime = string.replace(line, "<time>", "")
			ctime = string.replace(ctime, "</time>", "")
			print ctime
			
		if string.find(line, "<state>") >= 0:
			print line
			state = string.replace(line, "<state>", "")
#			state = string.replace(state, "</state>", "")
			offset = string.find(state, "</state>")
			if offset > 0:
				state = state[:offset]
			print state
			
		if string.find(line, "<length>") >= 0:
			print line
			runtime = string.replace(line, "<length>", "")
			runtime = string.replace(runtime, "</length><information>", "")
			runtime = string.replace(runtime, "</length>", "")
			print runtime
			
		if string.find(line, "<loop>") >= 0:
			print line
			loop = string.replace(line, "<loop>", "")
			loop = string.replace(loop, "</loop>", "")
			print loop
			
		if string.find(line, "<repeat>") >= 0:
			print line
			repeat = string.replace(line, "<repeat>", "")
			repeat = string.replace(repeat, "</repeat>", "")
			print repeat
			
		if string.find(line, "<info name='filename'>") >= 0:
			print line
			offset = string.find(line, "<info name='filename'>")+len("<info name='filename'>")
			if offset > 0:
				
				filename = line[offset:]
#				filename = string.replace(filename, "</info>    </category>", "")
				offset = string.find(filename, "</info>")
				if offset > 0:
					filename = filename[:offset]
				
				print filename

	print ipaddress, filename, ctime, runtime, state, loop, repeat
	
	query = "update chromecast_playing set ctime = '"+ctime+"', runtime = '"+runtime+"', status = '"+state+"', xmlfilename = '"+filename+"' where ipaddress = '"+ipaddress+"';"
	if filename == "":
		query = "delete from chromecast_playing where ipaddress = '"+ipaddress+"';"
	print query
	result_update = htdb_db.query(query)


htdb_db.close()
