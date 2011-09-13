#!/usr/bin/env python

import re
import os
import sys
from download import dl
from BeautifulSoup import BeautifulSoup as bfs
import threading
import string
import Queue


class jingdong:
	
	def __init__(self):
		return None
	
	def read_all_sort(self,sort_file):
		if(sort_file == None):
			print "Error: No sort_file list"
			return False
		f = open(sort_file)
		try:
			for i in f.readlines():
				print i
		except:
			print 'Error: read sort_file list failed'
			return False
		finally:
			f.close()
			return True
		
		
		
def main():
	jd = jingdong()
	jd.read_all_sort('/Users/apple/code/python/search/360.txt')
	
	
	
	

if __name__ == '__main__':
    main()