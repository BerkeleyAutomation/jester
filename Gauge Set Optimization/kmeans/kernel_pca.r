## Using K-medoids

require(compiler) # Enabling compilation
enableJIT(3)

rm(list = ls())
# Switch workspace
setwd("/Users/virajmahesh/Documents/Workspace/Eigentaste/kmeans/")
# Load the library for pam
library(cluster)
library(kernlab) # the kernlab library for kernel pca

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

# Calculate the NMAE for a given number of dimensions and clusters
NMAE<-function(n_dim, n_clusters) {
  cat("Selecting a random sample of half the users\n")
  
  users = dim(pca_data)[1] # number of users
  jokes = dim(pca_data)[2] # number of jokes
  
  selection = sample(users, users/4) # Random selection of half of the data
  train = pca_data[selection,] # Training data
  test = pca_data[-selection] # Testing data
  
  cluster_data = train[, gauge] # Data to be used for clustering
  
  cat("Performing PCA\n")
  pca = kpca(cluster_data, features=n_dim) # Compute PCA and keep the n dimensions
  cluster_data = rotated(pca)

  cat("Performing clustering\n")
  clusters = pam(cluster_data, n_clusters, cluster.only=TRUE, do.swap=FALSE, keep.diss=FALSE, keep.data=FALSE, trace.lev=3) # Perform clustering
  
  cat("Generating predictions\n")
  predictions = c() # Empty table
  
  for (i in 1:n_clusters) { # for each cluster
    members = train[clusters == i, ] # Ratings by users in the cluster for jokes not in the gauge set
    predicted_value = apply(members, 2, median, na.rm=TRUE) # Prediction for one cluster
    predictions = rbind(predictions, predicted_value) # Add prediction
  }
  
  cat("Calculation error\n")
  error = 0
  ratings = 0
  
  for (i in 1:(users/4)) {
    cat("User ", i, " of ", users/4, "\n")
    for (j in 1:jokes) {
      if (! j %in% gauge) {
        cluster = clusters[i]
        pred = predictions[cluster, j]
        real = train[i, j]
        error = error + abs(pred - real)/20.0
        ratings = ratings + 1
      }
    }
  }
  
  nmae = error/ratings
  cat("NMAE: ", nmae, "\n")
  
  nmae
}