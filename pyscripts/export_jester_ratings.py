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

[User ID] [Item ID] [Rating]

"""            
conn = MySQLdb.connect(host=SQL_HOST, user=SQL_USER, passwd=SQL_PASSWORD, db='dd')
cursor = conn.cursor()
query = 'SELECT userid, jokeid, jokerating FROM jester4and5.%s ORDER BY userid, jokeratingid ASC' % 'ratings'
cursor.execute(query)
rating_tuples = cursor.fetchall()
for rating_tuple in rating_tuples:
    user_id = str(rating_tuple[0])
    item_id = str(rating_tuple[1])
    rating = '%.3f' % rating_tuple[2]
    print user_id + '\t\t' + item_id + '\t\t' + rating
cursor.close()
conn.close()