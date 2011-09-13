#!/usr/bin/env python

from download import dl



def main():
	url = 'http://www.163.com'
	d = dl(url)
	
	print d.get()


if __name__ == '__main__':
    main()