#!/usr/bin/python

import _pg
import os
import commands
import string


htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

query = "select channelid, title, tconst from tv_recording_hint where state = 'active';"
print query
result2 = htdb_db.query(query)
result2_list = list(result2.dictresult())

for record2 in result2_list:
	title = str(record2['title'])
	channelid = str(record2['channelid'])
	tconst = str(record2['tconst'])
	
	title = string.replace(title, "'", "''")
	
	timeoffset = "15"
	query_offset = "select timeoffset from tv_channel_info where channelid = "+channelid+";"
	print query_offset
	result_offset = htdb_db.query(query_offset)
	result_offset_list = list(result_offset.dictresult())
	
	for record_offset in result_offset_list:
		timeoffset = str(record_offset['timeoffset'])
	
	print channelid, title
	
	query = "select id, starttime::timestamp - interval '"+timeoffset+" seconds' as starttime, endtime, endtime-starttime as runtime, channelid, title, episodetitle, episodenumdd, episodenumc, stamp from tv_info where starttime > now() and title = '"+title+"' and channelid = '"+channelid+"' order by starttime;"
	if channelid == "0.0":
		query = "select id, starttime::timestamp - interval '"+timeoffset+"' seconds' as starttime, endtime, endtime-starttime as runtime, channelid, title, episodetitle, episodenumdd, episodenumc, stamp from tv_info where starttime > now() and title = '"+title+"' order by starttime;"
	print query
	result = htdb_db.query(query)
	result_list = list(result.dictresult())

	for record in result_list:
		starttime = str(record['starttime'])
		endtime = str(record['endtime'])
		runtime = str(record['runtime'])
		channelid = str(record['channelid'])
		title = str(record['title'])
		episode = str(record['episodetitle'])
		episode_num = str(record['episodenumc'])
		episode_id = str(record['episodenumdd'])
		rec_id = str(int(record['id']))
		
		normaltitle = string.replace(title, "'", "")
		normaltitle = string.lower(title)
		normaltitle = string.replace(title, "&apos;", "")
		parts = string.split(normaltitle, ":")
		normaltitle = parts[0]
		
		if len(episode_num) > 0:
			season_thing = string.split(episode_num, "E")
			season_str = season_thing[0]
			season_str = string.replace(season_str, "S", "")
			episode_str = season_thing[1]
		else:
			season = "0"
			epi_parts = string.split(episode_id, ".")
			if len(epi_parts) == 2:
				episode_num = epi_parts[1]
				if len(episode) == 0:
					episode = episode_num
			else:
				episode = "0"
		
		title = string.replace(title, "'", "''")
		
		query = "select * from tv_files where primarytitle = '"+title+"' and season = "+season_str+" and episode = "+episode_str+";"
		print query
		result3 = htdb_db.query(query)
		result3_list = list(result3.dictresult())
		if len(result3_list) == 0:
			query = "select * from tv_files where normaltitle = '"+normaltitle+"' and season = "+season_str+" and episode = "+episode_str+";"
			print query
			result3 = htdb_db.query(query)
			result3_list = list(result3.dictresult())
			if len(result3_list) == 0:
				query = "select * from tv_recording where title = '"+title+"' and episodetitle = '"+episode+"' and episodenum = '"+episode_num+"';"			
				print query
				result3 = htdb_db.query(query)
				result3_list = list(result3.dictresult())
				if len(result3_list) == 0:
					values = "'"+starttime+"', '"+endtime+"', '"+channelid+"', '"+title+"', '"+episode+"', '"+episode_num+"', 'pending', now(), '"+runtime+"', '"+episode_id+"', '"+tconst+"'"
					query = "insert into tv_recording (starttime, endtime, channelid, title, episodetitle, episodenum, recordstatus, stamp, runtime, episodenumdd, tconst) values ("+values+");"
					print query
					result2 = htdb_db.query(query)

htdb_db.close()
