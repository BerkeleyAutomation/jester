setwd('~/Documents/Workspace/Eigentaste/')

library('ggplot2') # need this to export histograms

jester = read.csv('jester1.csv') # Reading data
jester[,1] = NULL # Drop first column

colnames(jester) = paste('JOKE', 1:100) # Rename columns

jester[jester == 99] = NA # Set missing values to NA

for (i in 1:100) { # iterating through all the jokes
  joke_data = jester[, i] # isolate joke data
  xlab = paste('Ratings for JOKE', i)
  plot = qplot(joke_data, xlab=xlab, ylab='# of users') # generate a histogram
  filename = paste('histograms/JOKE', i, '.png') # File name = JOKEi.png
  ggsave(filename=filename, plot=plot)
}
