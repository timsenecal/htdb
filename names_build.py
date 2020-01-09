#!/usr/bin/python

import _pg
import os
import commands
import string
import sys
import time

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

def run_file(filename):

	fref = open(filename)
	buffer = fref.read()
	fref.close()
	
	profession = "unknown"
	name_row = 0
	
#	buf_parts = string.split(buffer, "castlist_label")
	buf_parts = string.split(buffer, "fullcredits_content")
	
	print len(buf_parts)
	
#	sys.exit(0)
	if len(buf_parts) <  2:
		return
	name_parts = buf_parts[1]
	
#	name_list = string.split(name_parts, "<a href=\"/name/")
	name_lines = string.split(name_parts, "\n")
#	print len(name_list)
#	for line in name_list:
#		name_line_parts = string.split(name_line, "\n")
	print len(name_lines)
	for line in name_lines:
		if string.find(line, "Directed by") > 0:
			profession = "director"
		if string.find(line, "Writing Credits") > 0:
			profession = "writer"
		if string.find(line, "cast_list") > 0:
			profession = "actor"
		if string.find(line, "Produced by") > 0:
			profession = "producer"
		if string.find(line, "Music") > 0:
			profession = "musician"
		if string.find(line, "Cinematography by") > 0:
			profession = "director of photography"
		if string.find(line, "Film Editing by") > 0:
			profession = "editor"
		if string.find(line, "Art Direction") > 0:
			profession = "art director"
		if string.find(line, "Set Decoration") > 0:
			profession = "set decorator"
		if string.find(line, "Costume") > 0:
			profession = "costumer"
		if string.find(line, "Makeup") > 0:
			profession = "makeup"
		if string.find(line, "Production") > 0:
			profession = "production"
		if string.find(line, "Art Department") > 0:
			profession = "artist"
		if string.find(line, "Stunt") > 0:
			profession = "stunts"
		if string.find(line, "Sound") > 0:
			profession = "sound"
		if string.find(line, "Other") > 0:
			profession = "other"
		name_row = name_row+1
		if string.find(line, "<a href=\"/name/") >= 0:
			nconst = line
			offset = string.find(nconst, "nm")
			nconst = nconst[offset:]
			nconst = string.replace(nconst, "/\"", "")
			#remember, name_row is the row following the current 'line' from array
			name_line = name_lines[name_row]
			if string.find(name_line, "<img height") < 0:
#					print "name line", str(int(title_row))+"::"+title_line
				name = name_line[2:]
				name = string.strip(name)
				if len(name) > 0:
					normalname = string.lower(name)
				name = string.replace(name, "'", "''")
				normalname = string.replace(normalname, "'", "")
				print "found name: '"+nconst+"', '"+name+"', '"+normalname+"', '"+profession+"'"

				character = ""
				query_name = "insert into name_basics(nconst, primaryname, normalname, profession, tconst) values ('"+nconst+"', '"+name+"', '"+normalname+"', '"+profession+"', '"+tconst+"');"
				print query_name
				try:
					htdb_db.query(query_name)
				except:
					pass
				
				query_movie = "insert into movie_credits(tconst, nconst, profession, role) values ('"+tconst+"', '"+nconst+"', '"+profession+"', '"+character+"');"
				print query_movie
				try:
					htdb_db.query(query_movie)
				except:
					pass
		if string.find(line, "<td class=\"character\">") > 0:
			#remember, name_row is the row following the current 'line' from array
			character = name_lines[name_row]
#			print "character", character
			offset = string.find(character, ">")
			if offset > 0:
				character = character[offset+1:]
#				print character
			character = string.replace(character, "</a>", "")
			character = string.strip(character)
			character = string.replace(character, "'", "''")
			print "found character: '"+nconst+"', '"+name+"', '"+normalname+"', '"+profession+"', '"+character+"'"
			
			query_movie = "update movie_credits set role = '"+character+"' where tconst = '"+tconst+"' and nconst = '"+nconst+"' and profession = '"+profession+"';"
			print query_movie
			try:
				htdb_db.query(query_movie)
			except:
				pass

	return


##########################################################

clean = "no"
try:
	clean = sys.argv[1]
except:
	clean = "no"
	
if clean == "yes":
	query = "delete from name_basics;"
	print query
	result = htdb_db.query(query)
	
	query = "delete from movie_credits;"
	print query
	result = htdb_db.query(query)

query = "select distinct tconst, primarytitle from movie_files where data_collected = 'no';"
	
if string.find(clean, "2019-") > 0:
	query = "select distinct tconst, primarytitle from movie_files where stamp > '"+clean+"';"
if string.find(clean, "2020-") > 0:
	query = "select distinct tconst, primarytitle from movie_files where stamp > '"+clean+"';"
if string.find(clean, "2021-") > 0:
	query = "select distinct tconst, primarytitle from movie_files where stamp > '"+clean+"';"
if string.find(clean, "2022-") > 0:
	query = "select distinct tconst, primarytitle from movie_files where stamp > '"+clean+"';"
	
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

for record in result_list:
	tconst = str(record['tconst'])
	primarytitle = str(record['primarytitle'])
	print tconst, primarytitle
	filename = "/var/www/html/htdb/htdb-temp/"+tconst+"-cast.html"
	print filename
	fref = 0
	try:
		fref = open(filename, 'rb')
		buffer = fref.read()
		fref.close()
		fref = 1
		if len(buffer) < 1000:
			fref = 0
	except:
		fref = 0
	
	if fref == 0:
		curl_cmd = "/usr/bin/curl \"https://www.imdb.com/title/"+tconst+"/fullcredits\" > "+filename
		print curl_cmd
		os.system(curl_cmd)

		time.sleep(5)
		
	run_file(filename)

sys.exit(0)

query = "select distinct tconst from tv_files;"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

for record in result_list:
	tconst = str(record['tconst'])
	filename = "/home/tim/Documents/IMDB-data/htdb-temp/"+tconst+"-cast.html"
	curl_cmd = "/usr/bin/curl \"https://www.imdb.com/title/"+tconst+"/fullcredits\" > "+filename
	print curl_cmd
	os.system(curl_cmd)
	
	run_file(filename)

htdb_db.close()
