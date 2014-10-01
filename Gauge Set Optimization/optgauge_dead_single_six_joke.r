## Compare performance of Single and Double while building set of 4 Jokes

rm(list = ls()) # Empty environment
setwd("/Users/virajmahesh/Documents/Workspace/Eigentaste") # Switch workspace.
library(matrixStats) # Load the matrix stats library

## DEAD ZONE SIZE
SIZE = 3

## PREPROCESSING
print("PREPROCESSING")

dat = read.csv("jester1.csv", header = FALSE) # Read the jester data from the .csv file
pcadat = as.matrix(dat[,-1]) # Convert the data.frame to a matrix removing the first column
pcadat[pcadat == 99] <- NA # replace values of 99 with NA
count = pcadat # Creating a copy of pcadat
count[is.finite(pcadat)] = 1 # Replace all finite values with a 1
count[!is.finite(pcadat)] = 0 # Replace all the NAs with a 0

## BUILDING GAUGE SET
# Note which jokes were rated by more than 90% of respondants (at least 43,634 
# out of 48,483 potential respondants). Drop users that did not respond to all
# of these jokes.
print("BUILDING GAUGE SET")
NUMJOKES = dim(pcadat)[2] # Get the dimensions of PCA dat. NUMJOKES = # of cols
THRESH = .9 * dim(pcadat)[1] # Set the threshold THRESH = 90% of respondents = 90% of # of rows
newgauge = as.matrix(1*(colSums(count) >= THRESH)) # Sum of columns should be more than THRESH
pcadat[pcadat > -SIZE & pcadat < SIZE] <- NA
# multiply by 1 to conver from TRUE and FALSE
for (i in 1:NUMJOKES) { # Iterate throughs all the jokes
  if (newgauge[i]) { # If this user is to be included in the gauge set
    pcadat = pcadat[is.finite(pcadat[,i]),]  # include all non-zero ratings from this user
  }
}

## DATA SET VERIFICATION
print("DATA SET VERIFICATION")
count = pcadat # Create a copy of the new pcadat
count[is.finite(pcadat)] = 1 # Set all the finitie values to 1
count[!is.finite(pcadat)] = 0 # Set all the NA values to 0
newgauge = as.vector(colSums(count) == dim(pcadat)[1]) # The new gauge set 
notgauge = as.vector(colSums(count) < dim(pcadat)[1]) # What is not in the gauge set

## BREAKING DATA SET INTO TRAINING AND TESTING SETS
sel = sample(dim(pcadat)[1], dim(pcadat)[1]/3) # select a random sample of 1/3 of the observations
train = pcadat[sel,]; # Use the selected observations: 13,333 observations 
test = pcadat[-sel,]; # Use everything except the selected observations: 26,666 observations

LOGGING = FALSE
## CREATING NUMERIC GAUGE SET
gauge = c()
for (i in 1:dim(pcadat)[2]) {
  if (newgauge[i]) {
    gauge = c(gauge, i)
  }
}

checkCluster <- function(clust) {
  (sum(clust == 0, na.rm=TRUE) + sum(clust == 10, na.rm=TRUE) + 
     sum(clust == 20, na.rm=TRUE) + sum(clust == 30, na.rm=TRUE)) == dim(clust)[2]
}

eigentaste <- function(data, gauge) { # Runs eigentaste using gauge set and data
  if(LOGGING)
    print("RUNNING EIGENTASTE")
  thisdata = data[,gauge] # Ratings for the jokes that are part of the gauge set
  
  if(LOGGING)
    print("PERFORMING PCA")
  prin = princomp(thisdata) # Perform the PCA to get the first two eigenvectors
  comp1 = prin$loadings[,1] %*% t(thisdata) # The first component (X)
  comp2 = prin$loadings[,2] %*% t(thisdata) # The second component (Y)
  
  maxx = max(comp1) # Maximum x ?
  minx = min(comp1) # Minimum x ?
  maxy = max(comp2) # Maximum y ?
  miny = min(comp2) # Minimum y ?
  
  clust = as.matrix(comp1) # Convert the first component to a matrixs
  
  if(LOGGING)
    print("CREATING CLUSTERS")
  ## CREATING BASIC CLUSTERS
  for (k in 1:dim(clust)[2]) {
    if (comp1[k] >= 0 && comp2[k] >= 0) clust[k] = 0 
    if (comp1[k] < 0 && comp2[k] >= 0) clust[k] = 10
    if (comp1[k] >= 0 && comp2[k] < 0) clust[k] = 20
    if (comp1[k] < 0 && comp2[k] < 0) clust[k] = 30
  }
  
  ## BREAK FIRST QUADRANT INTO CLUSTERS
  for (k in 1:dim(clust)[2]) {
    if (clust[k] == 0 && comp1[k] >= maxx/2.0 && comp2[k] >= maxy/2.0) clust[k] = 9 
    if (clust[k] == 0 && comp1[k] < maxx/2.0 && comp2[k] >= maxy/2.0) clust[k] = 8
    if (clust[k] == 0 && comp1[k] >= maxx/2.0 && comp2[k] < maxy/2.0) clust[k] = 7
    if (clust[k] == 0 && comp1[k] >= maxx/4.0 && comp2[k] >= maxy/4.0) clust[k] = 6 
    if (clust[k] == 0 && comp1[k] < maxx/4.0 && comp2[k] >= maxy/4.0) clust[k] = 5
    if (clust[k] == 0 && comp1[k] >= maxx/4.0 && comp2[k] < maxy/4.0) clust[k] = 4
    if (clust[k] == 0 && comp1[k] >= maxx/8.0 && comp2[k] >= maxy/8.0) clust[k] = 3 
    if (clust[k] == 0 && comp1[k] < maxx/8.0 && comp2[k] >= maxy/8.0) clust[k] = 2
    if (clust[k] == 0 && comp1[k] >= maxx/8.0 && comp2[k] < maxy/8.0) clust[k] = 1
  }
  
  ## BREAK SECOND QUADRANT INTO CLUSTERS
  for (k in 1:dim(clust)[2]) { 
    if (clust[k] == 10 && comp1[k] < minx/2.0 && comp2[k] >= maxy/2.0) clust[k] = 19 
    if (clust[k] == 10 && comp1[k] >= minx/2.0 && comp2[k] >= maxy/2.0) clust[k] = 18
    if (clust[k] == 10 && comp1[k] < minx/2.0 && comp2[k] < maxy/2.0) clust[k] = 17
    if (clust[k] == 10 && comp1[k] < minx/4.0 && comp2[k] >= maxy/4.0) clust[k] = 16 
    if (clust[k] == 10 && comp1[k] >= minx/4.0 && comp2[k] >= maxy/4.0) clust[k] = 15
    if (clust[k] == 10 && comp1[k] < minx/4.0 && comp2[k] < maxy/4.0) clust[k] = 14
    if (clust[k] == 10 && comp1[k] < minx/8.0 && comp2[k] >= maxy/8.0) clust[k] = 13 
    if (clust[k] == 10 && comp1[k] >= minx/8.0 && comp2[k] >= maxy/8.0) clust[k] = 12
    if (clust[k] == 10 && comp1[k] < minx/8.0 && comp2[k] < maxy/8.0) clust[k] = 11
  }
  
  ## BREAK THIRD QUADRANT INTO CLUSTERS
  for (k in 1:dim(clust)[2]) {
    if (clust[k] == 20 && comp1[k] >= maxx/2.0 && comp2[k] < miny/2.0) clust[k] = 29 
    if (clust[k] == 20 && comp1[k] < maxx/2.0 && comp2[k] < miny/2.0) clust[k] = 28
    if (clust[k] == 20 && comp1[k] >= maxx/2.0 && comp2[k] >= miny/2.0) clust[k] = 27
    if (clust[k] == 20 && comp1[k] >= maxx/4.0 && comp2[k] < miny/4.0) clust[k] = 26 
    if (clust[k] == 20 && comp1[k] < maxx/4.0 && comp2[k] < miny/4.0) clust[k] = 25
    if (clust[k] == 20 && comp1[k] >= maxx/4.0 && comp2[k] >= miny/4.0) clust[k] = 24
    if (clust[k] == 20 && comp1[k] >= maxx/8.0 && comp2[k] < miny/8.0) clust[k] = 23 
    if (clust[k] == 20 && comp1[k] < maxx/8.0 && comp2[k] < miny/8.0) clust[k] = 22
    if (clust[k] == 20 && comp1[k] >= maxx/8.0 && comp2[k] >= miny/8.0) clust[k] = 21
  }
  
  ## BREAK FOURTH QUADRANT INTO CLUSTERS
  for (k in 1:dim(clust)[2]) { 
    if (clust[k] == 30 && comp1[k] < minx/2.0 && comp2[k] < miny/2.0) clust[k] = 39 
    if (clust[k] == 30 && comp1[k] >= minx/2.0 && comp2[k] < miny/2.0) clust[k] = 38
    if (clust[k] == 30 && comp1[k] < minx/2.0 && comp2[k] >= miny/2.0) clust[k] = 37
    if (clust[k] == 30 && comp1[k] < minx/4.0 && comp2[k] < miny/4.0) clust[k] = 36 
    if (clust[k] == 30 && comp1[k] >= minx/4.0 && comp2[k] < miny/4.0) clust[k] = 35
    if (clust[k] == 30 && comp1[k] < minx/4.0 && comp2[k] >= miny/4.0) clust[k] = 34
    if (clust[k] == 30 && comp1[k] < minx/8.0 && comp2[k] < miny/8.0) clust[k] = 33 
    if (clust[k] == 30 && comp1[k] >= minx/8.0 && comp2[k] < miny/8.0) clust[k] = 32
    if (clust[k] == 30 && comp1[k] < minx/8.0 && comp2[k] >= miny/8.0) clust[k] = 31
  }
  clust # Return the newly generated cluster
}

eigentaste2 <- function(data, gauge) {
  datagauge = data[,gauge]
  
  comp1 = as.matrix(datagauge)
  
  # get dimensions of projection
  maxx = max(comp1)
  minx = min(comp1)
  
  # matrix used to store cluster assignments
  clust = as.matrix(comp1)
  
  # create basic clusters
  for (k in 1:dim(clust)[1]) {
    if (comp1[k] >= 0) clust[k] = 0 
    if (comp1[k] < 0) clust[k] = 10
  }
  
  # break first quadrant into ten clusters
  for (k in 1:dim(clust)[1]) {
    if (clust[k] == 0 && comp1[k] >= maxx/1.4142) clust[k] = 9 
    if (clust[k] == 0 && comp1[k] >= maxx/2.0) clust[k] = 8
    if (clust[k] == 0 && comp1[k] >= maxx/2.8284) clust[k] = 7
    if (clust[k] == 0 && comp1[k] >= maxx/4.0) clust[k] = 6 
    if (clust[k] == 0 && comp1[k] >= maxx/5.6569) clust[k] = 5
    if (clust[k] == 0 && comp1[k] >= maxx/8.0) clust[k] = 4
    if (clust[k] == 0 && comp1[k] >= maxx/11.3137) clust[k] = 3 
    if (clust[k] == 0 && comp1[k] >= maxx/16.0) clust[k] = 2
    if (clust[k] == 0 && comp1[k] >= maxx/22.6274) clust[k] = 1
  }
  
  # break first quadrant into ten clusters
  for (k in 1:dim(clust)[1]) {
    if (clust[k] == 10 && comp1[k] < minx/1.4142) clust[k] = 19 
    if (clust[k] == 10 && comp1[k] < minx/2.0) clust[k] = 18
    if (clust[k] == 10 && comp1[k] < minx/2.8284) clust[k] = 17
    if (clust[k] == 10 && comp1[k] < minx/4.0) clust[k] = 16 
    if (clust[k] == 10 && comp1[k] < minx/5.6569) clust[k] = 15
    if (clust[k] == 10 && comp1[k] < minx/8.0) clust[k] = 14
    if (clust[k] == 10 && comp1[k] < minx/11.3137) clust[k] = 13 
    if (clust[k] == 10 && comp1[k] < minx/16.0) clust[k] = 12
    if (clust[k] == 10 && comp1[k] < minx/22.6274) clust[k] = 11
  }
  clust # Return the cluster
}

# Calculates the NMAE given a vector of differences
calculateNMAE <- function(difference) {
  count = difference
  count[is.finite(difference)] = 1
  count[!is.finite(difference)] = 0
  nmae = sum(difference, na.rm = TRUE)/(20 * sum(count))
  nmae
}

# Returns predicted ratings for jokes in not considered for 
# admission to the gauge set
generatePredictions <- function(train, notgauge, clust) {
  trainout = train[, notgauge] # All the jokes that are not part of the gauge set
  pred = matrix(nrow = 40, ncol = dim(trainout)[2]) # Creates an empty matrix of predictions
  for (k in 1:dim(trainout)[2]) {
    for (l in 0:39) {
      pred[l+1, k] = mean(trainout[clust == l, k], na.rm = TRUE) 
    }
  }
  pred
}

# Returns errors in predicted ratings for jokes in not considered 
# for admission to the gauge set
generateDifferences <- function(pred, test, notgauge, testclust) {
  testout = test[, notgauge] # Jokes in the test set that are not part of the gauge set
  val = testout # Create a copy of this data
  for (k in 1:dim(testout)[2]) {
    for (l in 0:39) {
      val[testclust == l, k] = val[testclust == l, k] - pred[l+1, k] # Subtract predictions to get error
    }
  }
  val
}

# This function is used for finding the NMAE of a set of jokes
NMAE <- function(test, train, gauge, notgauge) { # Construct the NMAE
  clust = eigentaste(train, gauge) # Cluster from training data
  testclust = eigentaste(test, gauge) # Cluster from test data
  
  ## MAKE PREDICTIONS FOR TEST DATA
  if(LOGGING)
    print("MAKING PREDICTIONS FOR TEST DATA")
  pred = generatePredictions(train, notgauge, clust) # Generating predictions
  
  ## GENERATING DIFFERENCE BETWEEN PREDICTION AND REALIZATION
  difference = generateDifferences(pred, test, notgauge, testclust)
  difference = abs(difference) # Taking the absoloute value of the difference for NMAE
  
  calculateNMAE(difference)
}

# This function is used for finding the NMAE of a single joke
NMAE2 <- function(test, train, gauge, notgauge) {
  clust = eigentaste2(train, gauge) # Get the cluster
  testclust = eigentaste2(test, gauge) # Get the test cluster
  pred = generatePredictions(train, notgauge, clust)
  
  difference = generateDifferences(pred, test, notgauge, testclust)
  difference = abs(difference)
  
  calculateNMAE(difference)
}

# Construct the NMAE that returns both actual NMAE and NMAE from out of sample
NMAE_OUT <- function(select, test, train, gauge, notgauge) {
  clust = eigentaste(train, gauge) # Cluster from training data
  selectclust = eigentaste(select, gauge) # Cluster from selection data
  testclust = eigentaste(test, gauge) # Cluster from testing data
  
  ## MAKE PREDICTIONS FOR TEST DATA
  if(LOGGING)
    print("MAKING PREDICTIONS FOR TEST DATA")
  pred = generatePredictions(train, notgauge, clust) # Generating predictions
  
  ## GENERATING DIFFERENCE BETWEEN PREDICTION AND REALIZATION
  difference = generateDifferences(pred, select, notgauge, selectclust)
  difference = abs(difference) # Taking the absoloute value of the difference for NMAE
  difference_out = generateDifferences(pred, test, notgauge, testclust)
  difference_out = abs(difference_out)
  
  nmae = calculateNMAE(difference)
  nmae_out = calculateNMAE(difference_out)
  
  c(nmae, nmae_out)
}

# Construct the NMAE that returns both actual NMAE and NMAE from out of sample
# Works with a gauge set that has a single
NMAE2_OUT <- function(select, test, train, gauge, notgauge) {
  clust = eigentaste2(train, gauge) # Get the cluster
  selectclust = eigentaste2(select, gauge) # Get the select cluster
  testclust = eigentaste2(test, gauge)
  
  pred = generatePredictions(train, notgauge, clust)
  
  ## GENERATING DIFFERENCE BETWEEN PREDICTION AND REALIZATION
  difference = generateDifferences(pred, select, notgauge, selectclust)
  difference = abs(difference) # Taking the absoloute value of the difference for NMAE
  difference_out = generateDifferences(pred, test, notgauge, testclust)
  difference_out = abs(difference_out)
  
  nmae = calculateNMAE(difference)
  nmae_out = calculateNMAE(difference_out)
  
  c(nmae, nmae_out)
}

## OPTIMIZING NMAE FOR SINGLE JOKES
allNMAE = matrix(nrow = length(gauge), ncol = 2) # Create a matrix to store the NMAE of each joke
optGauge = c() # initially empty
bestNMAE = 999 # Large initial value for best NMAE
bestNMAEOut = 999 # Large initial value for the best NAME out of sample


for (i in 1:6) { # fix all the jokes from 1 to 6
  optGauge = c(optGauge, 0) # Add space for another element
  
  for (j in 1:length(gauge)) { # Iterating through all the jokes
    
    newGauge = optGauge # start of with the previously assumed optimum gauge
    
    if(gauge[j] %in% optGauge) { # Joke is already part of the gauge set
      next
    }
    
    newGauge[i] = gauge[j] # Set the ith new gauge set joke to the jth joke we are testing
    
    ## BREAKING DATA SET INTO SELECTION, TESTING AND TRAINING
    random_sample = sample(dim(pcadat)[1], dim(pcadat)[1]/3) # select a random sample of 1/3 of the observations
    train = pcadat[sel,]; # Use the selected observations: 13,333 observations 
    test = pcadat[-sel,]; # Use everything except the selected observations: 26,666 observations
    
    sel2 = sample(dim(test)[1], dim(test)[1]/2)
    select = test[sel2,]; # 13,333 observations 
    test = test[-sel2,]; # 13,333 observations 
    
    if(length(newGauge) == 1) {
      result = NMAE2_OUT(select, test, train, newGauge, notgauge)
    }
    else {
      result = NMAE_OUT(select, test, train, newGauge, notgauge) # calculate nmae of new gauge
    }
    nmae = result[1]
    nmae_out = result[2]
    
    ## PRINTING DATA FOR LOGGING
    print("Opt Gauge")
    print(optGauge)
    print("New Gauge")
    print(newGauge)
    print(c("NMAE:", nmae)) # Print the NMAE of the new gauge
    print(c("NMAE OUT:", nmae_out)) # Print the Out of Sample NMAE of the new gauge
    print("") # Empty Line
    
    if(nmae < bestNMAE || j == 1) { # If the NMAE is lower than the best we have so far
      optGauge = newGauge # Store the new gauge as the optimum gauge
      bestNMAE = nmae # Store the new NMAE as the best NMAE
      bestNMAEOut = nmae_out # Store the Out of Sample NMAE
    }
  }
}

bestNMAE
bestNMAEOut
optGauge