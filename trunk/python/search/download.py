#!/usr/bin/env python

import socket
import urllib2
import urllib
import sys
import threading

class dl:
	
	url = ''
	
	def __init__(self,url):
		''' download a file from url '''
		self.url = url
		
		
	def get(self):
		#url = urllib.urlencode(self.url)
		req = urllib2.Request(self.url)
		req.add_header('User-Agent','Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html')
		try:
		
			response = urllib2.urlopen(req)
			code = response.code
			content = response.read()
			type = sys.getfilesystemencoding()   
			#content = unicode (content,"gb18030").encode("utf-8")
			return content
		except urllib2.URLError, e:
			return None
		except urllib2.HTTPError, e:
		   return None
		
		