#!/usr/bin/python

import _pg
import sys
import os
import os.path
import commands
import string

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")


tconst = "nada"
ptitle = "nada"

parms = "read"
try:
	parms = sys.argv[1]
except:
	parms = "read"

def strip_chars(inputstr):
	ret_val = ''.join(ch for ch in inputstr if ch.isdigit())
	
	return ret_val
	
if parms == "read":
	query = "delete from dvd_data;"
	print query
	result = htdb_db.query(query)

	device_command = "/bin/df -h | /bin/grep '/dev/sr'"
	print device_command
	device_dump = commands.getoutput(device_command)
	print device_dump
	lines = string.split(device_dump, "\n")
	for line in lines:
		device_parts = string.split(line)

		if len(device_parts) < 2:
			htdb_db.close()
			print "no DVD found"
			sys.exit(0)

		device_name = device_parts[0]
		device_path = device_parts[-1]

		print device_name, device_path

		titles_command = "/usr/bin/lsdvd "+device_name
		print titles_command
		titles_dump = commands.getoutput(titles_command)
		print titles_dump

		disc_title = "nada"
		title_num = "00"
		title_time = "00:00:00"
		title_chapters = "00"
		title_val = 0
		tlines = string.split(titles_dump, "\n")
		for tline in tlines:
			title_num = "00"
			if string.find(tline, "Disc Title: ") == 0:
				parts = string.split(tline)
				disc_title = parts[-1]
			if string.find(tline, "Title: ") == 0:
				parts = string.split(tline, ",")
				title_num = parts[0]
				title_num = string.replace(title_num, "Title: ", "")

				tparts = string.split(parts[1])
				title_time = tparts[1]
				title_chapters = tparts[3]
				
				time_parts = string.split(title_time, ".")
				time_str = time_parts[0]
				time_parts = string.split(time_str, ":")
				time_hr = int(time_parts[0])
				time_min = int(time_parts[1])
				time_sec = int(time_parts[2])
				
				title_val = (time_hr*60)+(time_min)

			if title_num != "00":
				if title_val > 5:
		#			print disc_title, title_num, title_time, title_chapters
					query = "insert into dvd_data (dvd_device, dvd_title, titlenum, titlelen, titlechaps) values ('"+device_name+"', '"+disc_title+"', '"+title_num+"', '"+title_time+"', '"+title_chapters+"');"
					print query
					result = htdb_db.query(query)


if parms == "rip":

	device_name = ""
	device_path = ""
	
	tv_series_dvd = "no"

	offender = ""
	soffender = ""
	disc_title = ""
	
	query = "select distinct dvd_title from dvd_data;"
	print query
	result = htdb_db.query(query)
	result_list = list(result.dictresult())
	for record in result_list:
		disc_title = str(record['dvd_title'])

		print "dvd name = ", disc_title

		if len(disc_title) > 0:
			if string.find(disc_title, "SEASON") > 0:
				tv_series_dvd = "yes"
			if string.find(disc_title, "D1") > 0:
				tv_series_dvd = "yes"
				offender = "_D"
			if string.find(disc_title, "D2") > 0:
				tv_series_dvd = "yes"
				offender = "_D"
			if string.find(disc_title, "D3") > 0:
				tv_series_dvd = "yes"
				offender = "_D"
			if string.find(disc_title, "D4") > 0:
				tv_series_dvd = "yes"
				offender = "_D"
			if string.find(disc_title, "D5") > 0:
				tv_series_dvd = "yes"
				offender = "_D"
			if string.find(disc_title, "D6") > 0:
				tv_series_dvd = "yes"
				offender = "_D"
			if string.find(disc_title, "DISC") > 0:
				tv_series_dvd = "yes"
				offender = "DISC"

			if string.find(disc_title, "SEASON_1") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "1"
			if string.find(disc_title, "SEASON_2") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "2"
			if string.find(disc_title, "SEASON_3") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "3"
			if string.find(disc_title, "SEASON_4") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "4"
			if string.find(disc_title, "SEASON_5") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "5"
			if string.find(disc_title, "SEASON_6") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "6"
			if string.find(disc_title, "SEASON_7") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "7"
			if string.find(disc_title, "SEASON_8") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "8"
			if string.find(disc_title, "SEASON_9") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "9"
			if string.find(disc_title, "SEASON_10") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "10"
			if string.find(disc_title, "SEASON_11") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "11"
			if string.find(disc_title, "SEASON_12") > 0:
				tv_series_dvd = "yes"
				soffender = "_SEASON_"
				season = "12"

			if string.find(disc_title, "S1") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "1"
			if string.find(disc_title, "S2") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "2"
			if string.find(disc_title, "S3") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "3"
			if string.find(disc_title, "S4") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "4"
			if string.find(disc_title, "S5") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "5"
			if string.find(disc_title, "S6") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "6"
			if string.find(disc_title, "S7") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "7"
			if string.find(disc_title, "S8") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "8"
			if string.find(disc_title, "S9") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "9"
			if string.find(disc_title, "S10") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "10"
			if string.find(disc_title, "S11") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "11"
			if string.find(disc_title, "S12") > 0:
				tv_series_dvd = "yes"
				soffender = "_S"
				season = "12"

			print "offender", offender

	#		sys.exit(0)

			search_title = disc_title

			query = "select distinct titlenum, titlelen from dvd_data where dvd_title = '"+disc_title+"' and title_rip != 'delete' order by titlenum;"
			print query
			result = htdb_db.query(query)
			result_list = list(result.dictresult())
			print len(result_list)
			if len(result_list) > 1:
				tv_series_dvd = "yes"

			print "tv series dvd = ", tv_series_dvd
#			sys.exit(0)

			if tv_series_dvd == "yes":
				ttype = "tvseries"
				orig_title = disc_title
				title = string.replace(orig_title, "SEASON", "")
				title = string.replace(title, "DISC", "")
				title = string.replace(title, offender, "")
				title = string.replace(title, soffender, "")
				title = string.replace(title, "0", "")
				title = string.replace(title, "1", "")
				title = string.replace(title, "2", "")
				title = string.replace(title, "3", "")
				title = string.replace(title, "4", "")
				title = string.replace(title, "5", "")
				title = string.replace(title, "6", "")
				title = string.replace(title, "7", "")
				title = string.replace(title, "8", "")
				title = string.replace(title, "9", "")
				title = string.replace(title, "10", "")
				title = string.replace(title, "11", "")
				title = string.replace(title, "12", "")
				title = string.replace(title, "_", "")
				title = string.replace(title, "-", "")

				search_title = string.replace(search_title, "0", "")
				search_title = string.replace(search_title, "1", "")
				search_title = string.replace(search_title, "2", "")
				search_title = string.replace(search_title, "3", "")
				search_title = string.replace(search_title, "4", "")
				search_title = string.replace(search_title, "5", "")
				search_title = string.replace(search_title, "6", "")
				search_title = string.replace(search_title, "7", "")
				search_title = string.replace(search_title, "8", "")
				search_title = string.replace(search_title, "9", "")
				search_title = string.replace(search_title, "10", "")
				search_title = string.replace(search_title, "11", "")
				search_title = string.replace(search_title, "12", "")
				search_title = string.replace(search_title, "_", " ")
				search_title = string.replace(search_title, " DISC", "")
				search_title = string.replace(search_title, " S D", "")
				print "modified title = "+search_title
				print "title = "+title 

				subtitle = string.replace(orig_title, title, "")
				subtitle = string.replace(subtitle, offender, ":")
				subtitle = string.replace(subtitle, "_", "")
				subtitle = string.replace(subtitle, "-", "")
				subtitle = string.replace(subtitle, "DISC", ":DISC")

				season = "0"
				disc = "0"
				parts = string.split(subtitle, ":")
				try:
					season = parts[0]
				except:
					season = "0"
				try:
					disc = parts[1]
				except:
					disc = "0"

				season = string.lower(season)
				season_num = season
				season = string.replace(season, "season", "season=")

				disc = string.replace(disc, "DISC", "")
				season_num = strip_chars(season)
				print title, season, season_num, disc

				if len(season_num) == 0:
					season_num = "0"

				if len(disc) == 0:
					disc = "0"

				if int(season_num) > 30:
					season_num = "0"

				try:
					disc_test = int(disc)
				except:
					disc = "0"

				normaltitle = string.lower(search_title)

				print title, normaltitle

			#	sys.exit(0)

				query = "select tconst, primarytitle from title_basics where normaltitle = '"+normaltitle+"' and ttype = '"+ttype+"';"
				print query
				result = htdb_db.query(query)
				result_list = list(result.dictresult())

				if len(result_list) == 0:
					query = "select tconst, primarytitle from title_basics where normaltitle ~* '"+normaltitle+"' and ttype = '"+ttype+"';"
					print query
					result = htdb_db.query(query)
					result_list = list(result.dictresult())

				if len(result_list) == 0:
					query = "select tconst, primarytitle from tv_files where primarytitle ~* '"+search_title+"';"
					print query
					result = htdb_db.query(query)
					result_list = list(result.dictresult())

				if len(result_list) == 0:
					query = "select tconst, primarytitle from dvd_rips where filename ~* '"+search_title+"';"
					print query
					result = htdb_db.query(query)
					result_list = list(result.dictresult())

				for record in result_list:
					tconst = str(record['tconst'])
					ptitle = str(record['primarytitle'])


				print tconst, ptitle

	#			sys.exit(0)


				print device_path

				query = "select distinct dvd_device, dvd_title, titlenum, titlelen from dvd_data where dvd_title = '"+disc_title+"' and title_rip != 'delete' order by dvd_title, titlenum;"
				print query
				result = htdb_db.query(query)
				result_list = list(result.dictresult())
				
				item_counter = 1
				prev_item = ""
				
				for record in result_list:
					device_path = str(record['dvd_device'])
					dest_name = str(record['dvd_title'])
					titlenum = str(record['titlenum'])
					runtime = str(record['titlelen'])
					
					if item_counter == 1:
						prev_item = device_path
					
					if device_path != prev_item:
						eject_command = "/usr/bin/eject "+prev_item
						print eject_command
						os.system(eject_command)
						
						prev_item = device_path
						
					
					dest_title = dest_name+"_title_"+titlenum
					
					tempdir = "/var/www/html/htdb/temp/"
					query = "select folder_path from folders where folder_type = 'temp';"
					print query
					result2 = htdb_db.query(query)
					result2_list = list(result2.dictresult())
					for record2 in result2_list:
						tempdir = str(record2['folder_path'])

					file_exists = "no"
					try:
						fref = open(tempdir+dest_title+".mp4", 'rb')
						fref.close()
						file_exists = "yes"
						print tempdir+dest_title+".mp4 exists"
					except:
						file_exists = "no"
						print tempdir+dest_title+".mp4 does not exist"

					unique = ""
					if file_exists == "yes":
						query = "select nextval('dvd_rip_id') as unique;"
						print query
						result = htdb_db.query(query)
						result_list = list(result.dictresult())
						for record in result_list:
							unique = str(int(record['unique']))
						dest_title = dest_title+"_"+unique

					query = "update dvd_data set title_rip = 'yes' where dvd_device = '"+device_path+"' and titlenum = "+titlenum+";"
					result = htdb_db.query(query)

					handbrake_command = "/usr/bin/HandBrakeCLI --subtitle-lang-list English --all-subtitles --no-dvdnav -i '"+device_path+"' -t "+titlenum+" -f av_mp4 -O -e x264 --vfr -E ca_aac --gain 5.0 -o '"+tempdir+dest_title+".mp4'"
					print handbrake_command

#					sys.exit(0)

					result = os.system(handbrake_command)
					print "handbrake result:", result

					query = "insert into dvd_rips (tconst, ttype, filename, primarytitle, season, disc, episode, runtime) values ('"+tconst+"', '"+ttype+"', '"+dest_title+".mp4', '"+ptitle+"', '"+season_num+"', '"+disc+"', '"+titlenum+"', '"+runtime+"');"
					print query
					result2 = htdb_db.query(query)
					
					item_counter = item_counter+1
					
				#eject dvd device
				eject_command = "/usr/bin/eject "+device_path
				print eject_command
				os.system(eject_command)

				prev_item = device_path
			else:
				ttype = "movie"
				print "ttype == movie, ripping a movie"
				orig_title = disc_title
				title = string.replace(orig_title, "_", " ")
				title = string.replace(title, " 16X9", "")
				title = string.replace(title, " LETTERBOX", "")

				normaltitle = string.lower(title)
				tconst = "nada"
				ptitle = title

				if string.find(normaltitle, "the ") == 0:
					normaltitle = normaltitle[4:]

				query = "select tconst, primarytitle from title_basics where normaltitle = '"+normaltitle+"' and ttype = '"+ttype+"';"
				print query
				result = htdb_db.query(query)
				result_list = list(result.dictresult())

				if len(result_list) == 0:
					if string.find(normaltitle, " and ") > 0:
						print "swapping and for &"
						normaltitle = string.replace(normaltitle, " and ", " & ")
					query = "select tconst, primarytitle from title_basics where normaltitle ~* '"+normaltitle+"' and ttype = '"+ttype+"';"
					print query
					result = htdb_db.query(query)
					result_list = list(result.dictresult())

					if len(result_list) == 0:
						if string.find(normaltitle, " & ") > 0:
							print "swapping & for and"
							normaltitle = string.replace(normaltitle, " & ", " and ")
						query = "select tconst, primarytitle from title_basics where normaltitle ~* '"+normaltitle+"' and ttype = '"+ttype+"';"
						print query
						result = htdb_db.query(query)
						result_list = list(result.dictresult())

				for record in result_list:
					tconst = str(record['tconst'])
					ptitle = str(record['primarytitle'])

				ptitle = string.replace(ptitle, ":", "")
				normaltitle = string.lower(ptitle)

				print tconst, ptitle, normaltitle

				print device_path

	#			sys.exit(0)

				query = "select distinct dvd_device, dvd_title, titlenum, titlelen from dvd_data where dvd_title = '"+disc_title+"' and (title_rip = 'yes' or title_rip = 'nada');"
				print query
				result = htdb_db.query(query)
				result_list = list(result.dictresult())

				for record in result_list:
					device_path = str(record['dvd_device'])
					dest_name = str(record['dvd_title'])
					titlenum = str(record['titlenum'])
					runtime = str(record['titlelen'])

					dest_title = ptitle+".mp4"
					dest_title = string.replace(dest_title, "'", "");
					dest_title = string.replace(dest_title, ":", "");

					tempdir = "/var/www/html/htdb/temp/"
					query = "select folder_path from folders where folder_type = 'temp';"
					print query
					result2 = htdb_db.query(query)
					result2_list = list(result2.dictresult())
					for record2 in result2_list:
						tempdir = str(record2['folder_path'])

					file_exists = "no"
					try:
						fref = open(tempdir+dest_title, 'rb')
						fref.close()
						file_exists = "yes"
					except:
						file_exists = "no"

					unique = ""
					if file_exists == "yes":
						query = "select nextval('dvd_rip_id') as unique;"
						print query
						result = htdb_db.query(query)
						result_list = list(result.dictresult())
						for record in result_list:
							unique = str(int(record['unique']))
						dest_title = ptitle+"_"+unique+".mp4"

					query = "update dvd_data set title_rip = 'yes' where dvd_device = '"+device_path+"' and titlenum = "+titlenum+";"
					result = htdb_db.query(query)

					if string.find(dest_title, "'") > 0:
						dest_title = string.replace(dest_title, "'", "")

					if string.find(ptitle, "'") > 0:
						ptitle = string.replace(ptitle, "'", "''")
						print dest_title, ptitle

					query = "insert into dvd_rips (tconst, ttype, filename, primarytitle, season, disc, episode, runtime) values ('"+tconst+"', '"+ttype+"', '"+dest_title+"', '"+ptitle+"', '0', '0', '0', '"+runtime+"');"
					print query
					result2 = htdb_db.query(query)

					handbrake_command = "/usr/bin/HandBrakeCLI --subtitle-lang-list English --all-subtitles --no-dvdnav -i '"+device_path+"' -t "+titlenum+" -f av_mp4 -O -e x264 --vfr -E ca_aac --gain 5.0 -o '"+tempdir+dest_title+"'"
					if string.find(dest_title, "'") > 0:
						handbrake_command = "/usr/bin/HandBrakeCLI --subtitle-lang-list English --all-subtitles --no-dvdnav -i '"+device_path+"' -t "+titlenum+" -f av_mp4 -O -e x264 --vfr -E ca_aac --gain 5.0 -o \""+tempdir+dest_title+"\""

					print handbrake_command

					os.system(handbrake_command)

					orig_title = dest_title

					query = "select folder_path, id from folders where folder_type = 'Movies';"
					print query
					result2 = htdb_db.query(query)
					result2_list = list(result2.dictresult())

					dest_id = "1"
					for record2 in result2_list:
						dest_path = str(record2['folder_path'])
						dest_id = str(int(record2['id']))


					dest_title = string.replace(dest_title, ":", "")
					dest_title = string.replace(dest_title, "?", "")
					dest_title = string.replace(dest_title, "/", "")

					move_command = "/bin/mv -- \""+tempdir+orig_title+"\" \""+dest_path+dest_title+"\""
					print move_command
					os.system(move_command)

					movie_info = os.stat(dest_path+dest_title)
					fsize = str(int(movie_info.st_size))
					filename = dest_title

					runtime_parts = string.split(runtime, ":")
					runtime_hours = runtime_parts[0]
					runtime_mins = runtime_parts[1]
					runtime = str((int(runtime_hours)*60)+int(runtime_mins))

					audio = "nada"
					video = "nada"

					info_command = "/usr/bin/ffmpeg -i \""+dest_path+dest_title+"\""
					print info_command
					info_dump = commands.getoutput(info_command)

					info_parts = string.split(info_dump, "\n")
					for info_line in info_parts:
						if string.find(info_line, "Stream") > 0:
								if string.find(info_line, "Video") > 0:
									video = info_line
								if string.find(info_line, "Audio") > 0:
									audio = info_line

					values = "'"+tconst+"', '"+ptitle+"', "+dest_id+", '"+filename+"', "+fsize+", '"+audio+"', '"+video+"', '"+runtime+"', 'no'"
					query3 = "insert into movie_files (tconst, primarytitle, folder_id, filename, filesize, audio, video, runtime, data_collected) values ("+values+");"
					print query3
					result3 = htdb_db.query(query3)

					proc_command = "/home/tim/Documents/IMDB-data/movies_grab.py"
					print proc_command
					os.system(proc_command)


					query = "update dvd_rips set status = 'loaded' where tconst = '"+tconst+"' and filename = '"+orig_title+"' and primarytitle = '"+ptitle+"';"
					print query
					result2 = htdb_db.query(query)
				
				#eject dvd device
	
				eject_command = "/usr/bin/eject "+device_path
				print eject_command
				os.system(eject_command)
		
	query = "delete from dvd_data;"
	print query
	result = htdb_db.query(query)

htdb_db.close()