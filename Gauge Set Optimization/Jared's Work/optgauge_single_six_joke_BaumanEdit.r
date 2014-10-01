## Compare performance of Single and Double while building set of 4 Jokes

rm(list = ls()) # Empty environment
setwd("/Users/virajmahesh/Documents/Workspace/Eigentaste") # Switch workspace.
library(matrixStats) # Load the matrix stats library

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
# multiply by 1 to conver from TRUE and FALSE
for (i in 1:NUMJOKES) { # Iterate throughs all the jokes
  if (newgauge[i]) { # If this joke is to be considered for gauge set inclusion
    pcadat = pcadat[is.finite(pcadat[,i]),]  # include all non-zero ratings of this joke
  }
}

## DATA SET VERIFICATION
print("DATA SET VERIFICATION")
count = pcadat # Create a finiteness indicator matrix, same size as pcadat
count[is.finite(pcadat)] = 1 # Set all the finitie values to 1
count[!is.finite(pcadat)] = 0 # Set all the NA values to 0
newgauge = as.vector(colSums(count) == dim(pcadat)[1]) # The new gauge set 
notgauge = as.vector(colSums(count) < dim(pcadat)[1]) # What is not in the gauge set

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

# Runs eigentaste using gauge set and data, returns clustering of data
eigentaste <- function(data, gauge) {
  if(LOGGING)
    print("RUNNING EIGENTASTE")
  thisdata = data[,gauge] # Ratings for the jokes that are part of the gauge set
  
  if(LOGGING)
    print("PERFORMING PCA")
  prin = princomp(thisdata) # Perform the PCA to get the first two eigenvectors
  comp1 = prin$loadings[,1] %*% t(thisdata) # The first component (X)
  comp2 = prin$loadings[,2] %*% t(thisdata) # The second component (Y)

  # find max and min x, y values for scaling purposes 
  maxx = max(comp1) # Maximum x
  minx = min(comp1) # Minimum x
  maxy = max(comp2) # Maximum y
  miny = min(comp2) # Minimum y
  
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

# Clusters data based upon value of SINGLE gauge variable, returns
# clustering without performing PCA (as this requires two gauge variables)
eigentaste2 <- function(data, gauge) {
  datagauge = data[,gauge]
  
  comp1 = as.matrix(datagauge)
  
  # get uppper and lower bounds of projection
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

# We should be using notgauge as defined at the very beginning of the
# program. If we are trying to determine which of 20 jokes to include
# in the guage set, none of those jokes should ever be used as part
# of the testing procedure 

#getNotGauge <- function(gauge) {
#  notgauge = rep(TRUE, 100)
#  for (i in gauge) {
#    notgauge[i] = FALSE
#  }
#  notgauge
#}

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
  View(testout)
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

  count = difference
  count[is.finite(difference)] = 1
  count[!is.finite(difference)] = 0
  nmae = sum(difference, na.rm = TRUE)/(20 * sum(count))
  nmae
}

# This function is used for finding the NMAE of a single joke
NMAE2 <- function(test, train, gauge, notgauge) {
  clust = eigentaste2(train, thisGauge) # Get the cluster
  testclust = eigentaste2(test, thisGauge) # Get the test cluster
  pred = generatePredictions(train, notgauge, clust)

  difference = generateDifferences(pred, test, notgauge, testclust)
  difference = abs(difference)
  
  count = difference
  count[is.finite(difference)] = 1
  count[!is.finite(difference)] = 0
  nmae = sum(difference, na.rm = TRUE)/(20 * sum(count))
  nmae

}

## OPTIMIZING NMAE FOR SINGLE JOKES
allNMAE = matrix(nrow = length(gauge), ncol = 2) # Create a matrix to store the NMAE of each joke
optGauge = gauge[1:6] # just pick the first 6 jokes of the gauge set
bestNMAE = 999; # Large initial value for best NMAE

# PICK FIRST JOKE

## BREAKING DATA SET INTO TRAINING AND TESTING SETS
sel = sample(dim(pcadat)[1], dim(pcadat)[1]/3) # select a random sample of 1/3 of the observations
train = pcadat[sel,]; # Use the selected observations: 13,333 observations 
test = pcadat[-sel,]; # Use everything except the selected observations: 26,666 observations

sel2 = sample(dim(test)[1], dim(test)[1]/2)
select = test[sel2,]; # 13,333 observations 
test = test[-sel2,]; # 13,333 observations

for (j in 1:length(gauge)) { # Iterating through all the jokes
    nmae = NMAE(select, train, gauge[j], notgauge)
    
    print(c("Joke:", j, ", NMAE:", nmae)) # Print the NMAE of the new gauge
    
    if(nmae < bestNMAE) { # If the NMAE is lower than the best we have so far
      optGauge[1] = gauge[j] # Store the new gauge as the optimum gauge
      bestNMAE = nmae
    }
}

print("First Joke: ")
print(optGauge[1])
print("Performance: ")

# INSERT OUT OF SAMPLE NMAE CODE HERE

for (i in 2:6) { # fix all the jokes from 1 to 6

	## BREAKING DATA SET INTO TRAINING AND TESTING SETS
	sel = sample(dim(pcadat)[1], dim(pcadat)[1]/3) # select a random sample of 1/3 of the observations
	train = pcadat[sel,]; # Use the selected observations: 13,333 observations 
	test = pcadat[-sel,]; # Use everything except the selected observations: 26,666 observations

	sel2 = sample(dim(test)[1], dim(test)[1]/2)
	select = test[sel2,]; # 13,333 observations 
	test = test[-sel2,]; # 13,333 observations 
	

  for (j in 1:length(gauge)) { # Iterating through all the jokes
    newGauge = optGauge
    if(gauge[j] %in% optGauge[1:(i-1)]) { # The joke is already being considered
      next
    }
    newGauge[i] = gauge[j] # Change one of the jokes in this set
    
    ## PRINTING DATA FOR LOGGING
    print("Opt Gauge")
    print(optGauge)
    print("New Gauge")
    print(newGauge)
    
    # perform analysis using first i jokes in gauge
    nmae = NMAE(select, train, newGauge[1:i,], notgauge)
    nmae2 = 
    
    print(c("NMAE:", nmae)) # Print the NMAE of the new gauge
    
    if(nmae < bestNMAE) { # If the NMAE is lower than the best we have so far
      optGauge = newGauge # Store the new gauge as the optimum gauge
      bestNMAE = nmae
    }
  }
  
  # INSERT OUT OF SAMPLE NMAE CODE HERE
}

bestNMAE
optGauge