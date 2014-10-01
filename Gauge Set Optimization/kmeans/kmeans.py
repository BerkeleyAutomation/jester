# Required imports
import numpy as np
from numpy.random import random_integers
from sklearn.decomposition import PCA
from scipy.cluster.vq import kmeans, vq

# Selects a random subset of half the data as training data
def produce_data(data):
	shape = data.shape
	num_users = shape[0]
	num_jokes = shape[1]

	print 'Selecting training data'
	# select half the users
	random_sample = random_integers(0, high=num_users - 1, size=num_users/2)
	other = np.array([i for i in range(num_users) if i not in random_sample])
	train = data[random_sample, :] # keep the randomly chosen users
	test = data[other, :]
	print 'Done'
	return train, test

# Returns the median of a row after removing nan values
def nan_median(row):
	return np.median(row[~np.isnan(row)])

# Perform PCA and then subsequently k-means clustering
def perform_clustering(data, gauge, dim, n_clusters=20, out_of_sample=True, out_data=[]):
	print 'Performing PCA'
	cluster_data = data[:, gauge]
	pca = PCA(n_components=dim) # Create a PCA model with 4 components
	pca.fit(cluster_data) # fit the model to the data
	cluster_data = pca.transform(cluster_data) # get the new data
	print 'Done'

	print 'Performing clustering'
	code_book = kmeans(cluster_data, n_clusters, iter=30)[0]
	clusters = vq(cluster_data, code_book)[0]
	print 'Done'
	return (clusters, code_book)

# Calculate the nmae of a data and gauge set
def nmae(data, gauge, jokes=100, dim=4, n_clusters=20):
	print 'Calculating NMAE'
	# Get the cluster that each user belongs to
	clusters, code_book = perform_clustering(data, gauge, dim, n_clusters)
	shape = data.shape
	users = shape[0]
	error = 0
	ratings = 0

	for user in range(users):
		print str(user) + ' of ' + str(users)
		for joke in range(jokes): # For each joke
			if joke in gauge: # Don't rate the gauge set jokes
				continue
			cluster = clusters[user] # cluster that this user belongs to
			# Ratings from members of the same cluster
			members = data[clusters == cluster, joke]
			pred = nan_median(members) # prediction is median value
			real = data[user, joke] # real value
			diff = abs(pred - real) # absoloute value of difference
			error += diff/20.0 # add to total error
			ratings += 1

	nmae = error/ratings
	return (nmae, code_book) # Return the code book along with NMAE
	# Use code book for out of sample

def nmae_out(code_book, data, gauge, jokes=100, dim=4, n_clusters=20):
	print 'Calculating out of sample NMAE'

	print 'Performing PCA'
	cluster_data = data[:, gauge]
	pca = PCA(n_components=dim) # Create a PCA model with 4 components
	pca.fit(cluster_data) # fit the model to the data
	cluster_data = pca.transform(cluster_data) # get the new data
	print 'Done'

	clusters = vq(cluster_data, code_book)[0] # get the clusters of out of sample data
	shape = data.shape
	users = shape[0]
	error = 0
	ratings = 0

	for user in range(users):
		print str(user) + ' of ' + str(users)
		for joke in range(jokes): # For each joke
			if joke in gauge: # Don't rate the gauge set jokes
				continue
			cluster = clusters[user] # cluster that this user belongs to
			# Ratings from members of the same cluster
			members = data[clusters == cluster, joke]
			pred = nan_median(members) # prediction is median value
			real = data[user, joke] # real value
			diff = abs(pred - real) # absoloute value of difference
			error += diff/20.0 # add to total error
			ratings += 1

	nmae = error/ratings
	return (nmae, code_book) # Return the code book along with NMAE
	# Use code book for out of sample

print 'Loading data from file'
data = np.genfromtxt('../jester1.csv', delimiter=',') # Read the file
print 'Done'

print 'Defining gauge set'
gauge = np.array([13, 25, 27, 37, 38, 60]) # Predefined gauge set
# Jokes not in the gauge set
not_gauge = np.array([i for i in range(100) if i not in gauge])
print 'Done'

print 'Reshaping data set'
data = data[:, 1:] # Dropping the first column
data[data == 99] = None # Set missing values to None
print 'Done'

print 'Selecting rows to keep'
# Only keep the users that have rated all data
rows_to_keep = np.apply_along_axis(lambda row:
									reduce(lambda x, y: x and y, row), 1, ~np.isnan(data))
pca_data = data[rows_to_keep, :] # PCA_data is only the data we want to keep
print 'Done'

print 'Defining constants'
shape = pca_data.shape
num_users = shape[0]
num_jokes = shape[1]
print 'Done'

## Generating data for number of clusters vs. NMAE
def clusters_vs_nmae():
	all_nmaes = []
	all_nmaes_out = []
	clusters = []
	for i in range(15, 100, 5):
		print 'Computing NMAE for ' + str(i) + ' clusters'
		train, test = produce_data(pca_data)
		NMAE, code_book = nmae(train, gauge, n_clusters=i) # in sample nmae
		NMAE_out, code_book = nmae_out(code_book, test, gauge) # out of sample nmae
		print 'Result: ' + str(NMAE)
		clusters.append(i) # Add the number of clusters
		all_nmaes.append(NMAE) # Add the NMAE from this number of clusters
		all_nmaes_out.append(NMAE_out) # Add the out of sample NMAE
	return (clusters, all_nmaes, all_nmaes_out) # return list of all clusters and nmaes
