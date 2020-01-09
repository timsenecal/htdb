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

label = ""
try:
	label = sys.argv[1]
except:
	print "requires playlist label"
	sys.exit(0)
	
client = ""
try:
	client = sys.argv[2]
except:
	print "requires playlist client"
	sys.exit(0)

print label, client

query = "select label, client, ttype, id, season, episode, tconst, mode from playlist where client = '"+client+"' and label = '"+label+"' and status != 'delete' and mode = 'sequence' order by id;"
print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

for record in result_list:
	label = str(record['label'])
	client = str(record['client'])
	ttype = str(record['ttype'])
	id = str(int(record['id']))
	season = int(record['season'])
	episode = int(record['episode'])
	tconst = str(record['tconst'])
	mode = str(record['mode'])
	
	query_count = "select tconst from playlist where tconst = '"+tconst+"' and mode = 'sequence' order by id;"
	print query_count
	result_count = htdb_db.query(query_count)
	result_count_list = list(result_count.dictresult())
	count = len(result_count_list)
	
	episode = episode+count
	
	query_episodes = "select season, episode from tv_files where tconst = '"+tconst+"' and season = "+str(int(season))+" and episode = "+str(int(episode))+";"
	print query_episodes
	result_epi = htdb_db.query(query_episodes)
	result_epi_list = list(result_epi.dictresult())
	if len(result_epi_list) == 1:
		query_set = "update playlist set season = '"+str(int(season))+"', episode = '"+str(int(episode))+"' where client = '"+client+"' and label = '"+label+"' and id = "+str(int(id))+";"
		print query_set
		result_set = htdb_db.query(query_set)
	else:
		query_episodes = "select episode from tv_files where tconst = '"+tconst+"' and season = "+str(int(season))+" and episode > "+str(int(episode))+" order by episode limit 1;"
		print query_episodes
		result_epi = htdb_db.query(query_episodes)
		result_epi_list = list(result_epi.dictresult())
		if (len(result_epi_list ) > 0):
			for record_epi in result_epi_list:
				episode = int(record_epi['episode'])
				query_set = "update playlist set season = '"+str(int(season))+"', episode = '"+str(int(episode))+"' where client = '"+client+"' and label = '"+label+"' and id = "+str(int(id))+";"
				print query_set
				result_set = htdb_db.query(query_set)
		else:
			season = season+1
			episode = 1
			query_episodes = "select episode, season from tv_files where tconst = '"+tconst+"' and season = "+str(int(season))+" and episode >= "+str(int(episode))+" order by episode limit 1;"
			print query_episodes
			result_epi = htdb_db.query(query_episodes)
			result_epi_list = list(result_epi.dictresult())
			if (len(result_epi_list ) > 0):
				for record_epi in result_epi_list:
					episode = int(record_epi['episode'])
					season = int(record_epi['season'])
					query_set = "update playlist set season = '"+str(int(season))+"', episode = '"+str(int(episode))+"' where client = '"+client+"' and label = '"+label+"' and id = "+str(int(id))+";"
					print query_set
					result_set = htdb_db.query(query_set)
			else:
				print "reset season as well as episode"
				season = 1
				episode = 1
				query_episodes = "select episode, season from tv_files where tconst = '"+tconst+"' and season = "+str(int(season))+" and episode >= "+str(int(episode))+" order by episode limit 1;"
				print query_episodes
				result_epi = htdb_db.query(query_episodes)
				result_epi_list = list(result_epi.dictresult())
				if (len(result_epi_list ) > 0):
					for record_epi in result_epi_list:
						episode = int(record_epi['episode'])
						season = int(record_epi['season'])
						query_set = "update playlist set season = '"+str(int(season))+"', episode = '"+str(int(episode))+"' where client = '"+client+"' and label = '"+label+"' and id = "+str(int(id))+";"
						print query_set
						result_set = htdb_db.query(query_set)

htdb_db.close()
