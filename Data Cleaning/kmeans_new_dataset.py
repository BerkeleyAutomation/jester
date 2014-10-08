# Running k-means on the new dataset
import numpy as np
from numpy.random import random_integers
from scipy.stats import nanmedian
from scipy.cluster.vq import kmeans, vq

# Loads a dataset named dataset_name (relative to the current script)
def load_raw_data(dataset_name):
	print 'Loading data'
	raw_data = np.genfromtxt(dataset_name, delimiter=',')
	return raw_data

# Drops a column from the dataset
def drop_column(data, col):
	print 'Droping column {0}'.format(col)
	data = np.hstack((data[:, 0:col], data[:, col + 1:]))
	return data

# Clean the data in-place
def clean_data(data):
	print 'Cleaning data'
	data[data == 99] = np.nan

# Split the dataset into test and train. 80% train, 20% test by default
def split(data, ratio=0.8):
	print 'Splitting data into train and test sets. Split ratio={0}'.format(ratio)
	rows, cols = data.shape
	train_rows = int(rows * ratio) # Number of training rows
	# Perforing a random sample to choose training dataset
	train = random_integers(0, high=rows - 1, size=train_rows)
	# Selecting left over rows for testing dataset
	test = np.array([i for i in range(rows) if i not in train])
	return data[train,:], data[test, :]

# Takes a dataset and the gauge set jokes according to which the 
# data must be clustered. Returns the clusters and the codebook.
def kmeans_clustering(data, gauge, nclusters=20, max_iter=50):
	print 'Clustering with {0} clusters'.format(nclusters)
	cluster_data = data[:, gauge] # Keep only the gauge set ratings
	print 'Dimensions of cluster data {0}'.format(cluster_data.shape)
	# Create a code book that will be used to classify data. Distortion is discarded
	codebook, distortion = kmeans(cluster_data, nclusters, iter=max_iter)
	clusters, distortion = vq(cluster_data, codebook) # Assigning clusters
	return clusters, codebook # Return both clusters and code book

def predictions(data, clusters, nclusters, pred_func=nanmedian):
	print 'Generating predictions'
	rows, cols = data.shape # Storing number of rows and columns
	predictions = [] # All predictions
	for cluster in range(nclusters): # for each cluster
		users = clusters == cluster # the users in the cluster
		ratings = data[users, :] # their ratings
		predictions.append(pred_func(ratings, axis=0)) # Columnwise median
	return predictions


def nmae(data, predictions, clusters, value_range=20.0):
	error = 0
	num_ratings = 0
	for user in range(data.shape[0]):
		cluster = clusters[users] # cluster that user belongs to
		prediction = predictions[cluster] # predicted ratings
		actual = data[user, :] # actual ratings
		abs_diff = np.abs(prediction - actual) # absouloute difference
		total = np.nansum(abs_diff) # total of all errors
		error += total
		num_ratings += np.count_nonzero(~np.is_nan(actual)) # number of ratings
		print error
	return error/(value_range * num_ratings)

raw_data = load_raw_data('Datasets/Dataset 1/joined_data.csv')
data = drop_column(raw_data, 0)
clean_data(data)
train, test = split(data)



