#!/usr/bin/python

import subprocess, threading, sys, time, string, os, _pg


device = sys.argv[1]
tuner = sys.argv[2]
station = sys.argv[3]
unique = sys.argv[4]

htdb_db = _pg.connect(host="localhost", user="htdb", passwd="htdb", dbname="htdb")

#query = "update tv_recording set recordstatus = 'recording' where id = "+rec_id+";"
#print query
#hdresult = htdb_db.query(query)

live_cmd = "/usr/bin/ffmpeg -i \"http://"+device+":5004/auto/v"+station+"?transcode=mobile\" -f hls /var/www/html/htdb/htdb-tvlive/hdhomerun_"+station+"_"+unique+".m3u8"

class Command(object):
	def __init__(self, cmd):
		self.cmd = cmd
		self.process = None

	def run(self, timeout):
		print "running thread for ", timeout, " seconds"
		def target():
#			print 'Thread started'
			self.process = subprocess.Popen(self.cmd, shell=True)
			self.process.communicate()
#			print 'Thread finished'

		thread = threading.Thread(target=target)
		thread.start()

		thread.join(timeout)
		if thread.is_alive():
			print 'Terminating process'
			self.process.terminate()
			thread.join()
		print self.process.returncode


print "saving stream of "+station
print live_cmd
os.system(live_cmd)

tuner_id = "tuner_one"
if tuner == "tuner1":
	tuner_id = "tuner_two"

#query = "update hdhomerun_devices set "+tuner_id+" = 'off', tuner_used = tuner_used-1 where ipaddress = '"+device+"';"
#print query
#hdresult = htdb_db.query(query)

htdb_db.close()
