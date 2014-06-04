<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php require_once("../includes/generalfunctions.php") ?>
<?php
$sqldb = "jester4and5copy"; //Set this to a different database (not "jester") for testing

define("BOTTOMLEFT", 0);
define("BOTTOMRIGHT", 1);
define("TOPLEFT", 2);
define("TOPRIGHT", 3);

/* Remove this if the new jokes/ratings are used */
$removedjokes = array();
$predictjokes = array(5, 7, 8, 13, 15, 16, 17, 18, 19, 20);
$numjokes = 100;

$ispredictor = array();

for ($i = 0; $i < $numjokes; $i++)
	$ispredictor[$i] = false;
	
for ($i = 0; $i < count($predictjokes); $i++)
	$ispredictor[$predictjokes[$i] - 1] = true;
/* End */

ini_set("memory_limit","10000M");

openConnection();
print("Running Jester Data Exploration\n\n");
setJester4TableNames($connection);
$euclideandistancepercentages = array();
$pearsondistancepercentages = array();
distancesFromAverageJokeRatings($connection, $euclideandistancepercentages, $pearsondistancepercentages);
plotClusters($connection, $euclideandistancepercentages, $pearsondistancepercentages);
meanRatingsStdDevWithinClusters($connection);
print("\nJester Data Exploration Complete\n");
mysql_close($connection);
?>
<?php
function getClusterPoints(&$clusterpointsx, &$clusterpointsy, &$clustercentersx, &$clustercentersy, &$layer, $level, $line, $orientation, $minx, $maxx, $miny, $maxy)
{
	global $clusterlevels;
	
	if ($level == $clusterlevels)
	{
		$clusterpointsx[$cluster][BOTTOMLEFT] = $minx;
		$clusterpointsy[$cluster][BOTTOMLEFT] = $miny;
		
		$clusterpointsx[$cluster][BOTTOMRIGHT] = $maxx;
		$clusterpointsy[$cluster][BOTTOMRIGHT] = $miny;
		
		$clusterpointsx[$cluster][TOPLEFT] = $minx;
		$clusterpointsy[$cluster][TOPLEFT] = $maxy;
		
		$clusterpointsx[$cluster][TOPRIGHT] = $maxx;
		$clusterpointsy[$cluster][TOPRIGHT] = $maxy;
		
		$clustercentersx[$cluster] = ($minx + $maxx) / 2;
		$clustercentersy[$cluster] = ($miny + $maxy) / 2;
		
		return;
	}
		
	if ($orientation == 0)
	{
		$xpos = $layer[$line];

		//Left
		getClusterPoints($clusterpointsx, $clusterpointsy, $clustercentersx, $clustercentersy, ($cluster . "0"), $layer, ($level + 1), ((2 * ($line + 1)) - 1), flip($orientation), $minx, $xpos, $miny, $maxy);
		
		//Right
		getClusterPoints($clusterpointsx, $clusterpointsy, $clustercentersx, $clustercentersy, ($cluster . "1"), $layer, ($level + 1), (2 * ($line + 1)), flip($orientation), $xpos, $maxx, $miny, $maxy);
	}
	else
	{
		$ypos = $layer[$line];

		//Left (Below)
		getClusterPoints($clusterpointsx, $clusterpointsy, $clustercentersx, $clustercentersy, ($cluster . "0"), $layer, ($level + 1), ((2 * ($line + 1)) - 1), flip($orientation), $minx, $maxx, $miny, $ypos);
		
		//Right (Above)
		getClusterPoints($clusterpointsx, $clusterpointsy, $clustercentersx, $clustercentersy, ($cluster . "1"), $layer, ($level + 1), (2 * ($line + 1)), flip($orientation), $minx, $maxx, $ypos, $maxy);
	}
	
	return;
}

function draw3DSurface(&$gnubargraphcode, &$clusterpointsx, &$clusterpointsy, &$clustercentersx, &$clustercentersy)
{
	foreach ($clustercentersx as $cluster => $centerx)
	{
		$centerx = $clustercentersx[$cluster];
		$centery = $clustercentersy[$cluster];
		
		$bottomleftx = $clusterpointsx[$cluster][BOTTOMLEFT];
		$bottomlefty = $clusterpointsy[$cluster][BOTTOMLEFT];
		
		$bottomrightx = $clusterpointsx[$cluster][BOTTOMRIGHT];
		$bottomrighty = $clusterpointsy[$cluster][BOTTOMRIGHT];
		
		$topleftx = $clusterpointsx[$cluster][TOPLEFT];
		$toplefty = $clusterpointsy[$cluster][TOPLEFT];
		
		$toprightx = $clusterpointsx[$cluster][TOPRIGHT];
		$toprighty = $clusterpointsy[$cluster][TOPRIGHT];
		
		$gnulinedrawcode .= "set arrow from $xpos, $miny to $xpos, $maxy nohead lt -1 lw 1.2\n";
	}
}

/*
function drawLines(&$gnulinedrawcode, &$clustercentersx, &$clustercentersy, $cluster, &$layer, $level, $line, $orientation, $minx, $maxx, $miny, $maxy)
{
	global $clusterlevels;
	
	if ($level == $clusterlevels)
	{
		$clustercentersx[$cluster] = ($minx + $maxx) / 2;
		$clustercentersy[$cluster] = ($miny + $maxy) / 2;
		
		return;
	}
		
	if ($orientation == 0)
	{
		$xpos = $layer[$line];
		$gnulinedrawcode .= "set arrow from $xpos, $miny to $xpos, $maxy nohead lt -1 lw 1.2\n";
		
		//Left
		drawLines($gnulinedrawcode, $clustercentersx, $clustercentersy, ($cluster . "0"), $layer, ($level + 1), ((2 * ($line + 1)) - 1), flip($orientation), $minx, $xpos, $miny, $maxy);
		
		//Right
		drawLines($gnulinedrawcode, $clustercentersx, $clustercentersy, ($cluster . "1"), $layer, ($level + 1), (2 * ($line + 1)), flip($orientation), $xpos, $maxx, $miny, $maxy);
	}
	else
	{
		$ypos = $layer[$line];
		$gnulinedrawcode .= "set arrow from $minx, $ypos to $maxx, $ypos nohead lt -1 lw 1.2\n";
		
		//Left (Below)
		drawLines($gnulinedrawcode, $clustercentersx, $clustercentersy, ($cluster . "0"), $layer, ($level + 1), ((2 * ($line + 1)) - 1), flip($orientation), $minx, $maxx, $miny, $ypos);
		
		//Right (Above)
		drawLines($gnulinedrawcode, $clustercentersx, $clustercentersy, ($cluster . "1"), $layer, ($level + 1), (2 * ($line + 1)), flip($orientation), $minx, $maxx, $ypos, $maxy);
	}
	
	return;
}
*/

function getMinProjectionValue($connection, $axis)
{
	global $tablenames;
	
	$query = "SELECT MIN(projectionvalue) FROM " . $tablenames["PROJECTION"] . " WHERE axis={$axis}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	$row = mysql_fetch_row($result);
	$minprojectionvalue = $row[0];
	
	return $minprojectionvalue;
}

function getMaxProjectionValue($connection, $axis)
{
	global $tablenames;
	
	$query = "SELECT MAX(projectionvalue) FROM " . $tablenames["PROJECTION"] . " WHERE axis={$axis}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	$row = mysql_fetch_row($result);
	$maxprojectionvalue = $row[0];
	
	return $maxprojectionvalue;
}

function plotClusters($connection, &$euclideandistancepercentages, &$pearsondistancepercentages)
{
	global $tablenames;
	
	$plotname = "userplot";
	
	$layer = array();
	
	$query = "SELECT DISTINCT layerindex, layervalue FROM " . $tablenames["LAYER"];
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$layerindex = $row[0];
		$layervalue = $row[1];
		
		$layer[$layerindex] = $layervalue;
	}
	
	$gnulinedrawcode = "";
	$clustercentersx = array();
	$clustercentersy = array();
	$cluster = "";
	$initiallevel = 0;
	$initialline = 0;
	$initialorientation = 0;
	$minx = getMinProjectionValue($connection, 0);
	$maxx = getMaxProjectionValue($connection, 0);
	$miny = getMinProjectionValue($connection, 1);
	$maxy = getMaxProjectionValue($connection, 1);
	drawLines($gnulinedrawcode, $clustercentersx, $clustercentersy, $cluster, $layer, $initiallevel, $initialline, $initialorientation, $minx, $maxx, $miny, $maxy);
	
	$plotcontents = "";
	$gnulabelcode = "";
	
	$query = "SELECT DISTINCT cluster FROM " . $tablenames["CLUSTERS"];
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$cluster = $row[0];
		
		$centerx = $clustercentersx[$cluster];
		$centery = $clustercentersy[$cluster];
		
		$gnulabelcode .= "set label \"$cluster\\n(euc%: " . sprintf("%.2f", $euclideandistancepercentages[$cluster]) . "%,\\npea%: " . sprintf("%.2f", $pearsondistancepercentages[$cluster]) . "%)\" at first $centerx, first $centery font \"Courier New Bold, 8\"\n";
		
		$query = "SELECT DISTINCT userid FROM " . $tablenames["PROJECTION"] . " WHERE userid IN (SELECT userid FROM " . $tablenames["CLUSTERS"] . " WHERE cluster='{$cluster}')";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$userid = $row[0];
		
			$projection = array();
		
			$query = "SELECT axis, projectionvalue FROM " . $tablenames["PROJECTION"] . " WHERE userid={$userid}";
			$resultinnerinner = mysql_query($query, $connection);
			if (!$resultinnerinner)
		   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

			while ($row = mysql_fetch_array($resultinnerinner))
			{
				$axis = $row[0];
				$projectionvalue = $row[1];
			
				$plotcontents .= "$projectionvalue ";
			}
		
			$plotcontents .= "\n";
		}
	}
	
	writeFile(($plotname  . ".dat"), $plotcontents);
	$gnuplotcode = $gnulinedrawcode . $gnulabelcode . "plot \"" . $plotname . ".dat\" with dots";
	
	writeFile(("gnuplotcode" . ".dat"), $gnuplotcode);
	
	print("Completed: Generating Projection Plot\n");
}

/*
function plotProjections($connection)
{
	global $tablenames;
	
	$matchcount = 0;
	
	$layer = array();
	
	$query = "SELECT DISTINCT layerindex, layervalue FROM " . $tablenames["LAYER"];
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$layerindex = $row[0];
		$layervalue = $row[1];
		
		$layer[$layerindex] = $layervalue;
	}
	
	$gnuplotcode = "plot ";
	
	$query = "SELECT DISTINCT cluster FROM " . $tablenames["CLUSTERS"];
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$cluster = $row[0];
		
		$plotcontents = "";
		$minvalues = array();
		$maxvalues = array();
		$minvalues[0] = 1000000;
		$maxvalues[0] = -1000000;
		$minvalues[1] = 1000000;
		$maxvalues[1] = -1000000;
		
		$query = "SELECT DISTINCT userid FROM " . $tablenames["PROJECTION"] . " WHERE userid IN (SELECT userid FROM " . $tablenames["CLUSTERS"] . " WHERE cluster='{$cluster}')";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$userid = $row[0];
		
			$projection = array();
		
			$query = "SELECT axis, projectionvalue FROM " . $tablenames["PROJECTION"] . " WHERE userid={$userid}";
			$resultinnerinner = mysql_query($query, $connection);
			if (!$resultinnerinner)
		   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

			while ($row = mysql_fetch_array($resultinnerinner))
			{
				$axis = $row[0];
				$projectionvalue = $row[1];

				if ($projectionvalue < $minvalues[$axis])
					$minvalues[$axis] = $projectionvalue;
				
				if ($projectionvalue > $maxvalues[$axis])
					$maxvalues[$axis] = $projectionvalue;
			
				$plotcontents .= "$projectionvalue ";
			}
		
			$plotcontents .= "\n";
		}
		
		$minvalues0index = matchingIndex($layer, $minvalues[0], $matchcount);
		$maxvalues0index = matchingIndex($layer, $maxvalues[0], $matchcount);
		$minvalues1index = matchingIndex($layer, $minvalues[1], $matchcount);
		$maxvalues1index = matchingIndex($layer, $maxvalues[1], $matchcount);
	
		print("cluster " . $cluster . ": axis0 = [" . $minvalues[0] . " (" . $minvalues0index . "), " . $maxvalues[0] . " (" . $maxvalues0index . ")]; axis1 = [" . $minvalues[1] . " (" . $minvalues1index . "), " . $maxvalues[1] . " (" . $maxvalues1index . ")]\n");
	
		$plotname = "projections_" . $cluster;
		plot($plotname, $plotcontents);
		$gnuplotcode .= "\"" . $plotname . ".dat\" with dots,\\\n";
	}
	
	print("\nmatch count: " . $matchcount . "\n\n");
	
	$trimmedgnuplotcode = substr($gnuplotcode, 0, (strlen($gnuplotcode) - 3));
	print("gnuplot code:\n\n" . $trimmedgnuplotcode . "\n");
	
	print("\nCompleted: Generating Projection Plot\n");
}
*/

function writeFile($filename, &$contents)
{
	$fullfilename = "explorationdata/" . $filename;

	if (!$handle = fopen($fullfilename, 'w'))
	{
		echo "Cannot open file ($fullfilename)";
		exit;
	}

	if (fwrite($handle, $contents) === false)
	{
		echo "Cannot write to file ($fullfilename)";
		exit;
	}

	fclose($handle);
}

function distancesFromAverageJokeRatings($connection, &$euclideandistancepercentages, &$pearsondistancepercentages)
{	
	global $tablenames;
	
	$jokeratingmeans = array();
	averageJokeRatings($connection, $jokeratingmeans);
	
	$euclideandistances = array();
	$pearsondistances = array();
	
	$query = "SELECT DISTINCT cluster FROM " . $tablenames["CLUSTERS"];
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$clustermeanratings = array();
		
		$query = "SELECT jokeid, meanrating FROM " . $tablenames["CLUSTERMEANS"] . " WHERE cluster={$cluster}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$jokeid = $row[0];
			$meanrating = $row[1];
			
			$clustermeanratings[$jokeid] = $meanrating;
		}
		
		$euclideandistances[$cluster] = euclideanDistance($clustermeanratings, $jokeratingmeans);
		$pearsondistances[$cluster] = pearsonDistance($clustermeanratings, $jokeratingmeans);
	}
	
	asort($euclideandistances);
	asort($pearsondistances);
	
	$totaleuclideandistance = totalDistance($euclideandistances);
	$totalpearsondistance = totalDistance($pearsondistances);
	
	print "Average Joke Ratings:\n\n";
	foreach ($jokeratingmeans as $jokeid => $jokeratingmean)
	{
		print "jokeratingmeans[$jokeid] = " . $jokeratingmean . "\n";
	}
	
	print "\nEuclidean Distances From Average Joke Ratings:\n\n";
	foreach ($euclideandistances as $cluster => $distance)
	{
		$percentoftotal = ($distance / $totaleuclideandistance) * 100;
		$euclideandistancepercentages[$cluster] = $percentoftotal;
		print "euclideandistances[$cluster] = " . $distance. " (" . sprintf("%.2f", $percentoftotal) . "%)\n";
	}
	
	print "\nPearson Distances From Average Joke Ratings:\n\n";
	foreach ($pearsondistances as $cluster => $distance)
	{
		$percentoftotal = ($distance / $totalpearsondistance) * 100;
		$pearsondistancepercentages[$cluster] = $percentoftotal;
		print "pearsondistances[$cluster] = " . $distance. " (" . sprintf("%.2f", $percentoftotal) . "%)\n";
	}
	
	print("\nCompleted: Calculating Distances From Average Joke Ratings\n");
}

function totalDistance(&$distances)
{
	$sum = 0;
	
	foreach ($distances as $distance)
	{
		$sum += $distance;
	}
	
	return $sum;
}

/*
function averageJokeRatings($connection, &$jokeratingmeans)
{
	global $numjokes, $tablenames;
	
	$jokeratingsums = array();
	$jokeratingcounts = array();
	
	for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
	{
		$jokeid = $jokeindex + 1;
		
		if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes
			continue;
			
		$jokeratingsums[$jokeid] = 0;
		$jokeratingcounts[$jokeid] = 0;
		$jokeratingmeans[$jokeid] = 0;
		
		$query = "SELECT cluster, meanrating FROM " . $tablenames["CLUSTERMEANS"] . " WHERE jokeid={$jokeid}";
		$result = mysql_query($query, $connection);
		if (!$result)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($result))
		{
			$cluster = $row[0];
			$meanrating = $row[1];
			
			$jokeratingsums[$jokeid] += $meanrating;
			$jokeratingcounts[$jokeid]++;
		}
		
		$jokeratingmeans[$jokeid] = $jokeratingsums[$jokeid] / $jokeratingcounts[$jokeid];
	}
}
*/

function averageJokeRatings($connection, &$jokeratingmeans)
{
	global $numjokes, $tablenames;
	
	$jokeratingsums = array();
	$jokeratingcounts = array();
	
	for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
	{
		$jokeid = $jokeindex + 1;
		
		if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes
			continue;
			
		$jokeratingsums[$jokeid] = 0;
		$jokeratingcounts[$jokeid] = 0;
		$jokeratingmeans[$jokeid] = 0;
	
		$query = "SELECT jokerating FROM " . $tablenames["RATINGS"] . " WHERE jokeid={$jokeid}";
		$result = mysql_query($query, $connection);
		if (!$result)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($result))
		{
			$jokerating = $row[0];
			
			$jokeratingsums[$jokeid] += $jokerating;
			$jokeratingcounts[$jokeid]++;
		}
		
		$jokeratingmeans[$jokeid] = $jokeratingsums[$jokeid] / $jokeratingcounts[$jokeid];
	}
}

function euclideanDistance(&$vector1, &$vector2)
{
	$sum = 0;
	
	foreach ($vector1 as $key => $vector1value)
	{
		if (array_key_exists($key, $vector1) && array_key_exists($key, $vector2))
		{
			$diff = $vector1[$key] - $vector2[$key];
			$sum += ($diff * $diff);
		}
		else
			exit("Cannot find the Euclidean distance of two arrays with different keys.");
	}
	
	return $sum;
}

function pearsonDistance(&$vector1, &$vector2)
{
	$vector1mean = mean($vector1);
	$vector2mean = mean($vector2);
	
	$num = 0;
	$norm1 = 0;
	$norm2 = 0;
	$denom = 0;
	
	foreach ($vector1 as $key => $vector1value)
	{
		if (array_key_exists($key, $vector1) && array_key_exists($key, $vector2))
		{
			$num += (($vector1[$key] - $vector1mean) * ($vector2[$key] - $vector2mean));
			$norm1 += (($vector1[$key] - $vector1mean) * ($vector1[$key] - $vector1mean));
			$norm2 += (($vector2[$key] - $vector2mean) * ($vector2[$key] - $vector2mean));
		}
		else
			exit("Cannot find the Pearson distance of two arrays with different keys.");
	}
	
	$denom = sqrt($norm1) * sqrt($norm2);
	
	if ($denom != 0)
		$correl = $num / $denom;
	else
		exit("Pearson denominator is 0.");

	$distance = 1 - $correl;
	
	return $distance;
}

function mean(&$vector)
{
	$sum = 0;
	$count = 0;
	
	foreach ($vector as $key => $vectorvalue)
	{
		$sum += $vector[$key];
		$count++;
	}
	
	$mean = $sum / $count;
	
	return $mean;
}

//Calculate each cluster's internal standard deviation over mean ratings
function meanRatingsStdDevWithinClusters($connection)
{
	global $tablenames;
	
	$stddevs = array();
	
	$query = "SELECT DISTINCT cluster FROM " . $tablenames["CLUSTERS"];
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$clustermeans = array();
		$meanratingsum = 0;
		$meanratingcount = 0;
		$meanratingaverage = 0;
	
		$query = "SELECT jokeid, meanrating FROM " . $tablenames["CLUSTERMEANS"] . " WHERE cluster={$cluster}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$jokeid = $row[0];
			$meanrating = $row[1];
		
			$clustermeans[$jokeid] = $meanrating;
			$meanratingsum += $meanrating;
			$meanratingcount++;
		}
	
		$meanratingaverage = $meanratingsum / $meanratingcount;
	
		$sum = 0;
		$count = 0;
	
		foreach ($clustermeans as $jokeid => $meanrating)
		{
			$diff = $meanrating - $meanratingaverage;
			$diffsquared = $diff * $diff;
		
			$sum += $diffsquared;
			$count++;
		}
	
		$variance = $sum / $count;
		$stddev = sqrt($variance);
		
		$stddevs[$cluster] = $stddev;
	}
	
	print "\nStandard Deviations Within Clusters Over Average Joke Ratings:\n\n";
	foreach ($stddevs as $cluster => $stddev)
	{
		print "stddevs[$cluster] = " . sprintf("%.2f", $stddev) . "\n";
	}
}

function unitTest($connection)
{	
}
?>