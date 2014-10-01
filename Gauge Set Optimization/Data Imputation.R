# Clearing environment
rm(list = ls())
setwd("/Users/virajmahesh/Documents/Workspace/Eigentaste/kmeans/")

# Defining constant
DIFFERENCE <- 20

cat("Reading Data")
raw.data <- read.csv("../jester1.csv", header = FALSE) 
pca.data <- as.matrix(raw.data[, -1])
pca.data[pca.data == 99] <- NA

# gauge <- c(14, 26, 28, 38, 39, 61) # Chosen gauge set
# not.gauge <- (1:100)[-gauge] # Jokes not in the gauge set

RemoveNAs <- function(data) {
  for (i in 1:ncol(data)) {
    data <- data[is.finite(data[, i]), ]
  }
  data
}

CalculatePredictions <- function(train.data, clusters, num.clusters, func = median) {
  predictions <- c()
  print(num.clusters)
  for (i in 1:num.clusters) { # for each cluster
    cluster.members <- train.data[clusters == i, ] # Ratings by users in the cluster for jokes not in the gauge set
    predicted.values <- apply(cluster.members, 2, func, na.rm = TRUE) # Prediction for one cluster
    predictions <- rbind(predictions, predicted.values) # Add prediction
  }
  predictions
}

# NMAE as a function of data, dimensions and clusters
NMAE<-function(pca.data, dimensions, num.clusters, orig.data = NA) {
  # No original data is passed
  if (is.na(orig.data)) {
    orig.data <- pca.data
  }
  
  cat("Removing users that have not rated all jokes\n")
  pca.data <- RemoveNAs(pca.data)
  
  cat("Selecting a random sample of half the users\n")
  
  users <- nrow(pca.data) # Number of Users
  jokes <- ncol(pca.data) # Number of Jokes
  
  selection <- sample(users, users/2) # Random selection of half of the data
  
  train.data <- pca.data[selection, ] # Training data
  test.data <- pca.data[-selection ] # Testing data
  
  cluster.data <- train.data # Data to be used for clustering
  
  cat("Performing PCA\n")
  pca <- princomp(cluster.data, scores = TRUE) # Compute PCA and keep the scores
  cluster.data <- pca$scores[, 1:dimensions] # Keep the specified number of dimensions
  explained.variance <- sum((as.vector(pca$sdev^2 / sum(pca$sdev^2)))[1:dimensions]) # Calculate explained variance
  
  cat("Performing clustering\n")
  clusters <- kmeans(cluster.data, num.clusters, iter.max <- 20)$cluster # Perform clustering
  
  cat("Generating predictions\n")
  predictions <- c() # Empty table
  
  cat("Calculation error\n")
  error <- 0 # Total error
  ratings <- 0 # Number of jokes rated
  
  predictions <- CalculatePredictions(train.data, clusters, num.clusters, median)
  print(predictions)
  
  for (i in 1:nrow(train.data)) {
    cat("User ", i, " of ", nrow(train.data), "\n")
    
    for (j in 1:jokes) {
        predicted.value <- predictions[clusters[i], j]
        real.value <- train.data[i, j]
        error <- error + abs(predicted.value - real.value)/DIFFERENCE
        ratings <- ratings + 1
    }
  }
  
  nmae <- error/ratings
  cat("NMAE: ", nmae, "\n")
  
  c(explained.variance, nmae)
}

ImputeData <- function(data, func) {
  for (i in 1:ncol(data)) {
    data[is.na(data[, i]), i] = func(data[, i], na.rm = TRUE)
  }
  data
}

imputed.data = ImputeData(pca.data, median)

imputed.nmaes = c()
for (i in 1:10) {
  nmae = NMAE(imputed.data, 6, 50)
  imputed.nmaes = c(imputed.nmaes, nmae)
}

imputed.data = ImputeData(pca.data, mean)
