#!/usr/bin/python

import commands, sys, string, os, _pg

id = "0"
try:
	id = sys.argv[1]
except:
	id = "0"

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

pids = []
	
query = "select station, filename from tv_live where id = '"+id+"';"
if id == "0":
	query = "select station, filename from tv_live where state = 'active';"

print query
result = htdb_db.query(query)
result_list = list(result.dictresult())

for record in result_list:
	station = str(record['station'])
	filename = str(record['filename'])

	kill_cmd = "/bin/ps -ewf | /bin/grep \""+filename+"\""
	print kill_cmd
	buffer = commands.getoutput(kill_cmd)
	
	buff_lines = string.split(buffer, "\n")
	for line in buff_lines:
#		print line
		if string.find(line, "ffmpeg") > 0:
			line_parts = string.split(line)
			print line_parts[1]
			pids.append(line_parts[1])

#print pids

if len(pids) > 0:
	kill_cmd = "/bin/kill "
	for pid in pids:
		kill_cmd = kill_cmd+pid+" "

	print kill_cmd
	os.system(kill_cmd)

	query = "update tv_live set state = 'killed' where id = '"+id+"';";
	print query
	result = htdb_db.query(query)

	del_cmd = "/bin/rm -rf "+filename
	print del_cmd
	os.system(del_cmd)

	filename_ts = string.replace(filename, ".m3u8", "*.ts")
	del_cmd = "/bin/rm -rf "+filename_ts
	print del_cmd
	os.system(del_cmd)

htdb_db.close()
