#!/usr/bin/env python

import re
import sys
from download import dl
from BeautifulSoup import BeautifulSoup as bfs
import threading
import string
import Queue
import db



class joyo:
	
	baseurl = 'http://www.amazon.cn/s/ref=nb_sb_noss?url=search-alias='
	detail_url = 'http://www.amazon.cn/mn/detailApp?asin=B0010LEDBA'
	url = ''
	product = {}
	
	def __init__(self,url = None,product = None):
		self.url = url
		self.product = product
		return None
		
	def get_price(self,url):
		return price
	
	def get_good_page(self,url):
		d = dl(url)
		content = d.get()
		if (content != None):
			soup = bfs(content)
			count = soup.findAll('div',{'id':'resultCount'})[0].contents[0]
			string = re.search('\d*,\d{3}',count).group()
			string = re.sub(',','',string)
			page = int(int(string)/24) + 1
			return page
		return 1

	

	def get_good_list(self):
		#1 get page count
		url = self.baseurl + self.url
		
		page = self.get_good_page(url)
		# start get good list
		for i in range(1,page):
			good_list = []
			#print "start get page " + str(i) + "  " + self.url
			fetch_url = url + '&page=' + str(i)
			print fetch_url
			d = dl(fetch_url)
			content = d.get()

			if (content != None):
				soup = bfs(content)
				# search good list
				product1 = soup.findAll('div',{'class':re.compile('result [\S ]*product')})
				num = len(product1)
				
				# insert to db
				d = db.db()
					
				# get product id
				for p_id in range(num):
					product_id = product1[p_id].attrs[2][1]
					good_list.append(product_id)
					#print product_id
					
					#data = []
					#data.append(product_id)
					d.query('insert into joyo value (\'' + product_id + '\')')
					#del data
			#print (good_list)
			
			# unset good_list
			#del(good_list)
				
		return True			
					
	def get_title(self):
		return tilte



class parse_Thread(threading.Thread):
	
	def __init__(self,queue):
		return None


class start(threading.Thread):
	
	def __init__(self,queue,product = None):
		threading.Thread.__init__(self)
		self.queue = queue
		self.product = product
		print 'product is ' + product + "\n"
		
		
	def run(self):
		# for evever
		while True:
			if self.queue.empty():
				break
			# get task
			url = self.queue.get()
			print url + "\r"
			# do task
			jo = joyo(url,self.product)
			product = jo.get_good_list()
			
			
			
			print product
			
			# finish
			self.queue.task_done()

def main():
	list = {'home-appliances':1,'communications':1,'beauty':1,'kitchen':1}
	queue = Queue.Queue()
	
	# add task
	for i in list:
		queue.put(i)	
	
	# start threading
	for i in list:
		#jo = joyo(i)
		#jo.get_good_list()
		#print jo.product
		t = start(queue,i)
		t.setDaemon(True)
		t.start()

	#it's ok
	queue.join()

if __name__ == '__main__':
    main()
