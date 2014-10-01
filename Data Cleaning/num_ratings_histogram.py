import numpy as np
import matplotlib.pyplot as plt

data = np.genfromtxt('Datasets/Dataset 1/joined_data.csv', delimiter=',') # Loading data from file
num_ratings = data[:, 0] # First column is number of ratings

figure = plt.figure() # Creating a new figure

# Setting the x and y axes labels
plt.xlabel('# of jokes rated')
plt.ylabel('# of users')
plt.hist(num_ratings) # Plot the histogram

figure.show() # Show the plot

# Calculating statistics
max_rating = np.max(num_ratings)
min_rating = np.min(num_ratings)
mean_rating = np.mean(num_ratings)
median_rating = np.median(num_ratings)

# Printing results
print 'Min # of jokes rated: {0}: '.format(min_rating)
print 'Max # of jokes rated: {0}: '.format(max_rating)
print 'Mean # of jokes rated: {0}: '.format(mean_rating)
print 'Median # of jokes rated: {0}'.format(median_rating)
