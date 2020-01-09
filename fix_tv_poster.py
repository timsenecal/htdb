#!/usr/bin/python

#update both the format of the file to h.264 mp4, and create a poster image if needed.

import _pg
import sys
import os
import os.path
import commands
import string

tconst = "0"

try:
	tconst = sys.argv[1]
except:
	tconst = "0"

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

counter = 0
query = "select tconst, season, episode, folder_id, folder, filename, primarytitle, id from tv_files order by filename;"

if tconst != "0":
	query = "select tconst, season, episode, folder_id, folder, filename, primarytitle, id from tv_files where tconst = '"+tconst+"' order by season, episode;"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

print "number of records found:", len(result_list)

for record in result_list:
	tconst = str(record['tconst'])
	season = str(int(record['season']))
	episode = str(int(record['episode']))
	folderID = str(int(record['folder_id']))
	folder = str(record['folder'])
	filename = str(record['filename'])
	
	print tconst, season, episode, filename
	print "#############################################################################"
	
	if tconst != "nada":
		base_folder = "/media/tim/Octo/TV Shows/"
		query3 = "select folder_path from folders where id = "+folderID+";"
		print query3
		result3 = htdb_db.query(query3)
		result3_list = list(result3.dictresult())
		for record3 in result3_list:
			base_folder = str(record3['folder_path'])
		
		update = "yes"
		generate_poster = "yes"
		epiID = ""
		
		query2 = "select showtconst, epitconst, season, episode, poster, title, id from tv_episodes where showtconst = '"+tconst+"' and season = "+season+" and episode = "+episode+";"
		print query2
		result2 = htdb_db.query(query2)
		result2_list = list(result2.dictresult())
		if len(result2_list) == 0:
			update = "insert"
		
		for record2 in result2_list:
			poster = str(record2['poster'])
			epiID = str(int(record2['id']))
			
			print tconst, season, episode, filename, poster, epiID
			print "#############################################################################"
			
			print "poster = '"+poster+"'"
			if poster == "None":
				poster = ""
			
			if len(poster) > 0:
				print poster
				generate_poster = "no"
				update = "no"
				
				check_cmd = "/usr/bin/file \"/var/www/html/htdb/htdb-posters/"+poster+"\""
				print check_cmd
				check_dump = commands.getoutput(check_cmd)
				print check_dump
				print "#############################################################################"
				if string.find(check_dump, "(No such file or directory)") > 0:
					generate_poster = "yes"
					update = "update"
				
				if string.find(check_dump, "No such file or directory") > 0:
					generate_poster = "yes"
					update = "update"
			else:
				print "poster is empty"
				generate_poster = "yes"
				update = "yes"
		
			if generate_poster == "yes":
				print "generating poster", update
				poster_name = "/var/www/html/htdb/htdb-posters/"+tconst+"s"+season+"e"+episode+".jpg"
				poster_file = tconst+"s"+season+"e"+episode+".jpg"
				print poster_name

				poster_cmd = "/usr/bin/ffmpeg -y -i \""+base_folder+folder+filename+"\" -ss 00:00:25.000 -vframes 1 \""+poster_name+"\""
				print poster_cmd
				os.system(poster_cmd)

				counter = counter+1
			
		
		poster_name = "/var/www/html/htdb/htdb-posters/"+tconst+"s"+season+"e"+episode+".jpg"
		poster_file = tconst+"s"+season+"e"+episode+".jpg"
		info_command = "/usr/bin/ffprobe -i \""+poster_name+"\""
		print info_command
		info_dump = commands.getoutput(info_command)
		
		print "update poster = ",update
		
		scale = "25"
		info_parts = string.split(info_dump, "\n")
		for info_line in info_parts:
			if string.find(info_line, "Stream") > 0:
					if string.find(info_line, "Video") > 0:
						video = info_line
						thing_parts = string.split(video, ",")
						print thing_parts
						geometry = thing_parts[3]
						if string.find(thing_parts[2], "x") > 0:
							geometry = thing_parts[2]
						geoparts = string.split(geometry)
						print geoparts
						geometry = geoparts[0]
						geoparts = string.split(geometry, "x")
						height = int(geoparts[1])
						print "poster height = ", height
						
						if (string.find(video, " DAR 16:9], ") > 0):
							height = 120
							convert_cmd = "/usr/bin/convert \""+poster_name+"\" -resize 213x120! \""+poster_name+"\""
							print convert_cmd
							os.system(convert_cmd)
							if update != "insert":
								update = "yes"
						
						if height > 150:
							scale = 12000/height
							scalestr = str(int(scale))
							convert_cmd = "/usr/bin/convert \""+poster_name+"\" -resize "+scalestr+"% \""+poster_name+"\""
							print convert_cmd
							os.system(convert_cmd)
							if update != "insert":
								update = "yes"

		if update == "insert":
			epitconst = tconst+"s"+season+"e"+episode
			title = "s"+season+"e"+episode
			summary = ""
			poster = poster_file
			episodenum = title
			query = "insert into tv_episodes (showtconst, epitconst, title, description, poster, season, episode, episodenum) values ('"+tconst+"', '"+epitconst+"', '"+title+"', '"+summary+"', '"+poster+"', "+season+", "+episode+",'"+episodenum+"');"
			print query
			htdb_db.query(query)
		
		query = "update tv_episodes set poster = '"+poster_file+"' where id = "+epiID+";"
		print query
		htdb_db.query(query)

#		if counter > 50:
#			sys.exit(0)				
		
htdb_db.close()