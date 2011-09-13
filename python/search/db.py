#!/usr/bin/env python
import MySQLdb as mysqldb

class db:
	
	def __init__(self,data=None):
		conn = mysqldb.connect(host='localhost',user='root',passwd='root',db='search')
		self.mysql = conn
		self.cursor=conn.cursor()
		
		return None
		
	def insert(self,table,data):
		#sql="insert into cdinfo values(%s,%s,%s,%s,%s)"
		sql = ''
		for i in range(len(data) - 1):
			sql = sql + '%s,'
		sql = 'insert into ' + table + ' values (' + sql + '%s)'
		n = self.cursor.execute(sql,data)
		self.mysql.commit()
		return n
	
	def query (self,sql):
		n = self.cursor.execute(sql)
		self.mysql.commit()
		return n



