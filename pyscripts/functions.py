from constants import *
from numpy import *

def arg_max(d):
    max_value = -10000
    max_key = None
    for key in d.keys():
        if d[key] > max_value:
            max_value = d[key]
            max_key = key
    return max_key
    
def python_list_to_output(l):
    output = ''
    for elem in l:
        output += str(elem) + ' '
    return output

def list_to_dict(value_list):
    index = 1
    value_dict = {}
    for value in value_list:
        value_dict[index] = value
        index += 1
    return value_dict