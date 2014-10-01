import numpy as np
import matplotlib.pyplot as plt


def plot_histogram(data, xlabel='', ylabel=''):
	figure = plt.figure() # Creating a new figure

	plt.xlabel(xlabel) # Setting the label for the x axis
	plt.ylabel(ylabel) # Setting the label for the y axis
	plt.hist(data) # Plot the histogram

	figure.show() # Show the plot

data = np.genfromtxt('Datasets/sql_dump.csv', delimiter=',') # Read the dataset from the file
data = data[1:, :] # Drop the first row (row headings)

user_ids = data[:, 0] # First column is user_ids
user_ids = list(map(int, user_ids)) # Converting user_ids to ints

min_id = np.min(user_ids) # First user in the database
max_id = np.max(user_ids) # Last user in the database

unique_ids = set(user_ids) # List of unique ids

user_ids.sort() # Sort the user ids

total_ids = len(unique_ids) # Total number of unique users
count = [0] * max_id # Create a vector of 0s

print 'Max user id: {0}'.format(max_id)
print '# of Unique ids: {0}'.format(total_ids)

for i in range(len(user_ids)):
	user = user_ids[i] # Get the ith user id
	count[user - 1] += 1 # This user has rated one more joke

nonzero_count = list(filter(lambda x: x != 0, count)) # Remove non-zero ratings
low_num_rating_count = list(filter(lambda x: x <= 10, count)) # Keep ratings less than 10

plot_histogram(count)
plot_histogram(nonzero_count)
plot_histogram(low_num_rating_count)