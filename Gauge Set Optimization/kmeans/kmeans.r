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

# Calculate the NMAE for a given number of dimensions and clusters
NMAE<-function(n_dim, n_clusters) {
  cat("Selecting a random sample of half the users\n")
  
  users = dim(pca_data)[1] # number of users
  jokes = dim(pca_data)[2] # number of jokes
  
  selection = sample(users, users/2) # Random selection of half of the data
  train = pca_data[selection,] # Training data
  test = pca_data[-selection] # Testing data
  
  cluster_data = train[, gauge] # Data to be used for clustering
  
  cat("Performing PCA\n")
  pca = princomp(cluster_data, scores=TRUE) # Compute PCA and keep the scores
  scores = pca$scores # Get the scores after PCA
  cluster_data = scores[, 1:n_dim] # Keep n_dim dimensions
  explained_variance = sum((as.vector(pca$sdev^2/sum(pca$sdev^2)))[1:n_dim])
  cat("Performing clustering\n")
  clusters = kmeans(cluster_data, n_clusters, iter.max=20)$cluster # Perform clustering
  
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
  
  for (i in 1:(users/2)) {
    cat("User ", i, " of ", users/2, "\n")
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
  
  c(explained_variance, nmae)
}

results = c()
for (dimension in 1:6) { # for each dimension
  for (i in 1:5) { # Take 5 observations per dimension
    results = rbind(results, NMAE(dimension, 50)) # 50 clusters, accumulate result
  }
}

plot(results, xlab='Explained Variance', ylab='NMAE')

results_2 = c()
for (dimension in 1:6) { # for each dimension
  for (i in 1:10) { # Take 5 observations per dimension
    results_2 = rbind(results_2, NMAE(dimension, 50)) # 50 clusters, accumulate result
  }
}

results_3 = c()
results_4 = c()
for (dimension in 1:6) {
  prev = (dimension-1)*10 + 1
  curr = dimension*10
  section = results_2[prev:curr,]
  avg = apply(section, 2, mean)
  med = apply(section, 2, median)
  results_3 = rbind(results_3, avg)
  results_4 = rbind(results_4, med)
}

plot(results, xlab='Explained Variance', ylab='NMAE (5 obs.)')
plot(results_2, xlab='Explained Variance', ylab='NMAE (10 obs.)')
plot(results_3, type='o', xlab='Explained Variance', ylab='NMAE (mean)')
plot(results_4, type='o', xlab='Explained Variance', ylab='NMAE (median)')