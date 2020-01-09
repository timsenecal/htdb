#!/usr/bin/python

import _pg
import os
import sys
import time
import commands
import string
import socket
import os.path

chromecast_id = "0"
chromecast_time = "0"
chromecast_run = 0
client_label = ""

item_id = sys.argv[1]
item_type = sys.argv[2]

try:
	chromecast_id = sys.argv[3]
except:
	chromecast_id = "0"

try:
	chromecast_time = sys.argv[4]
except:
	chromecast_time = "0"
	
try:
	chromecast_run = sys.argv[5]
except:
	chromecast_run = "0"
	
try:
	client_label = sys.argv[6]
except:
	client_label = ""

print item_id, item_type, chromecast_id, chromecast_time, chromecast_run

cast_ip_address = "0.0.0.0"
cast_name = "nada"

chromecast_tune_time = chromecast_time

if string.find(chromecast_time, " ") > 0:
	chromecast_time = string.replace(chromecast_time, ":", "")
	chromecast_time = string.replace(chromecast_time, "-", "")
	chromecast_time = string.replace(chromecast_time, " ", "")

if string.find(chromecast_run, ":") > 0:
	run_parts = string.split(chromecast_run, ":")
	h = int(run_parts[0])
	m = int(run_parts[1])
	s = int(run_parts[0])
	
	# math is hours + minutes + seconds + 75 seconds to offest initial 15 second advance, and add an additional minute on the backside
	chromecast_run = (h*3600)+(m*60)+s+75
else:
	chromecast_run = 0


chromecast_offset = "no"

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
		
		query = "delete from chromecast_devices;"
		print query
		result = htdb_db.query(query)
		
		query = "insert into chromecast_devices (name, ipaddress, localip) values ('"+cast_name+"', '"+cast_ip_address+"', '"+my_ip+"');"
		print query
		result = htdb_db.query(query)

query = "select ipaddress, localip from chromecast_devices;"
result = htdb_db.query(query)
result_list = list(result.dictresult())
for record in result_list:
	cast_ip_address = str(record['ipaddress'])
	my_ip = str(record['localip'])

if chromecast_time != "0":
	print "waiting for:  "+chromecast_time

	thing = time.localtime()

	test_block = time.strftime("%Y%m%d%H%M%S", thing)

	print "current time: "+test_block

	counter = 0
	while test_block < chromecast_time:
		time.sleep(10)

		thing = time.localtime()

		test_block = time.strftime("%Y%m%d%H%M%S", thing)

		counter = counter + 1

		if counter == 30:
			print "current time: "+test_block
			counter = 0

title = ""
episode = ""
query = ""
print item_type
if item_type == "movie":
	query = "select mf.filename, f.folder_path, '' as folder, mf.primarytitle, '' as episodenum, mf.runtime from movie_files as mf, folders as f where mf.id = '"+item_id+"' and mf.folder_id = f.id;"
	
if item_type == "tvshow":
	query = "select tf.filename, f.folder_path, tf.folder, tf.primarytitle, ('s'||tf.season||'e'||tf.episode) as episodenum, tf.runtime from tv_files as tf, folders as f where tf.id = '"+item_id+"' and tf.folder_id = f.id;"

if item_type == "music":
	query = "select mf.filename, f.folder_path, mf.folder, mf.songtitle as primarytitle, '' as episodenum, mf.runtime from music_files as mf, folders as f where mf.id = '"+item_id+"' and mf.folder_id = f.id;"

if item_type == "photo":
	query = "select filename, folder_id, folder, primarytitle, '' as episodenum, '0' as runtime from photo_files where id = '"+item_id+"';"
	#no photo table exists yet, so...
	query = ""
	
if item_type == "tvstation":
	query = ""
	title = "Channel "+item_id
	query2 = "select hd.ipaddress from hdhomerun_channels as hc, hdhomerun_devices as hd where hc.channelid = "+item_id+" and hc.deviceid = hd.deviceid and hd.tuner_used < hd.tunercount;";
	print query2
	result2 = htdb_db.query(query2)
	result2_list = list(result2.dictresult())
	for record2 in result2_list:
		tuner_address = str(record2['ipaddress'])
		print tuner_address
		
		# create the vlc url path needed to convert hdhomerun video to chromecast video
		#urlpath = "http://"+tuner_address+":5004/auto/v"+item_id+"?transcode=mobile"
		folderpath = "http://"+tuner_address+":5004/auto/"
		filename = "v"+item_id
		
		
		#make a dummy database query for the vlc url path
		query = "select '"+filename+"' as filename, '"+folderpath+"' as folder_path, '' as folder, '"+title+"' as primarytitle, '' as episodenum, '0' as runtime;";

print "query equals: '"+query+"'"

if len(query) > 0:
	result = htdb_db.query(query)
	result_list = list(result.dictresult())
	for record in result_list:
		filename = str(record['filename'])
		folder = str(record['folder'])
		title = str(record['primarytitle'])
		episode = str(record['episodenum'])
		folderpath = str(record['folder_path'])
		runtime = str(record['runtime'])
		
		temp = int(runtime)
		temp = temp*60;
		runtime = str(int(temp))
		
		file_path = folderpath+folder+filename
		
		file_path = string.replace(file_path, "&", "&amp;")
		file_path = string.replace(file_path, " ", "%20")
		
		check_cmd = "/bin/ps -ewf | /bin/grep vlc"
		print check_cmd
		device_dump = commands.getoutput(check_cmd)
		#print device_dump
		lines = string.split(device_dump, "\n")
		for line in lines:
			if string.find(line, " /usr/bin/vlc") > 0:
				if string.find(line, "chromecast") > 0:
					print line
#					curl_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=pl_stop\""
					curl_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=in_play&input="+file_path+"\""
					print curl_cmd
					os.system(curl_cmd)
					
					query = "delete from chromecast_playing where ipaddress = '"+cast_ip_address+"';"
					result = htdb_db.query(query)
					query = "insert into chromecast_playing (ipaddress, ttype, file_id, title, episode, filepath, filename, runtime) values ('"+cast_ip_address+"', '"+item_type+"', '"+item_id+"', '"+title+"', '"+episode+"', '"+file_path+"', '"+filename+"', '"+runtime+"');"
					if item_type == "tvstation":
						query = "insert into chromecast_playing (ipaddress, ttype, channelid, title, episode, filepath, filename, runtime) values ('"+cast_ip_address+"', '"+item_type+"', '"+item_id+"', '"+title+"', '"+episode+"', '"+file_path+"', '"+filename+"', '"+runtime+"');"
					print query
					result = htdb_db.query(query)
					
					# we might be here because of a tv auto tune record
					if item_type == "tvstation":
						if chromecast_tune_time != "0":
							query = "update chromecast_tune set tunestatus = 'playing' where channelid = '"+item_id+"' and starttime = '"+chromecast_tune_time+"';"
							print query
							result = htdb_db.query(query)
					
					if len(client_label) > 0:
						query = "insert into client_playing (client, ttype, id) values ('"+client_label+"', '"+item_type+"', '"+item_id+"');"
						try:
							result = htdb_db.query(query)
							print query
						except:
							query = "select currenttime from client_playing where client = '"+client_label+"' and ttype = '"+item_type+"' and id = '"+item_id+"';"
							print query
							result_time = htdb_db.query(query)
							result_time_list = list(result_time.dictresult())
							for recordtime in result_time_list:
								ctime = str(recordtime['currenttime'])
								chromecast_offset = "yes"
							
							query = "update client_playing set stamp = now() where client = '"+client_label+"' and ttype = '"+item_type+"' and id = '"+item_id+"';"
							print query
							result = htdb_db.query(query)
							
if chromecast_offset == "yes":
	curl_cmd = "/usr/bin/curl -s -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=seek&val="+ctime+"s\""
	print curl_cmd
	os.system(curl_cmd)

if chromecast_run > 0:
	print "item has a time of ", chromecast_run, "seconds... will wait."
	time.sleep(chromecast_run)
	curl_cmd = "/usr/bin/curl -u :vlchtdb \"http://127.0.0.1:9010/requests/status.xml?command=pl_stop\""
	print curl_cmd
	os.system(curl_cmd)
	query = "delete from chromecast_playing where ipaddress = '"+cast_ip_address+"';"
	print query
	result = htdb_db.query(query)
	
	if item_type == "tvstation":
		if chromecast_tune_time != "0":
			query = "update chromecast_tune set tunestatus = 'finished' where channelid = '"+item_id+"' and starttime = '"+chromecast_tune_time+"';"
			print query
			result = htdb_db.query(query)


htdb_db.close()
