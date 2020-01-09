#!/usr/bin/python

import _pg
import os
import commands
import string


htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

cast_cmd = "/usr/local/bin/cast --timeout 1s  discover"
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
		
#		query = "insert into chromecast_devices (name, ipaddress, localip) values ('"+cast_name+"', '"+cast_ip_address+"', '"+my_ip+"');"
		query = "update chromecast_devices set ipaddress = '"+cast_ip_address+"' where name = '"+cast_name+"';"
		print query
		result = htdb_db.query(query)
		
		cast_cmd = "/usr/local/bin/cast --host "+cast_ip_address+" --port 8009 status"
		print cast_cmd
		buffer = commands.getoutput(cast_cmd)
		buff_parts = string.split(buffer, "\n")
		for line in buff_parts:
			if string.find(line, "Volume: ") >= 0:
				temp_data = string.replace(line, "Volume: ", "")
				query = "update chromecast_devices set volume = '"+temp_data+"' where name = '"+cast_name+"';"
				print query
				result = htdb_db.query(query)

buffer = commands.getoutput("/usr/bin/hdhomerun_config discover")
print buffer
if string.find(buffer, "found at") > 0:
	parts = string.split(buffer, "\n")
	for part in parts:
		if string.find(part, "found at") > 0:
			devices = string.split(part, " ")
			device = str(devices[2])
			address = str(devices[5])
			print device, address
			query = "update hdhomerun_devices set ipaddress = '"+address+"' where deviceid = '"+device+"';"
			print query
			result4 = htdb_db.query(query)

query = "select starttime, runtime, channelid, extract(epoch from (starttime-now())) as offset, id as rec_id from chromecast_tune where starttime > now() and tunestatus = 'pending' order by starttime;"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

for record in result_list:
	starttime = str(record['starttime'])
	runtime = str(record['runtime'])
	channelid = str(record['channelid'])
	offset = int(round(record['offset']))
	rec_id = str(int(record['rec_id']))
	
	print "'"+channelid+"', '"+starttime+"', '"+runtime+"', '"+str(offset)+"'"
	print offset
#	offset = 900
	if offset < 1000:
		cast_cmd = "/var/www/html/htdb/chromecast_play.py "+channelid+" tvstation 0 \""+starttime+"\" \""+runtime+"\" > /var/www/html/htdb/tuner_file"+channelid+".log &"
		
		query2 = "update chromecast_tune set tunestatus = 'waiting' where id = '"+rec_id+"';"
		print query
		result2 = htdb_db.query(query2)
		
		print cast_cmd
		os.system(cast_cmd)

# /home/tim/Documents/IMDB-data/recordings_check.py

htdb_db.close()
