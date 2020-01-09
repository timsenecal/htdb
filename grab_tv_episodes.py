#!/usr/bin/python

import _pg
import sys
import os
import os.path
import commands
import string

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

tconst = ""
seaton = 0

curl_command = "/usr/bin/curl \"https://www.imdb.com/title/<tconst>/episodes?season=<season>\" > /home/tim/Documents/IMDB-data/htdb-temp/<tconst>s<season>.html"
file_path = "/home/tim/Documents/IMDB-data/htdb-temp/<tconst>s<season>.html"

img_curl_command = "/usr/bin/curl \"<image-src>\" > /home/tim/Documents/IMDB-data/htdb-posters/<tconst>s<season>e<episode>.jpg"
image_path = "/home/tim/Documents/IMDB-data/htdb-posters/<tconst>s<season>e<episode>.jpg"

query = "select distinct tconst, season, data_collected, primarytitle from tv_files where data_collected = 'no' and tconst != 'nada' order by tconst, season;"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())
for record in result_list:
	tconst = str(record['tconst'])
	season = str(int(record['season']))
	primarytitle = str(record['primarytitle'])
	
	print primarytitle, tconst, season

	titles = []
	summaries = []
	images = []
	epitconst = []
	next_line = "no"
	episode = 1
	
	full_curl_command = string.replace(curl_command, "<tconst>", tconst)
	full_curl_command = string.replace(full_curl_command, "<season>", season)
	
	full_file_path = string.replace(file_path, "<tconst>", tconst)
	full_file_path = string.replace(full_file_path, "<season>", season)
	
	full_img_curl_command = string.replace(img_curl_command, "<tconst>", tconst)
	full_img_curl_command = string.replace(full_img_curl_command, "<season>", season)
	
	full_image_path = string.replace(image_path, "<tconst>", tconst)
	full_image_path = string.replace(full_image_path, "<season>", season)
	
	print full_curl_command
	os.system(full_curl_command)

	fileref = open(full_file_path, 'rb')
	buffer = fileref.read()
	fileref.close()

	buffparts = string.split(buffer, "\n")

	for line in buffparts:
		if string.find(line, "<img width=\"") >= 0:
			if string.find(line, "class=\"zero-z-index\" alt=\"") >= 0:
				image = line
				offset = string.find(image, "src=\"")
				image = image[offset+5:]
				offset2 = string.find(image, "\"", offset+5)
				image = image[0:offset2]
				
				image_command = string.replace(full_img_curl_command,"<image-src>", image)
				image_command = string.replace(image_command, "<episode>", str(int(episode)))
				
				episode_image_path = string.replace(full_image_path, "<episode>", str(int(episode)))
				
				print image_command
				os.system(image_command)
				images.append(episode_image_path)
	
				episode = episode+1
		
		if string.find(line, "title=") >= 0:
			if string.find(line, "itemprop=\"url\"") >= 0:
				episodeID = line
#				print episodeID
				eOffset = string.find(episodeID, " <div data-const=\"")
#				print episodeID, eOffset
				episodeID = episodeID[eOffset+18:]
#				print episodeID
				eOffset = string.find(episodeID, "\"")
#				print episodeID, eOffset
				episodeID = episodeID[0:eOffset]
#				print episodeID
				epitconst.append(episodeID)
				
			if string.find(line, "itemprop=\"name\"") >= 0:
	#			print line
				title = line
				title = string.replace(title, "title=\"", "")
				offset = string.find(title, '"')
				title = title[0:offset]
	#			print title
				titles.append(title)
				
		if next_line == "yes":
	#		print line
			description = line
			description = string.replace(description, "</div>", "")
			description = string.strip(description)
			summaries.append(description)
			next_line = "no"
		if string.find(line, "class=\"item_description\"") >= 0:
			next_line = "yes"


	item = 0
	
	print tconst, "season",season
	for title in titles:
		print "tconst: ", tconst
		print "Title:  ", title
		print "Summary:", summaries[item]
		try:
			print "Image:  ", images[item]
		except:
			print "Image:   nada"
		print "Episode:", epitconst[item]
		print "-----"
		
		try:
			poster = images[item]
			pparts = string.split(poster,"/")
			poster = pparts[-1]
		except:
			poster = ""
		
		epi = str(int(item+1))
		if len(epi) == 1:
			epi = "0"+epi
		seas = str(int(season))
		if len(seas) == 1:
			seas = "0"+seas
		episodenum = "S"+seas+"E"+epi
		
		title = string.replace(title, "'", "''")
		summary = summaries[item]
		summary = string.replace(summary, "'", "''")
		epiconst = epitconst[item]
		
		add_entry = "yes"
		
		if string.find(summary, "Know what this is about") >= 0:
			add_entry = "no"
		if string.find(title, "Episode #") >= 0:
			add_entry = "no"
		
		if add_entry == "yes":
			query = "delete from tv_episodes where showtconst = '"+tconst+"' and epitconst = '"+epiconst+"';"
			htdb_db.query(query)

			query = "insert into tv_episodes (showtconst, epitconst, title, description, poster, season, episode, episodenum) values ('"+tconst+"', '"+epiconst+"', '"+title+"', '"+summary+"', '"+poster+"', "+seas+", "+epi+",'"+episodenum+"');"
			print query
			htdb_db.query(query)
		
		item = item+1

#query = "update tv_files set data_collected = 'yes';"
#print query
#htdb_db.query(query)

	
htdb_db.close()