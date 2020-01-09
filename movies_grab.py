#!/usr/bin/python

import _pg
import os
import time
import string
import commands

curl_command = "/usr/bin/curl \"https://www.imdb.com/title/<tconst>/?\" > /home/tim/Documents/IMDB-data/htdb-temp/<tconst>.html"
metagrep_command = "/bin/grep '<meta name=\"description\" content=' /home/tim/Documents/IMDB-data/htdb-temp/<tconst>.html"
imggrep_command = "/bin/grep \"<link rel='image_src' href=\" /home/tim/Documents/IMDB-data/htdb-temp/<tconst>.html"
imggrab_command = "/usr/bin/curl <img_url> > /home/tim/Documents/IMDB-data/htdb-posters/<tconst>.jpg"
imgfix_command = "/usr/bin/file /home/tim/Documents/IMDB-data/htdb-posters/<tconst>.jpg"

total_updated = 0

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

query = "select filename, tconst from movie_files where data_collected = 'no' and tconst != 'nada' order by filename;"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

max_found = len(result_list)

for record in result_list:
	
	tconst = str(record['tconst'])
	filename = str(record['filename'])
	print tconst, filename
	description = ""
	
	movie_curl_cmd = string.replace(curl_command, "<tconst>", tconst)
	print movie_curl_cmd
	(status, output) = commands.getstatusoutput(movie_curl_cmd)
	
	if (status == 0):
		movie_meta_cmd = string.replace(metagrep_command, "<tconst>", tconst)
		print movie_meta_cmd
		(status, output) = commands.getstatusoutput(movie_meta_cmd)
		if (status == 0):
			description = output
			description = string.replace(description, '        <meta name="description" content="', "")
			description = string.replace(description, '" />', "")
#			print description[0:30]
			
			description = string.replace(description, "'", "''")
			query = "insert into movie_info (tconst, description) values ('"+tconst+"', '"+description+"');"
			print query
			result = htdb_db.query(query)
		
		movie_image_cmd = string.replace(imggrep_command, "<tconst>", tconst)
		print movie_image_cmd
		(status, output) = commands.getstatusoutput(movie_image_cmd)
		if (status == 0):
			image_url = output
			image_url = string.replace(image_url, '        <link rel=\'image_src\' href="', "")
			image_url = string.replace(image_url, '" />', "")
			image_url_parts = string.split(image_url, ",")
			image_url = image_url_parts[0]
			image_url = image_url+".jpg"
			print image_url
			
			image_get_cmd = string.replace(imggrab_command, "<img_url>", image_url)
			image_get_cmd = string.replace(image_get_cmd, "<tconst>", tconst)
			print image_get_cmd
			(status, output) = commands.getstatusoutput(image_get_cmd)
			if (status == 0):
				
				image_fix_cmd = string.replace(imgfix_command, "<tconst>", tconst)
				(status, info_dump) = commands.getstatusoutput(image_fix_cmd)
				if (status == 0):
					total_updated = total_updated + 1
					
					print info_dump
					
					info_parts = string.split(info_dump)
					dims_part = info_parts[-3]
					print tconst, dims_part
					
					dims = string.replace(dims_part, "x", " ")
					dims = string.replace(dims, ",", "")
					dims_parts = string.split(dims)
					width = dims_parts[0]
					height = dims_parts[1]
					
					dims = width+" "+height
					print dims
					divisor = float(height)/float(160.0)
					print divisor
					width = int(width)
					width = width/divisor
					height = round(float(height)/divisor)
					movie_dims = "width=\""+str(int(width))+"\" height=\""+str(int(height))+"\""
					
					query = "update movie_info set dims = '"+dims+"', movie_dims = '"+movie_dims+"' where tconst = '"+tconst+"';"
					print query
					htdb_db.query(query)
					
					query = "update movie_files set data_collected = 'yes' where tconst = '"+tconst+"';"
					print query
					result = htdb_db.query(query)
			else:
				image_cp_cmd = "/bin/cp -prf /home/tim/Documents/IMDB-data/htdb-posters/unknown.jpg /home/tim/Documents/IMDB-data/htdb-posters/"+tconst+".jpg"
				print image_cp_cmd
				(status, info_dump) = commands.getstatusoutput(image_cp_cmd)
				
				image_fix_cmd = string.replace(imgfix_command, "<tconst>", tconst)
				(status, info_dump) = commands.getstatusoutput(image_fix_cmd)
				if (status == 0):
					total_updated = total_updated + 1
					
					print info_dump
					
					info_parts = string.split(info_dump)
					dims_part = info_parts[-3]
					print tconst, dims_part
					
					dims = string.replace(dims_part, "x", " ")
					dims = string.replace(dims, ",", "")
					dims_parts = string.split(dims)
					width = dims_parts[0]
					height = dims_parts[1]
					
					dims = width+" "+height
					print dims
					divisor = float(height)/float(160.0)
					print divisor
					width = int(width)
					width = width/divisor
					height = round(float(height)/divisor)
					movie_dims = "width=\""+str(int(width))+"\" height=\""+str(int(height))+"\""
					
					query = "update movie_info set dims = '"+dims+"', movie_dims = '"+movie_dims+"' where tconst = '"+tconst+"';"
					print query
					htdb_db.query(query)
					
					query = "update movie_files set data_collected = 'yes' where tconst = '"+tconst+"';"
					print query
					result = htdb_db.query(query)
	
	if total_updated < max_found:
		time.sleep(15)

htdb_db.close()

print "total number of movies found:", total_updated