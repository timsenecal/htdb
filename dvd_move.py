#!/usr/bin/python

import _pg
import sys
import os
import os.path
import commands
import string

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

source_path = ""
dest_path = ""

query = "select folder_path from folders where folder_type = 'temp';"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())
for record in result_list:
	source_path = str(record['folder_path'])

query = "select ttype, filename, newfilename, primarytitle, season, episode, episodenum, id from dvd_rips where status = 'move' order by id;"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

for record in result_list:
	ttype = str(record['ttype'])
	if ttype == "tvseries":
		query2 = "select folder_path, id from folders where folder_type = 'TV';"
	else:
		query2 = "select folder_path, id from folders where folder_type = 'Movies';"
	print query2
	result2 = htdb_db.query(query2)
	result2_list = list(result2.dictresult())
	for record2 in result2_list:
		dest_path = str(record2['folder_path'])
		dest_id = str(int(record2['id']))
	
	dest_name = str(record['newfilename'])
	source_name = str(record['filename'])
	folder = str(record['primarytitle'])
	season = int(record['season'])
	video_id = str(int(record['id']))
	
	seasonstr = str(int(season))
	if season < 10:
		seasonstr = "0"+str(int(season))
	seasonstr = "Season_"+seasonstr
	
	print "dest_name = ", dest_name
	
	if ttype == "tvseries":
		subfolder = string.replace(folder, " ", "_")
		subfolder = dest_path+subfolder
		mkdir_command = "/bin/mkdir '"+subfolder+"'"
		print mkdir_command
		os.system(mkdir_command)

		mkdir_command = "/bin/chmod 666 '"+subfolder+"'"
		print mkdir_command
		os.system(mkdir_command)

		subfolder = folder+"/"+seasonstr
		subfolder = string.replace(subfolder, " ", "_")
		subfolder = dest_path+subfolder
		mkdir_command = "/bin/mkdir '"+subfolder+"'"
		print mkdir_command
		os.system(mkdir_command)

		mkdir_command = "/bin/chmod 666 '"+subfolder+"'"
		print mkdir_command
		os.system(mkdir_command)
	else:
		subfolder = dest_path
		
		if string.find(dest_name, "/Movies//None") > 0:
			dest_name = string.replace(folder, " ", "_");
			dest_name = string.replace(dest_name, "'", "")
			dest_name = dest_name+".mp4"
	
	dest_name_parts = string.split(dest_name, "/")
	dest_name = dest_name_parts[-1]
	
	dest_name = subfolder+"/"+dest_name
	dest_name = string.replace(dest_name, "//", "/")
	newfilename = dest_name
	
#	move_command = "/bin/cp -p '"+source_path+source_name+"' '"+subfolder+"/"+dest_name+"'"
	move_command = "/bin/cp '"+source_path+source_name+"' '"+dest_name+"'"
	print move_command
	
	mv_result = os.system(move_command)
	print "copy result:", mv_result
	
	if mv_result == 0:
		query = "update dvd_rips set status = 'loaded', newfilename = '"+subfolder+"/"+dest_name+"' where id = '"+video_id+"';"
		print query
		result2 = htdb_db.query(query)
		
		movie_info = os.stat(dest_name)
		fsize = str(int(movie_info.st_size))
		
		# need to collect video, audio, folder, folder_id, filesize, normaltitle
		query2 = "select tconst, newfilename, primarytitle, season, episode, runtime from dvd_rips where id = '"+video_id+"';"
		print query2
		result2 = htdb_db.query(query2)
		result2_list = list(result2.dictresult())
		for record2 in result2_list:
			tconst = str(record2['tconst'])
			newfilename = str(record2['newfilename'])
			primarytitle = str(record2['primarytitle'])
			season = str(record2['season'])
			episode = str(record2['episode'])
			runtime = str(record2['runtime'])
			
			print "***********************************************************"
			print "runtime", runtime
			run_parts = string.split(runtime, ".")
			runtime = run_parts[0]
			run_parts = string.split(runtime, ":")
			pcount = len(run_parts)
			hours = 0
			mins = 0
			secs = 0
			if pcount == 3:
				secs = int(run_parts[2])
				mins = int(run_parts[1])
				hours = int(run_parts[0])
			
			print "hours", hours
			print "mins", mins
			runtime = (hours*60)+mins
			print "runtime", runtime
			runtime = str(int(runtime))
			
			print "***********************************************************"
			print "runtime", runtime
			
			filepath = string.replace(newfilename, dest_path, "")
			file_parts = string.split(filepath, "/")
			filename = file_parts[-1]
			filepath = string.replace(filepath, filename, "")

			audio = "nada"
			video = "nada"
			
			info_command = "/usr/bin/ffmpeg -i \""+newfilename+"\""
			print info_command
			info_dump = commands.getoutput(info_command)

			info_parts = string.split(info_dump, "\n")
			for info_line in info_parts:
				if string.find(info_line, "Stream") > 0:
						if string.find(info_line, "Video") > 0:
							video = info_line
						if string.find(info_line, "Audio") > 0:
							audio = info_line
			
			normaltitle = string.lower(primarytitle)
			
			if ttype == "movie":
				primarytitle = string.replace(primarytitle, "'", "''")
				values = "'"+tconst+"', '"+primarytitle+"', "+dest_id+", '"+filename+"', "+fsize+", '"+audio+"', '"+video+"', '"+runtime+"', 'no'"
				query3 = "insert into movie_files (tconst, primarytitle, folder_id, filename, filesize, audio, video, runtime, data_collected) values ("+values+");"
				print query3
				result3 = htdb_db.query(query3)
			
			if ttype == "tvshow":
				primarytitle = string.replace(primarytitle, "'", "''")
				values = "'"+tconst+"', '"+primarytitle+"', '"+normaltitle+"', "+dest_id+", '"+filepath+"', '"+filename+"', "+fsize+", '"+audio+"', '"+video+"', '"+runtime+"', "+season+", "+episode+", 'no'"
				query3 = "insert into tv_files (tconst, primarytitle, normaltitle, folder_id, folder, filename, filesize, audio, video, runtime, season, episode, data_collected) values ("+values+");"
				print query3
				result3 = htdb_db.query(query3)

				episode_cmd = "/var/www/html/htdb/grab_tv_episodes.py"
				print episode_cmd
				os.system(episode_cmd)

				#run this one last, it updates data_collected flag
				series_cmd = "/var/www/html/htdb/grab_tv_series.py"
				print series_cmd
				os.system(series_cmd)

htdb_db.close()
