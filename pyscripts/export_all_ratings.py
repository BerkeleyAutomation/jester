#! /usr/bin/python

import MySQLdb
import sys
from constants import *
from functions import *
from numpy import *
import random

conn = MySQLdb.connect(host=SQL_HOST, user=SQL_USER, passwd=SQL_PASSWORD, db=SQL_DB)
cursor = conn.cursor()
query = 'SELECT jokerating FROM dd.%s' % 'ratings'
cursor.execute(query)
rating_tuples = cursor.fetchall()
for rating_tuple in rating_tuples:
    print rating_tuple[0]
cursor.close()
conn.close()