# Optimization of predictive jokes, looking outside of initial gauge set
# Uses Jester1 data set rather than 2+, which is used in other investigations

rm(list = ls())
#setwd("C:/Users/Jared/Desktop/EECS_Lab")
library(matrixStats)

##################  Preprocessing   ###################

dat = read.csv("jester1.csv", header = FALSE)
pcadat = as.matrix(dat[,-1])
pcadat[pcadat == 99] <- NA # clean up missing values
count = pcadat
count[is.finite(pcadat)] = 1
count[!is.finite(pcadat)] = 0
hist(colSums(count), 48) # histogram with 48 partitions

# Note which jokes were rated by more than 90% of respondants (at least 43,634 
# out of 48,483 potential respondants). Drop users that did not respond to all
# of these jokes.
NUMJOKES = dim(pcadat)[2]
THRESH = .9 * dim(pcadat)[1]
newgauge = as.matrix(1*(colSums(count) >= THRESH))
for (i in 1:NUMJOKES) {
	if (newgauge[i]) {
		pcadat = pcadat[is.finite(pcadat[,i]),]
	}
}

# verify new gauge set built
count = pcadat
count[is.finite(pcadat)] = 1
count[!is.finite(pcadat)] = 0
hist(colSums(count), 400) # histogram with 400 partitions
newgauge = as.vector(colSums(count) == dim(pcadat)[1])
notgauge = as.vector(colSums(count) < dim(pcadat)[1])


############ Break data set into training and testing sets ###########

# break data set into training and two testing sets
sel = sample(dim(pcadat)[1], dim(pcadat)[1]/3)
train = pcadat[sel,]; # 13,333 observations 
test = pcadat[-sel,]; # 26,666 observations

sel2 = sample(dim(test)[1], dim(test)[1]/2)
test2 = test[sel2,]; # 13,333 observations 
test = test[-sel2,]; # 13,333 observations 

# Create dead zone over [-1, 1] to remove inliers
keep = ((train[,] < -1) | (train[,] > 1))
keep = keep*1
keep[is.na(keep)] = 0
train[keep < 1] = NA

# build gauge set
gauge = c()
for (i in 1:dim(pcadat)[2]) {
	if (newgauge[i]) {
		gauge = c(gauge, i)
	}
}

allNMAE = matrix(nrow = length(gauge), ncol = length(gauge))
bestNMAE = 1 # in-sample NMAE
bestNMAEout = 1 # out-of-sample NMAE
bestGauge = c(999)

this = 1:length(gauge)
this = this[-c(5, 15, 21, 22)] #5, 15,

# iterate through all pairs of jokes in the guage set
for (i in this) {
	# because R can't manage to exit this nested for loop gracefully...
	if (i == length(gauge)) break
	
	for (j in (i+1):length(gauge)) {
	
		if (j == 5) next
		if (j == 15) next
		if (j == 21) next
		if (j == 22) next

		thisGauge <- gauge[c(i, j, 5, 15, 21, 22)]
		print(thisGauge)
	
		################# Use selected elements of guage set to cluster training data ##########################
		traingauge = train[,thisGauge]
		keepgauge = keep[,thisGauge]
		
		# compute PCA
		means = colMeans(traingauge, na.rm = TRUE)/colMeans(keepgauge)
		for (k in 1:length(thisGauge)) { traingauge[,k] = traingauge[,k] - means[k] }
		traingauge[keepgauge < 1] = 0
		
		# create covariance matrix
		covar = t(traingauge) %*% traingauge
		penalty = t(keepgauge) %*% keepgauge - 1
		covar = covar / penalty

		# perform a spectral decomposition of the matrix
		# prin = princomp(traingauge)
		eigen = eigen(covar, symmetric = TRUE)
		comp1 = as.vector(eigen$vectors[,1] %*% t(traingauge))
		comp2 = as.vector(eigen$vectors[,2]%*% t(traingauge))
		
		# get dimensions of projection
		maxx = max(comp1)
		minx = min(comp1)
		maxy = max(comp2)
		miny = min(comp2)
		
		# matrix used to store cluster assignments
		clust = comp1
		
		# create basic clusters
		for (k in 1:length(clust)) {
			if (comp1[k] >= 0 && comp2[k] >= 0) clust[k] = 0 
			if (comp1[k] < 0 && comp2[k] >= 0) clust[k] = 10
			if (comp1[k] >= 0 && comp2[k] < 0) clust[k] = 20
			if (comp1[k] < 0 && comp2[k] < 0) clust[k] = 30
		}

		# break first quadrant into ten clusters
		for (k in 1:length(clust)) {
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

		# break second quadrant into ten clusters
		for (k in 1:length(clust)) { 
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

		# break third quadrant into ten clusters
		for (k in 1:length(clust)) {
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

		# break fourth quadrant into ten clusters
		for (k in 1:length(clust)) { 
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
		
		################# Use the same elements of guage set to cluster test data ##########################
		testgauge = test[,thisGauge]
		comp1 = as.vector(eigen$vectors[,1] %*% t(testgauge))
		comp2 = as.vector(eigen$vectors[,2] %*% t(testgauge))
		
		# get dimensions of projection
		maxx = max(comp1)
		minx = min(comp1)
		maxy = max(comp2)
		miny = min(comp2)
		
		# matrix used to store cluster assignments
		testclust = comp1
		
		# create basic clusters
		for (k in 1:length(testclust)) {
			if (comp1[k] >= 0 && comp2[k] >= 0) testclust[k] = 0 
			if (comp1[k] < 0 && comp2[k] >= 0) testclust[k] = 10
			if (comp1[k] >= 0 && comp2[k] < 0) testclust[k] = 20
			if (comp1[k] < 0 && comp2[k] < 0) testclust[k] = 30
		}
		sum(testclust == 0, na.rm=TRUE) + sum(testclust == 10, na.rm=TRUE) + sum(testclust == 20, na.rm=TRUE) + sum(testclust == 30, na.rm=TRUE)

		# break first quadrant into ten clusters
		for (k in 1:length(testclust)) {
			if (testclust[k] == 0 && comp1[k] >= maxx/2.0 && comp2[k] >= maxy/2.0) testclust[k] = 9 
			if (testclust[k] == 0 && comp1[k] < maxx/2.0 && comp2[k] >= maxy/2.0) testclust[k] = 8
			if (testclust[k] == 0 && comp1[k] >= maxx/2.0 && comp2[k] < maxy/2.0) testclust[k] = 7
			if (testclust[k] == 0 && comp1[k] >= maxx/4.0 && comp2[k] >= maxy/4.0) testclust[k] = 6 
			if (testclust[k] == 0 && comp1[k] < maxx/4.0 && comp2[k] >= maxy/4.0) testclust[k] = 5
			if (testclust[k] == 0 && comp1[k] >= maxx/4.0 && comp2[k] < maxy/4.0) testclust[k] = 4
			if (testclust[k] == 0 && comp1[k] >= maxx/8.0 && comp2[k] >= maxy/8.0) testclust[k] = 3 
			if (testclust[k] == 0 && comp1[k] < maxx/8.0 && comp2[k] >= maxy/8.0) testclust[k] = 2
			if (testclust[k] == 0 && comp1[k] >= maxx/8.0 && comp2[k] < maxy/8.0) testclust[k] = 1
		}

		# break second quadrant into ten clusters
		for (k in 1:length(testclust)) { minx
			if (testclust[k] == 10 && comp1[k] < minx/2.0 && comp2[k] >= maxy/2.0) testclust[k] = 19 
			if (testclust[k] == 10 && comp1[k] >= minx/2.0 && comp2[k] >= maxy/2.0) testclust[k] = 18
			if (testclust[k] == 10 && comp1[k] < minx/2.0 && comp2[k] < maxy/2.0) testclust[k] = 17
			if (testclust[k] == 10 && comp1[k] < minx/4.0 && comp2[k] >= maxy/4.0) testclust[k] = 16 
			if (testclust[k] == 10 && comp1[k] >= minx/4.0 && comp2[k] >= maxy/4.0) testclust[k] = 15
			if (testclust[k] == 10 && comp1[k] < minx/4.0 && comp2[k] < maxy/4.0) testclust[k] = 14
			if (testclust[k] == 10 && comp1[k] < minx/8.0 && comp2[k] >= maxy/8.0) testclust[k] = 13 
			if (testclust[k] == 10 && comp1[k] >= minx/8.0 && comp2[k] >= maxy/8.0) testclust[k] = 12
			if (testclust[k] == 10 && comp1[k] < minx/8.0 && comp2[k] < maxy/8.0) testclust[k] = 11
		}

		# break third quadrant into ten clusters
		for (k in 1:length(testclust)) {
			if (testclust[k] == 20 && comp1[k] >= maxx/2.0 && comp2[k] < miny/2.0) testclust[k] = 29 
			if (testclust[k] == 20 && comp1[k] < maxx/2.0 && comp2[k] < miny/2.0) testclust[k] = 28
			if (testclust[k] == 20 && comp1[k] >= maxx/2.0 && comp2[k] >= miny/2.0) testclust[k] = 27
			if (testclust[k] == 20 && comp1[k] >= maxx/4.0 && comp2[k] < miny/4.0) testclust[k] = 26 
			if (testclust[k] == 20 && comp1[k] < maxx/4.0 && comp2[k] < miny/4.0) testclust[k] = 25
			if (testclust[k] == 20 && comp1[k] >= maxx/4.0 && comp2[k] >= miny/4.0) testclust[k] = 24
			if (testclust[k] == 20 && comp1[k] >= maxx/8.0 && comp2[k] < miny/8.0) testclust[k] = 23 
			if (testclust[k] == 20 && comp1[k] < maxx/8.0 && comp2[k] < miny/8.0) testclust[k] = 22
			if (testclust[k] == 20 && comp1[k] >= maxx/8.0 && comp2[k] >= miny/8.0) testclust[k] = 21
		}

		# break fourth quadrant into ten clusters
		for (k in 1:length(testclust)) { 
			if (testclust[k] == 30 && comp1[k] < minx/2.0 && comp2[k] < miny/2.0) testclust[k] = 39 
			if (testclust[k] == 30 && comp1[k] >= minx/2.0 && comp2[k] < miny/2.0) testclust[k] = 38
			if (testclust[k] == 30 && comp1[k] < minx/2.0 && comp2[k] >= miny/2.0) testclust[k] = 37
			if (testclust[k] == 30 && comp1[k] < minx/4.0 && comp2[k] < miny/4.0) testclust[k] = 36 
			if (testclust[k] == 30 && comp1[k] >= minx/4.0 && comp2[k] < miny/4.0) testclust[k] = 35
			if (testclust[k] == 30 && comp1[k] < minx/4.0 && comp2[k] >= miny/4.0) testclust[k] = 34
			if (testclust[k] == 30 && comp1[k] < minx/8.0 && comp2[k] < miny/8.0) testclust[k] = 33 
			if (testclust[k] == 30 && comp1[k] >= minx/8.0 && comp2[k] < miny/8.0) testclust[k] = 32
			if (testclust[k] == 30 && comp1[k] < minx/8.0 && comp2[k] >= miny/8.0) testclust[k] = 31
		}	
		
		
		
		############### Use the same elements of guage set to cluster 2nd test data #######################
		testgauge2 = test2[,thisGauge]
		comp1 = as.vector(eigen$vectors[,1] %*% t(testgauge2))
		comp2 = as.vector(eigen$vectors[,2] %*% t(testgauge2))
		
		# get dimensions of projection
		maxx = max(comp1)
		minx = min(comp1)
		maxy = max(comp2)
		miny = min(comp2)
		
		# matrix used to store cluster assignments
		testclust2 = comp1
		
		# create basic clusters
		for (k in 1:length(testclust2)) {
			if (comp1[k] >= 0 && comp2[k] >= 0) testclust2[k] = 0 
			if (comp1[k] < 0 && comp2[k] >= 0) testclust2[k] = 10
			if (comp1[k] >= 0 && comp2[k] < 0) testclust2[k] = 20
			if (comp1[k] < 0 && comp2[k] < 0) testclust2[k] = 30
		}
		sum(testclust2 == 0, na.rm=TRUE) + sum(testclust2 == 10, na.rm=TRUE) + sum(testclust2 == 20, na.rm=TRUE) + sum(testclust2 == 30, na.rm=TRUE)

		# break first quadrant into ten clusters
		for (k in 1:length(testclust2)) {
			if (testclust2[k] == 0 && comp1[k] >= maxx/2.0 && comp2[k] >= maxy/2.0) testclust2[k] = 9 
			if (testclust2[k] == 0 && comp1[k] < maxx/2.0 && comp2[k] >= maxy/2.0) testclust2[k] = 8
			if (testclust2[k] == 0 && comp1[k] >= maxx/2.0 && comp2[k] < maxy/2.0) testclust2[k] = 7
			if (testclust2[k] == 0 && comp1[k] >= maxx/4.0 && comp2[k] >= maxy/4.0) testclust2[k] = 6 
			if (testclust2[k] == 0 && comp1[k] < maxx/4.0 && comp2[k] >= maxy/4.0) testclust2[k] = 5
			if (testclust2[k] == 0 && comp1[k] >= maxx/4.0 && comp2[k] < maxy/4.0) testclust2[k] = 4
			if (testclust2[k] == 0 && comp1[k] >= maxx/8.0 && comp2[k] >= maxy/8.0) testclust2[k] = 3 
			if (testclust2[k] == 0 && comp1[k] < maxx/8.0 && comp2[k] >= maxy/8.0) testclust2[k] = 2
			if (testclust2[k] == 0 && comp1[k] >= maxx/8.0 && comp2[k] < maxy/8.0) testclust2[k] = 1
		}

		# break second quadrant into ten clusters
		for (k in 1:length(testclust2)) { minx
			if (testclust2[k] == 10 && comp1[k] < minx/2.0 && comp2[k] >= maxy/2.0) testclust2[k] = 19 
			if (testclust2[k] == 10 && comp1[k] >= minx/2.0 && comp2[k] >= maxy/2.0) testclust2[k] = 18
			if (testclust2[k] == 10 && comp1[k] < minx/2.0 && comp2[k] < maxy/2.0) testclust2[k] = 17
			if (testclust2[k] == 10 && comp1[k] < minx/4.0 && comp2[k] >= maxy/4.0) testclust2[k] = 16 
			if (testclust2[k] == 10 && comp1[k] >= minx/4.0 && comp2[k] >= maxy/4.0) testclust2[k] = 15
			if (testclust2[k] == 10 && comp1[k] < minx/4.0 && comp2[k] < maxy/4.0) testclust2[k] = 14
			if (testclust2[k] == 10 && comp1[k] < minx/8.0 && comp2[k] >= maxy/8.0) testclust2[k] = 13 
			if (testclust2[k] == 10 && comp1[k] >= minx/8.0 && comp2[k] >= maxy/8.0) testclust2[k] = 12
			if (testclust2[k] == 10 && comp1[k] < minx/8.0 && comp2[k] < maxy/8.0) testclust2[k] = 11
		}

		# break third quadrant into ten clusters
		for (k in 1:length(testclust2)) {
			if (testclust2[k] == 20 && comp1[k] >= maxx/2.0 && comp2[k] < miny/2.0) testclust2[k] = 29 
			if (testclust2[k] == 20 && comp1[k] < maxx/2.0 && comp2[k] < miny/2.0) testclust2[k] = 28
			if (testclust2[k] == 20 && comp1[k] >= maxx/2.0 && comp2[k] >= miny/2.0) testclust2[k] = 27
			if (testclust2[k] == 20 && comp1[k] >= maxx/4.0 && comp2[k] < miny/4.0) testclust2[k] = 26 
			if (testclust2[k] == 20 && comp1[k] < maxx/4.0 && comp2[k] < miny/4.0) testclust2[k] = 25
			if (testclust2[k] == 20 && comp1[k] >= maxx/4.0 && comp2[k] >= miny/4.0) testclust2[k] = 24
			if (testclust2[k] == 20 && comp1[k] >= maxx/8.0 && comp2[k] < miny/8.0) testclust2[k] = 23 
			if (testclust2[k] == 20 && comp1[k] < maxx/8.0 && comp2[k] < miny/8.0) testclust2[k] = 22
			if (testclust2[k] == 20 && comp1[k] >= maxx/8.0 && comp2[k] >= miny/8.0) testclust2[k] = 21
		}

		# break fourth quadrant into ten clusters
		for (k in 1:length(testclust2)) { 
			if (testclust2[k] == 30 && comp1[k] < minx/2.0 && comp2[k] < miny/2.0) testclust2[k] = 39 
			if (testclust2[k] == 30 && comp1[k] >= minx/2.0 && comp2[k] < miny/2.0) testclust2[k] = 38
			if (testclust2[k] == 30 && comp1[k] < minx/2.0 && comp2[k] >= miny/2.0) testclust2[k] = 37
			if (testclust2[k] == 30 && comp1[k] < minx/4.0 && comp2[k] < miny/4.0) testclust2[k] = 36 
			if (testclust2[k] == 30 && comp1[k] >= minx/4.0 && comp2[k] < miny/4.0) testclust2[k] = 35
			if (testclust2[k] == 30 && comp1[k] < minx/4.0 && comp2[k] >= miny/4.0) testclust2[k] = 34
			if (testclust2[k] == 30 && comp1[k] < minx/8.0 && comp2[k] < miny/8.0) testclust2[k] = 33 
			if (testclust2[k] == 30 && comp1[k] >= minx/8.0 && comp2[k] < miny/8.0) testclust2[k] = 32
			if (testclust2[k] == 30 && comp1[k] < minx/8.0 && comp2[k] >= miny/8.0) testclust2[k] = 31
		}	

		###################### Use train clusters to make predictions for test clusters #####################

		# generate matrix of predictions
		trainout = train[,notgauge]
		testout = test[,notgauge]
		test2out = test2[,notgauge]


		pred = matrix(nrow = 40, ncol = dim(trainout)[2])
		for (k in 1:dim(trainout)[2]) {
			for (l in 0:39) {
				pred[l+1, k] = mean(trainout[clust == l, k], na.rm = TRUE) 
			}
		}

		# generate matrix of differences between predictions and realizations
		val = testout
		for (k in 1:dim(testout)[2]) {
			for (l in 0:39) {
				val[testclust == l, k] = val[testclust == l, k] - pred[l+1, k] 
			}
		}
		
		# differences for second test set
		val2 = test2out
		for (k in 1:dim(test2out)[2]) {
			for (l in 0:39) {
				val2[testclust2 == l, k] = val2[testclust2 == l, k] - pred[l+1, k] 
			}
		}

		# construct matrix of absolute values of differences
		val = abs(val)
		sum(val, na.rm = TRUE)
		
		val2 = abs(val2)
		sum(val2, na.rm = TRUE)
		
		

		# construct nmae statistic
		count = val
		count[is.finite(val)] = 1
		count[!is.finite(val)] = 0
		nmae = sum(val, na.rm = TRUE)/(20 * sum(count))
		print(nmae)
		
		count2 = val2
		count2[is.finite(val2)] = 1
		count2[!is.finite(val2)] = 0
		nmae2 = sum(val2, na.rm = TRUE)/(20 * sum(count2))
		print(nmae2)
		
		
		
		allNMAE[i,j] = nmae
		
		if (nmae < bestNMAE) {
			bestGauge = thisGauge
			bestNMAE = nmae
			bestNMAEout = nmae2
		}
	}
}
t(allNMAE)
bestNMAE
bestGauge
bestNMAEout
