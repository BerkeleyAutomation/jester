## Using K-means

require(compiler) # Enabling compilation
enableJIT(3)

rm(list = ls())
# Switch workspace
setwd("/Users/virajmahesh/Documents/Workspace/Eigentaste/kmeans/")
# Load the library for pam
library(cluster)

cat("Reading Data")
raw_data = read.csv("../jester1.csv", header = FALSE) # Read the jester data from the .csv file
pca_data = as.matrix(raw_data[,-1]) # Convert the data.frame to a matrix removing the first column
pca_data[pca_data == 99] <- NA # replace values of 99 with NA

gauge = c(14, 26, 28, 38, 39, 61) # Chosen gauge set
not_gauge = (1:100)[-gauge] # Jokes not in the gauge set

# Remove those users that have not rated every joke in the gauge set
for (i in 1:100) {
  pca_data = pca_data[is.finite(pca_data[, i]),]
}

plot_variance = function() {
  users = dim(pca_data)[1] # number of users
  jokes = dim(pca_data)[2] # number of jokes

  selection = sample(users, users/2) # Random selection of half of the data
  train = pca_data[selection,] # Training data
  test = pca_data[-selection] # Testing data

  cluster_data = train # Data to be used for clustering

  cat("Performing PCA\n")
  pca = princomp(cluster_data, scores=TRUE) # Compute PCA and keep the scores
  scores = pca$scores # Get the scores after PCA

  result = c()
  for (n_dim in 1:100) {
    explained_variance = sum((as.vector(pca$sdev^2/sum(pca$sdev^2)))[1:n_dim])
    result = c(result, explained_variance)
  }
  result
}

results = plot_variance()