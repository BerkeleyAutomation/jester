#! /usr/bin/python

import MySQLdb
import sys
from constants import *
from functions import *
from numpy import *
import random
from time import time

"""
Dataset format:

[Item ID]: [Item Name]

"""            
conn = MySQLdb.connect(host=SQL_HOST, user=SQL_USER, passwd=SQL_PASSWORD, db='dd')
cursor = conn.cursor()
query = 'SELECT jokeid, joketext FROM jester4and5.%s ORDER BY jokeid ASC' % 'jokes'
cursor.execute(query)
rating_tuples = cursor.fetchall()
for rating_tuple in rating_tuples:
    item_id = str(rating_tuple[0])
    item_text = str(rating_tuple[1])
    print item_id + ':\n' + item_text
cursor.close()
conn.close()