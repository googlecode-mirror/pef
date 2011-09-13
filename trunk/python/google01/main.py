#!/usr/bin/env python
#
# Copyright 2007 Google Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
from google.appengine.ext import webapp
from google.appengine.ext.webapp import util
import datetime
from google.appengine.ext import db
from google.appengine.api import users
import Queue
import threading
import urllib2
import time
from BeautifulSoup import BeautifulSoup
from google.appengine.api import urlfetch
import re


class MainHandler(webapp.RequestHandler):
    def get(self):
        self.response.out.write('Hello world!')


class Employee(db.Model):
  name = db.StringProperty(required=True)
  role = db.StringProperty(required=True, choices=set(["executive", "manager", "producer"]))
  #hire_date = db.DateProperty()
  new_hire_training_completed = db.BooleanProperty()
  account = db.StringProperty()
  
class urladdress(db.Model):
    text = db.TextProperty()

def main():
    application = webapp.WSGIApplication([('/', MainHandler)],
                                         debug=True)
    util.run_wsgi_app(application)
    
    e = Employee(name="henry",
             role="executive",
             account='chijiao')
    #e.hire_date = datetime.datetime.now()
    #e.put()
    
    url = "http://www.google.com.hk"
    header = {'Cookie':'yCartOrderLogic={&TheSkus&:[{&Id&:20046196$&Num&:1}]}'}
    result = urlfetch.fetch(url,'','GET',header)
    #print result.headers
    ###print result.content
    soup = BeautifulSoup(result.content)
    ###<span class="price">?134.00</span>
    price = soup.findAll('span',{'class':'price'})
    href = soup.findAll('a',href=True)
    
    print href
    print price
    
    for i in href:
        print i['href']
        u = urladdress()
        u.text = db.Text(result.content,"utf-8")
        u.put()
    query = urladdress.all()
    print query

    


if __name__ == '__main__':
    main()
