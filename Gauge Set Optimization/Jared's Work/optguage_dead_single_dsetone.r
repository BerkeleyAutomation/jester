# Optimization of predictive jokes, looking outside of initial gauge set
# Uses Jester1 data set rather than 2+, which is used in other investigations

rm(list = ls())
setwd("/Users/virajmahesh/Documents/Workspace/Eigentaste")
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
# break data set into training and testing sets
sel = sample(dim(pcadat)[1], dim(pcadat)[1]/3)
train = pcadat[sel,]; # 13,333 observations 
test = pcadat[-sel,]; # 26,666 observations

sel2 = sample(dim(test)[1], dim(test)[1]/2)
test2 = test[sel2,]; # 13,333 observations 
test = test[-sel2,]; # 13,333 observations 

# Create dead zone over [-2, 2] to remove inliers
keep = ((train[,] < -1) | (train[,] > 1))
keep = keep*1
keep[is.na(keep)] = 0
train[keep < 1] = 999

# build gauge set
gauge = c()
for (i in 1:dim(pcadat)[2]) {
	if (newgauge[i]) {
		gauge = c(gauge, i)
	}
}

allNMAE = matrix(nrow = length(gauge), ncol = 1)
bestNMAE = 1
bestNMAEout = 1
bestGauge = c(999)

# iterate through all pairs of jokes in the guage set
for (i in 1:length(gauge)) {

	thisGauge <- gauge[i]
	print(thisGauge)
	
	################# Use selected elements of guage set to cluster training data ##########################
	traingauge = train[,thisGauge]
	
	comp1 = as.matrix(traingauge)
	
	# get dimensions of projection
	maxx = max(comp1)
	minx = min(comp1)
	
	# matrix used to store cluster assignments
	clust = as.matrix(comp1)
	clust[] = -1
	
	# create basic clusters
	for (k in 1:dim(clust)[1]) {
		if (comp1[k] != 999) {
			if (comp1[k] >= 0) clust[k] = 0 
			if (comp1[k] < 0) clust[k] = 10
		}
	}

	# break first quadrant into ten clusters
	for (k in 1:dim(clust)[1]) {
		if (comp1[k] != 999) {
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
	}

	# break first quadrant into ten clusters
	for (k in 1:dim(clust)[1]) {
		if (comp1[k] != 999) {
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
	}
	
	

	
	
	################# Use the same elements of guage set to cluster test data ##########################
	testgauge = test[,thisGauge]
	
	comp1 = as.matrix(testgauge)
	
		# get dimensions of projection
	maxx = max(comp1)
	minx = min(comp1)
	
	# matrix used to store cluster assignments
	testclust = as.matrix(comp1)
	testclust[] = -1
	
	# create basic clusters
	for (k in 1:dim(testclust)[1]) {
		if (comp1[k] != 999) {
			if (comp1[k] >= 0) testclust[k] = 0 
			if (comp1[k] < 0) testclust[k] = 10
		}
	}

	# break first quadrant into ten clusters
	for (k in 1:dim(testclust)[1]) {
		if (comp1[k] != 999) {
			if (testclust[k] == 0 && comp1[k] >= maxx/1.4142) testclust[k] = 9 
			if (testclust[k] == 0 && comp1[k] >= maxx/2.0) testclust[k] = 8
			if (testclust[k] == 0 && comp1[k] >= maxx/2.8284) testclust[k] = 7
			if (testclust[k] == 0 && comp1[k] >= maxx/4.0) testclust[k] = 6 
			if (testclust[k] == 0 && comp1[k] >= maxx/5.6569) testclust[k] = 5
			if (testclust[k] == 0 && comp1[k] >= maxx/8.0) testclust[k] = 4
			if (testclust[k] == 0 && comp1[k] >= maxx/11.3137) testclust[k] = 3 
			if (testclust[k] == 0 && comp1[k] >= maxx/16.0) testclust[k] = 2
			if (testclust[k] == 0 && comp1[k] >= maxx/22.6274) testclust[k] = 1
		}
	}

	# break first quadrant into ten clusters
	for (k in 1:dim(testclust)[1]) {
		if (comp1[k] != 999) {
			if (testclust[k] == 10 && comp1[k] < minx/1.4142) testclust[k] = 19 
			if (testclust[k] == 10 && comp1[k] < minx/2.0) testclust[k] = 18
			if (testclust[k] == 10 && comp1[k] < minx/2.8284) testclust[k] = 17
			if (testclust[k] == 10 && comp1[k] < minx/4.0) testclust[k] = 16 
			if (testclust[k] == 10 && comp1[k] < minx/5.6569) testclust[k] = 15
			if (testclust[k] == 10 && comp1[k] < minx/8.0) testclust[k] = 14
			if (testclust[k] == 10 && comp1[k] < minx/11.3137) testclust[k] = 13 
			if (testclust[k] == 10 && comp1[k] < minx/16.0) testclust[k] = 12
			if (testclust[k] == 10 && comp1[k] < minx/22.6274) testclust[k] = 11
		}
	}
		
		
	################# Use the same elements of guage set to cluster test data ##########################
	testgauge2 = test2[,thisGauge]
	
	comp1 = as.matrix(testgauge2)
	
		# get dimensions of projection
	maxx = max(comp1)
	minx = min(comp1)
	
	# matrix used to store cluster assignments
	testclust2 = as.matrix(comp1)
	testclust2[] = -1
	
	# create basic clusters
	for (k in 1:dim(testclust2)[1]) {
		if (comp1[k] != 999) {
			if (comp1[k] >= 0) testclust2[k] = 0 
			if (comp1[k] < 0) testclust2[k] = 10
		}
	}

	# break first quadrant into ten clusters
	for (k in 1:dim(testclust2)[1]) {
		if (comp1[k] != 999) {
			if (testclust2[k] == 0 && comp1[k] >= maxx/1.4142) testclust2[k] = 9 
			if (testclust2[k] == 0 && comp1[k] >= maxx/2.0) testclust2[k] = 8
			if (testclust2[k] == 0 && comp1[k] >= maxx/2.8284) testclust2[k] = 7
			if (testclust2[k] == 0 && comp1[k] >= maxx/4.0) testclust2[k] = 6 
			if (testclust2[k] == 0 && comp1[k] >= maxx/5.6569) testclust2[k] = 5
			if (testclust2[k] == 0 && comp1[k] >= maxx/8.0) testclust2[k] = 4
			if (testclust2[k] == 0 && comp1[k] >= maxx/11.3137) testclust2[k] = 3 
			if (testclust2[k] == 0 && comp1[k] >= maxx/16.0) testclust2[k] = 2
			if (testclust2[k] == 0 && comp1[k] >= maxx/22.6274) testclust2[k] = 1
		}
	}

	# break first quadrant into ten clusters
	for (k in 1:dim(testclust2)[1]) {
		if (comp1[k] != 999) {
			if (testclust2[k] == 10 && comp1[k] < minx/1.4142) testclust2[k] = 19 
			if (testclust2[k] == 10 && comp1[k] < minx/2.0) testclust2[k] = 18
			if (testclust2[k] == 10 && comp1[k] < minx/2.8284) testclust2[k] = 17
			if (testclust2[k] == 10 && comp1[k] < minx/4.0) testclust2[k] = 16 
			if (testclust2[k] == 10 && comp1[k] < minx/5.6569) testclust2[k] = 15
			if (testclust2[k] == 10 && comp1[k] < minx/8.0) testclust2[k] = 14
			if (testclust2[k] == 10 && comp1[k] < minx/11.3137) testclust2[k] = 13 
			if (testclust2[k] == 10 && comp1[k] < minx/16.0) testclust2[k] = 12
			if (testclust2[k] == 10 && comp1[k] < minx/22.6274) testclust2[k] = 11
		}
	}
		
		

	###################### Use train clusters to make predictions for test clusters #####################

	# generate matrix of predictions
	train[train == 999] = NA
	trainout = train[,notgauge]
	testout = test[,notgauge]
	test2out = test2[,notgauge]

	pred = matrix(nrow = 20, ncol = dim(trainout)[2])
	for (k in 1:dim(trainout)[2]) {
		for (l in 0:19) {
			pred[l+1, k] = mean(trainout[clust == l, k], na.rm = TRUE) 
		}
	}
	head(pred) 

	# generate matrix of differences between predictions and realizations
	val = testout
	for (k in 1:dim(testout)[2]) {
		for (l in 0:19) {
			val[testclust == l, k] = val[testclust == l, k] - pred[l+1, k] 
		}
	}
	
	# differences for second test set
	val2 = test2out
	for (k in 1:dim(test2out)[2]) {
		for (l in 0:19) {
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

	allNMAE[i,1] = nmae
	
	if (nmae < bestNMAE) {
		bestGauge = thisGauge
		bestNMAE = nmae
		bestNMAEout = nmae2
	}
	
	train[!is.finite(train)] = 999
}
t(allNMAE)
bestNMAE
bestGauge
bestNMAEout