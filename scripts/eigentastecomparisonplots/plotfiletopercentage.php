<?php
plotFileToPercentage("diff_pte5plot_pte2plot.dat");

function plotFileToPercentage($plotfilename)
{
	ini_set("memory_limit","10000M");
	
	$linedelimiter = "\n";
	$valuedelimiter = " ";

	print "File: $plotfilename\n";
	$plotfile = fopen($plotfilename, "r");
	$contents = fread($plotfile, filesize($plotfilename));
	fclose ($plotfile);
	
	$plotarray = array();
	
	$lines = array();
	$lines = explode($linedelimiter, $contents);
	
	foreach ($lines as $lineindex => $line)
	{
		$values = array();
		$values = explode($valuedelimiter, $line);
		
		if (count($values) == 2)
		{
			$recommendedjokenum = $values[0];
			$plotarray[$recommendedjokenum] = ($values[1] * 100) / 20;
		}
	}
	
	$filename = $plotfilename . "_percentages.dat";

	if (!$handle = fopen($filename, 'w'))
	{
		echo "Cannot open file ($filename)";
		exit;
	}

	$contents = getPlotString($plotarray);

	if (fwrite($handle, $contents) === false)
	{
		echo "Cannot write to file ($filename)";
		exit;
	}

	fclose($handle);
	
	print "\n";
}

function getPlotString(&$plotarray)
{
	$plotstring = "";
	
	foreach ($plotarray as $recommendedjokenum => $plotelement)
	{
		if (!($plotelement === false))
		{
			$plotstring .= "$recommendedjokenum $plotelement\n";
		}
	}
	
	return $plotstring;
}
?>