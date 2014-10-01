# Combines the 3 seperate parts of dataset 1 into one large file
import numpy as np
import matplotlib.pyplot as plt

# loading seperate parts of the dataset
data_1 = np.genfromtxt('Datasets/Dataset 1/jester-data-1.csv', delimiter=',')
data_2 = np.genfromtxt('Datasets/Dataset 1/jester-data-2.csv', delimiter=',')
data_3 = np.genfromtxt('Datasets/Dataset 1/jester-data-3.csv', delimiter=',')

joined_data = np.concatenate((data_1, data_2, data_3)) # Concatenating dataset

np.savetxt('Datasets/Dataset 1/joined_data.csv', joined_data, delimiter=',', fmt='%.2f') # Exporting the dataset