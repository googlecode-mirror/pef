import threading
import datetime
import Queue
import urllib2
import time



hosts = ['http://www.sina.com','http://www.163.com']
queue = Queue.Queue()

class ThreadClass(threading.Thread):
    
    def __init__(self,queue):
        threading.Thread.__init__(self)
        self.queue = queue
    
    def run(self):
        while True:
            host = self.queue.get()
            
            url = urllib2.urlopen(host)
            print url.read(1024)
            
            self.queue.task_done()


for i in range(2):
    t = ThreadClass()
    t.start()
    
