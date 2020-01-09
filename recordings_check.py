#!/usr/bin/python

import _pg
import os
import commands
import string
import sys


tuner_id = "0"
tuner_address = "0"
transcode = "no"
duration = "no"
cur_tuner = "nada"

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

def find_tconst(title, runtime):
	tconst = "nada"
	query = "select distinct tconst from tv_files where primarytitle = '"+title+"';"
	print query
	result = htdb_db.query(query)
	result_list = list(result.dictresult())
	for record in result_list:
		tconst = str(record['tconst'])
		
	if tconst == "nada":
		query = "select tconst from title_basics where primarytitle = '"+title+"' and ttype = 'tvseries' and runtime_int = "+runtime+" order by tconst limit 1;"
		print query
		result = htdb_db.query(query)
		result_list = list(result.dictresult())
		for record in result_list:
			tconst = str(record['tconst'])
			
	if tconst == "nada":
		query = "select tconst from alt_titles where alttitle = '"+title+"' order by tconst limit 1;"
		print query
		result = htdb_db.query(query)
		result_list = list(result.dictresult())
		for record in result_list:
			tconst = str(record['tconst'])
	
	return tconst

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

id = ""
try:
	id = sys.argv[1]
except:
	id = ""

print "id = ",id

query = "select starttime, runtime, channelid, title, episodetitle, episodenum, extract(epoch from (starttime-now())) as offset, id as rec_id, episodenumdd, tconst from tv_recording where starttime > now() and recordstatus = 'pending' order by starttime;"
if len(id) > 0:
	query = "select starttime, runtime, channelid, title, episodetitle, episodenum, extract(epoch from (starttime-now())) as offset, id as rec_id, episodenumdd, tconst from tv_recording where id = '"+id+"';"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

for record in result_list:
	starttime = str(record['starttime'])
	runtime = str(record['runtime'])
	channelid = str(record['channelid'])
	title = str(record['title'])
	episode = str(record['episodetitle'])
	episode_num = str(record['episodenum'])
	offset = int(round(record['offset']))
	rec_id = str(int(record['rec_id']))
	episodenumdd = str(record['episodenumdd'])
	tconst = str(record['tconst'])
	
	episode = string.replace(episode, ": ", " - ")
	episode = string.replace(episode, "/", ".")
	
	video_type = "tvshow"
	folder_id = "2"
	folder_path = "/var/www/html/htdb/htdb-tvshows/TV_shows/"

	query = "select folder_path, id from folders where folder_type = 'TV';"
	
	test = episodenumdd[0:2]
	if test == "MV":
		video_type = "movie"
		folder_id = "1"
		folder_path = "/var/www/html/htdb/htdb-movies/Movies/"

		query = "select folder_path, id from folders where folder_type = 'Movies';"
		episode_num = ""
		episode = ""
		season = ""
		
		
		
	print query
	result = htdb_db.query(query)
	result_list = list(result.dictresult())
	for record in result_list:
		folder_path = str(record['folder_path'])
		folder_id = str(int(record['id']))
	
	primarytitle = title
	parts = string.split(title, ":")
	title = parts[0]
	
	print "'"+title+"', '"+episode+"', '"+channelid+"', '"+starttime+"', '"+runtime+"', '"+str(offset)+"'"
#	print offset
#	offset = 900
	if offset < 1000:
		foldertitle = string.replace(title, " ", "_")
		
		if video_type == "tvshow":
			season = episode_num
			parts = string.split(season, "E")
			season = parts[0]
			season = string.replace(season, "S", "Season_")
			season = season

			if len(season) == 0:
				season = "0"

			filename = title
			if len(episode_num) > 0:
				filename = title+" - "+episode_num+" - "+episode
			
			#make parent show directory - should exist after first recording
			folder_cmd = "/bin/mkdir '"+folder_path+foldertitle+"'"
			print folder_cmd
			os.system(folder_cmd)
			
			#make season directory
			folder_cmd = "/bin/mkdir '"+folder_path+foldertitle+season+"'"
			print folder_cmd
			os.system(folder_cmd)
		else:
			filename = title
		
		device_option = ""
		query = "select deviceid from hdhomerun_channels where tvchannel = '"+channelid+"' order by deviceid;"
		print query
		result2 = htdb_db.query(query)
		result2_list = list(result2.dictresult())
		for record in result2_list:
			deviceid = str(record['deviceid'])
			device_option = "deviceid = '"+deviceid+"'"
		
		
	#		query = "select ipaddress, tunercount, transcode, duration, tuner_one, tuner_two, deviceid from hdhomerun_devices where "+device_option+" tuner_used < tunercount order by deviceid limit 1;"
			query = "select ipaddress, tunercount, transcode, duration, tuner_one, tuner_two, deviceid from hdhomerun_devices where "+device_option+" order by deviceid limit 1;"
			print query
			result2 = htdb_db.query(query)
			result2_list = list(result2.dictresult())

			for record in result2_list:
				tuner_address = str(record['ipaddress'])
				tuner_id = str(record['deviceid'])
				cur_tuner = str(record['tuner_one'])
				if cur_tuner == 'pending':
					cur_tuner = "tuner1"
				else:
					cur_tuner = "tuner0"
				if cur_tuner == 'on':
					cur_tuner = "tuner1"
				else:
					cur_tuner = "tuner0"
				transcode = str(record['transcode'])
				
				if video_type == "tvshow":
					foldertitle = foldertitle+"/"
					season = season+"/"
				else:
					foldertitle = ""
					season = ""
					episode = ""
				
				foldertitle = string.replace(foldertitle, "//", "/")
				season = string.replace(season, "//", "/")
				
				if transcode == "no":
					recording_cmd = "/home/tim/Documents/IMDB-data/htdb-tvdata/hdhomerun_http.py '"+starttime+"' "+tuner_address+" "+cur_tuner+" none "+channelid+" "+runtime+" '"+folder_path+foldertitle+season+filename+"' "+rec_id+""
				else:
					recording_cmd = "/home/tim/Documents/IMDB-data/htdb-tvdata/hdhomerun_http.py '"+starttime+"' "+tuner_address+" "+cur_tuner+" mobile "+channelid+" "+runtime+" '"+folder_path+foldertitle+season+filename+"' "+rec_id+""

				#print recording_cmd

				tuner_label = "tuner_one"
				if cur_tuner == "tuner1":
					tuner_label = "tuner_two"
				
				query = "update tv_recording set recordstatus = 'started' where id = "+rec_id+";"
				print query
				result2 = htdb_db.query(query)
				foldertitle = string.replace(title, " ", "_")

				runtime_parts = string.split(runtime, ":")
				rh = int(runtime_parts[0])
				rm = int(runtime_parts[1])
				runtime = (rh*60)+rm
				runtime = str(int(runtime))

				print "episode_num", episode_num
				if string.find(episode_num, "S") >= 0:
					season_parts = string.split(episode_num, "E")
					season_num = season_parts[0]
					episode = season_parts[1]
					season_num = string.replace(season_num, "S", "")
				else:
					season_num = "0"
					episode = episode_num

#				tconst = find_tconst(title, runtime)
				
				if len(season_num) == 0:
					season_num = "0"
				
				try:
					episode_test = int(episode)
				except:
					episode = "0"
				
				tvfolder = foldertitle+"/"+season+"/"
				tvfolder = string.replace(tvfolder, "//", "/")
				
				values = "'"+filename+".mp4', '"+tconst+"', 'no', '"+folder_id+"', '"+tvfolder+"', "+runtime+", "+season_num+", "+episode+", '"+primarytitle+"'"
				query = "insert into tv_files (filename, tconst, data_collected, folder_id, folder, runtime, season, episode, primarytitle) values ("+values+");"
				
				if video_type == "movie":
					values = "'"+filename+".mp4', '"+tconst+"', 'no', '"+runtime+"', '"+folder_id+"', '"+primarytitle+"'"
					query = "insert into movie_files(filename, tconst, data_collected, runtime, folder_id, primarytitle) values ("+values+");"
				
				print query
				result3 = htdb_db.query(query)
				
				if video_type == "tvshow":
					if (tconst !=  "nada"):
						query = "select showtconst from tv_episodes where showtconst = '"+tconst+"' and episodenum = '"+episode_num+"';"
						print query
						result3 = htdb_db.query(query)
						result3_list = list(result3.dictresult())
						if len(result3_list) == 0:
							description = ""
							query3 = "select title, episodetitle, description from tv_info where episodenumdd = '"+episodenumdd+"' limit 1;"
							print query3
							result3 = htdb_db.query(query3)
							result3_list = list(result3.dictresult())
							for record3 in result3_list:
								epititle = str(record3['episodetitle'])
								description = str(record3['description'])
							epitconst = tconst+episode_num
							query = "insert into tv_episodes (showtconst, epitconst, title, description, season, episode, episodenum) values ('"+tconst+"', '"+epitconst+"', '"+epititle+"', '"+description+"', "+season_num+", "+episode+",'"+episode_num+"');"
							print query
							result3 = htdb_db.query(query)

				recording_cmd = recording_cmd+" >> /home/tim/Documents/IMDB-data/htdb-tvdata/recordings.log &"
				print recording_cmd
				os.system(recording_cmd)

htdb_db.close()
