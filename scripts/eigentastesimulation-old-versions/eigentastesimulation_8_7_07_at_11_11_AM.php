<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php
//Make sure that this is the correct file, and no functions are automatically running
require_once("vectorscriptsbugfix.php")
?>
<?php
$sqldb = "jestercopy"; //Set this to a different database (not "jester") for testing

/* Remove this if the new jokes/ratings are used */
$removedjokes = array();
$predictjokes = array(5, 7, 8, 13, 15, 16, 17, 18, 19, 20);
$numjokes = 100;

for ($i = 0; $i < $numjokes; $i++)
	$ispredictor[$i] = false;
	
for ($i = 0; $i < count($predictjokes); $i++)
	$ispredictor[$predictjokes[$i] - 1] = true;
	
define("NUMJOKES", 100);
define("NUMPREDICTJOKES", 10);
define("NUMNONPREDICTJOKES", 90);
/* End */

//Note: No joke seeding is used in this simulation. All seeding is bypassed.

ini_set("memory_limit","10000M");

openConnection();
print("Running Simulation\n\n");
unitTest($connection);
/*
//groupUsers($connection);
//markRatedPredictJokes($connection);

//editedCorrel($connection);
//genLayer($connection); // Tested - works
//genClusters($connection); // Tested - works
//genPredictVec($connection); /// Tested - works (at least on 36,705 initialgroup 1 users)

//exportMATLABData($connection);
//exportMATLABDataAllUsers($connection);
//importMATLABResults($connection);
//importMATLABResultsAllUsers($connection);

runSimulation($connection);
*/
print("\nSimulation Complete\n");
mysql_close($connection);
?>
<?php
function runSimulation($connection)
{
	$numjokestoratearray = array();

	for ($i = 1; $i <= NUMNONPREDICTJOKES; $i++)
	{
		$numjokestoratearray[] = $i;
	}

	$meanweight = 5;

	$plotarray = array();
	$plotnamearray = array();

	$xaxisplot = array();
	foreach ($numjokestoratearray as $numjokestorate)
	{
		$xaxisplot[] = $numjokestorate;
	}
	$xaxisplotname = "xaxisplot";

	$e2plot = array();
	$e2plotname = "e2plot";
	$e3plot = array();
	$e3plotname = "e3plot";
	$prede2plot = array();
	$prede2plotname = "prede2plot";
	$prede3plot = array();
	$prede3plotname = "prede3plot";

	$pte2plot = array();
	$pte2plotname = "pte2plot";
	$pte3plot = array();
	$pte3plotname = "pte3plot";
	$ptprede2plot = array();
	$ptprede2plotname = "ptprede2plot";
	$ptprede3plot = array();
	$ptprede3plotname = "ptprede3plot";

	$se2plot = array();
	$se2plotname = "se2plot";
	$se3plot = array();
	$se3plotname = "se3plot";
	$sprede2plot = array();
	$sprede2plotname = "sprede2plot";
	$sprede3plot = array();
	$sprede3plotname = "sprede3plot";

	runSimulationUsersRatedX($connection, $meanweight, $numjokestoratearray, $e2plot, $prede2plot, $pte2plot, $ptprede2plot, $e3plot, $prede3plot, $pte3plot, $ptprede3plot, $se2plot, $sprede2plot, $se3plot, $sprede3plot);

	$plotarray[] = getPlotString($xaxisplot);
	$plotnamearray[] = $xaxisplotname;

	$plotarray[] = getPlotString($e2plot);
	$plotnamearray[] = $e2plotname;
	$plotarray[] = getPlotString($e3plot);
	$plotnamearray[] = $e3plotname;
	$plotarray[] = getPlotString($prede2plot);
	$plotnamearray[] = $prede2plotname;
	$plotarray[] = getPlotString($prede3plot);
	$plotnamearray[] = $prede3plotname;

	$plotarray[] = getPlotString($pte2plot);
	$plotnamearray[] = $pte2plotname;
	$plotarray[] = getPlotString($pte3plot);
	$plotnamearray[] = $pte3plotname;
	$plotarray[] = getPlotString($ptprede2plot);
	$plotnamearray[] = $ptprede2plotname;
	$plotarray[] = getPlotString($ptprede3plot);
	$plotnamearray[] = $ptprede3plotname;

	$plotarray[] = getPlotString($se2plot);
	$plotnamearray[] = $se2plotname;
	$plotarray[] = getPlotString($se3plot);
	$plotnamearray[] = $se3plotname;
	$plotarray[] = getPlotString($sprede2plot);
	$plotnamearray[] = $sprede2plotname;
	$plotarray[] = getPlotString($sprede3plot);
	$plotnamearray[] = $sprede3plotname;

	foreach ($plotarray as $key => $plot)
	{
		$plotname = $plotnamearray[$key];
		$filename = "plots/" . $plotname . ".dat";
	
		if (!$handle = fopen($filename, 'w'))
		{
			echo "Cannot open file ($filename)";
			exit;
		}
	
		$contents = $plot;
	
		if (fwrite($handle, $contents) === false)
		{
			echo "Cannot write to file ($filename)";
			exit;
		}
	
		fclose($handle);
	}
	
	print("Completed: Running Simulation\n");
}

/* Tested - works */
function groupUsers($connection)
{
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];
		$ininitialusergroup = rand(0, 1);
			
		$query = "UPDATE users SET ininitialusergroup={$ininitialusergroup} WHERE userid={$userid}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	print("Completed: Group Users\n");
}

/* Tested - works (at least on the 73,421 old users) */
function markRatedPredictJokes($connection)
{
	global $predictjokes;
	
	$query = "SELECT userid, numrated FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$userid = $row[0];
		$numrated = $row[1];
		
		if ($numrated < count($predictjokes))
		{
			$ratedpredictjokes = 0;
			$ratedpredictjokesandmore = 0;
		}
		else
		{
			$ratedpredictjokes = 1;
			$ratedpredictjokesandmore = 1;
		
			$jokeidarray = array();
		
			$query = "SELECT jokeid FROM ratings WHERE userid={$userid}";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

			while ($row = mysql_fetch_array($resultinner))
			{
				$jokeid = $row[0];
				$jokeidarray[] = $jokeid;
			}
		
			foreach ($predictjokes as $predictjokeid)
			{
				if (!in_array($predictjokeid, $jokeidarray))
				{
					$ratedpredictjokes = 0;
					$ratedpredictjokesandmore = 0;
					break;
				}
			}
		
			if (($ratedpredictjokes == 1) && $numrated <= count($predictjokes))
				$ratedpredictjokesandmore = 0;
		}
		
		$query = "UPDATE users SET ratedpredictjokes={$ratedpredictjokes}, ratedpredictjokesandmore={$ratedpredictjokesandmore} WHERE userid={$userid}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	print("Completed: Mark Rated Prediction Jokes\n");
}

//Tested - works
function editedCorrel($connection)
{
	global $covariance, $eigenvecs, $meanvector, $countvector, $covnormalizer, $correlnormx, $correlnormy, $eigenvals;
	global $numjokes, $predictjokes, $threshold, $ispredictor, $eigenaxes;
	global $projections, $projectionsforclustering, $useridsforprojections;
	
	//Initialize all the arrays that will be used
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		$meanvector[$i] = 0.0; //The mean rating for each joke $i (from users who qualify)
		$countvector[$i] = 0; //The number of ratings for each joke $i (from users who qualify)
	}
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			$covariance[$i][$j] = 0.0;
			$eigenvecs[$i][$j] = 0.0;
			$covnormalizer[$i][$j] = 0;
			$correlnormx[$i][$j] = 0.0;
			$correlnormy[$i][$j] = 0.0;
		}
	}
	
	$query = "SELECT numrated, userid, ratedpredictjokesandmore FROM users WHERE ininitialusergroup=1";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$numrated = $row[0];
		$userid = $row[1];
		$ratedpredictjokesandmore = $row[2];
		
		//Skip user if an error occurs
		
		if ($numrated > $numjokes)
		{
			print($_POST["presystemerror"] . "User " . $userid . " has rated " . $numrated . " jokes." . $_POST["postsystemerror"]);
			continue;
		}
		
		//Skip (do not cluster) user if he/she has not rated all the prediction jokes (and at least one more joke, too)
		//*** Important Note: If the user is not skipped, his/her ratings will factor into the meanvector!
		
		if ($ratedpredictjokesandmore == 0)
		{
			continue;
		}
		
		//Process user ratings
		
		$query = "SELECT jokeid, jokerating FROM ratings WHERE userid={$userid}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
		while ($row = mysql_fetch_array($resultinner))
		{
			$jokeid = $row[0];
			$jokerating = $row[1];
			$jokeindex = $jokeid - 1;
			
			$meanvector[$jokeindex] += $jokerating;
			$countvector[$jokeindex]++;
		}
	}

	//Compile the statistics
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		if ($countvector[$i] != 0)
			$meanvector[$i] = $meanvector[$i] / $countvector[$i];
		//else $meanvector[$i] = 0.0 (neutral rating, because no one has rated it yet)
	}
	
	//Calculate covariance
	
	clearTable($connection, "usercovariances");
	clearTable($connection, "pairs");
	clearTable($connection, "covariance");

	editedCalcCovariance($connection);
	
	//Normalize covariance matrix and store it, along with the pairs
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			if ((sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j])) != 0)
				$covariance[$i][$j] = $covariance[$i][$j] / (sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j]));
			else //One or both of the jokes was not rated, so $correlnormx[$i][$j] and/or $correlnormy[$i][$j] are equal to zero
				$covariance[$i][$j] = 0; //So, if one or both of the jokes was not rated, there is no correlation
			
			$query = "INSERT INTO covariance (row, col, covariance) VALUES ({$i}, {$j}, {$covariance[$i][$j]})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	//Calculate eigenvectors and eigenvalues
	
	jacobi(count($predictjokes));
	eigSrt(count($predictjokes));
	
	//Store eigenvectors and eigenvalues
	
	clearTable($connection, "eigenvalues");
	clearTable($connection, "eigenvectors");
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		$query = "INSERT INTO eigenvalues (eigenvalueindex, eigenvalue) VALUES ({$i}, {$eigenvals[$i]})";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			$query = "INSERT INTO eigenvectors (row, col, eigenvectorelement) VALUES ({$i}, {$j}, {$eigenvecs[$i][$j]})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	//Calculate and store projections
	
	clearTable($connection, "projection");
	
	editedCalcProjection($connection, $eigenaxes);
	
	print("Completed: Correlation\n");
}

/* Tested - works */
//Calculate the global covariance matrix and normalization matrices
function editedCalcCovariance($connection)
{
	global $predictjokes, $meanvector, $covariance, $covnormalizer, $correlnormx, $correlnormy;
	
	$query = "SELECT userid FROM users WHERE ininitialusergroup=1 AND ratedpredictjokesandmore=1"; //If there are not enough ratings for this user (if he/she has not rated all the prediction set jokes and at least another one), do not try to calculate covariance
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];
		
		for ($i = 0; $i < count($predictjokes); $i++)
		{
			$jokeid = $predictjokes[$i];
			
			if (isRated($connection, $userid, $jokeid)) //The user may not have necessarily rated every prediction joke 
			{											//as new ones may have been added
				$jokerating = getJokeRating($connection, $userid, $jokeid);
				
				$ratingvect[$i] = $jokerating - $meanvector[$jokeid - 1];
			}
			else
				$ratingvect[$i] = false;
		}
		
		//Calculate covariance matrix for this user
		
		for ($i = 0; $i < count($predictjokes); $i++)
		{
			for ($j = 0; $j < count($predictjokes); $j++)
			{
				if (!(($ratingvect[$i] === false) || ($ratingvect[$j] === false)))
				{
					$covij = $ratingvect[$i] * $ratingvect[$j]; //This represents the covariance of joke i and joke j (for this user)
					$covnormalizer[$i][$j]++; //Keep track of rated contributions to the global covariance matrix
					$correlnormx[$i][$j] += $ratingvect[$i] * $ratingvect[$i];
					$correlnormy[$i][$j] += $ratingvect[$j] * $ratingvect[$j];
				}
				else
				{
					$covij = 0.0; //If the user did not rate one or two of these prediction jokes, the covariance is zero (rather than positive or negative)
				}
				
				$query = "INSERT INTO usercovariances (userid, row, col, covariance) VALUES ({$userid}, {$i}, {$j}, {$covij})";
				$resultinner = mysql_query($query, $connection);
				if (!$resultinner)
					die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
				
				$covariance[$i][$j] += $covij; //Add the covariance to the global covariance matrix
			}
		}
	}
}

/* Tested - works */
//Project on eigenplane and store projections
function editedCalcProjection($connection, $numaxes)
{
	global $predictjokes, $eigenvecs, $projections, $projectionsforclustering, $useridsforprojections;
	
	$projection = array();
	
	$usercount = 0;
	
	$query = "SELECT userid FROM users WHERE ininitialusergroup=1 AND ratedpredictjokesandmore=1"; //If there are not enough ratings for this user (if he/she has not rated all the prediction set jokes and at least another one), do not try to project	
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	while ($row = mysql_fetch_array($result))
	{
		//$projection does not have to be cleared each time, here, as project clears it
		
		$userid = $row[0];

		project($connection, $projection, $eigenvecs, $userid, $numaxes);
		
		$useridsforprojections[$usercount] = $userid;
		
		for ($axis = 0; $axis < $numaxes; $axis++)
		{
			$projections[$usercount][$axis] = $projection[$axis]; //This will get changed by genLayer
			$projectionsforclustering[$usercount][$axis] = $projection[$axis]; //A version needs to be retained until genClusters
			
			$query = "INSERT INTO projection (userid, axis, projectionvalue) VALUES ({$userid}, {$axis}, {$projection[$axis]})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
		
		$usercount++;
	}
}

function exportMATLABData($connection)
{
	global $numjokes, $maxjokerating, $minjokerating;
	
	$clusternum = 1;
	
	$query = "SELECT DISTINCT cluster FROM clusters ORDER BY cluster";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$filename = "points/points" . $clusternum . ".dat";
		$header = "%Cluster $cluster ($clusternum)\n\n";
		
		if (!$handle = fopen($filename, 'w'))
		{
			echo "Cannot open file ($filename)";
			exit;
		}
		
		if (fwrite($handle, $header) === false)
		{
			echo "Cannot write to file ($filename)";
			exit;
		}
		
		$usersincluster = array();
		
		$query = "SELECT userid FROM clusters WHERE cluster='{$cluster}' ORDER BY userid";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$userid = $row[0];
			$usersincluster[] = $userid;
		}
		
		$jokeratings = array();
		$meanratings = array();
		$ratingsum = array();
		$ratingcount = array();

		for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
		{
			$jokeid = $jokeindex + 1;

			if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes
				continue;

			$ratingsum[$jokeid] = 0;
			$ratingcount[$jokeid] = 0;

			foreach ($usersincluster as $userid)
			{
				$jokeratings[$jokeid][$userid] = isGetJokeRating($connection, $userid, $jokeid);

				if (!($jokeratings[$jokeid][$userid] === false))
				{
					$ratingsum[$jokeid] += $jokeratings[$jokeid][$userid];
					$ratingcount[$jokeid]++;
				}
			}

			$meanratings[$jokeid] = $ratingsum[$jokeid] / $ratingcount[$jokeid]; //Calculate the mean of jokeid
		
			$fileline = "";
			
			foreach ($jokeratings[$jokeid] as $userid => $jokerating)
			{
				//Replace null values with the mean rating for the joke (across the cluster's users) so that MATLAB can evaluate
				
				if ($jokeratings[$jokeid][$userid] === false)
					$jokeratings[$jokeid][$userid] = $meanratings[$jokeid];
					
				$fileline .= ($jokeratings[$jokeid][$userid] . " ");
			}
			
			$fileline .= "\n";
			
			if (fwrite($handle, $fileline) === false)
			{
				echo "Cannot write to file ($filename)";
				exit;
			}
		}
		
		fclose($handle);
		
		$clusternum++;
	}
	
	print("Completed: MATLAB Data Export\n");
}

function exportMATLABDataAllUsers($connection)
{
	global $numjokes, $maxjokerating, $minjokerating;
		
	$filename = "points/points.dat";
	$header = "";
	
	if (!$handle = fopen($filename, 'w'))
	{
		echo "Cannot open file ($filename)";
		exit;
	}
	
	if (fwrite($handle, $header) === false)
	{
		echo "Cannot write to file ($filename)";
		exit;
	}
	
	$usersratednumjokes = array();
	
	$query = "SELECT clusters.userid FROM clusters, users WHERE clusters.userid = users.userid AND users.numrated = NUMJOKES ORDER BY userid";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$userid = $row[0];
		$usersratednumjokes[] = $userid;
	}
	
	$jokeratings = array();
	$meanratings = array();
	$ratingsum = array();
	$ratingcount = array();

	for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
	{
		$jokeid = $jokeindex + 1;

		if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes
			continue;

		$ratingsum[$jokeid] = 0;
		$ratingcount[$jokeid] = 0;

		foreach ($usersratednumjokes as $userid)
		{
			$jokeratings[$jokeid][$userid] = isGetJokeRating($connection, $userid, $jokeid);

			if (!($jokeratings[$jokeid][$userid] === false))
			{
				$ratingsum[$jokeid] += $jokeratings[$jokeid][$userid];
				$ratingcount[$jokeid]++;
			}
		}

		$meanratings[$jokeid] = $ratingsum[$jokeid] / $ratingcount[$jokeid]; //Calculate the mean of jokeid
	
		$fileline = "";
		
		foreach ($jokeratings[$jokeid] as $userid => $jokerating)
		{
			if ($jokeratings[$jokeid][$userid] === false)
				echo "Error: a joke is not rated";
				
			$fileline .= ($jokeratings[$jokeid][$userid] . " ");
		}
		
		$fileline .= "\n";
		
		if (fwrite($handle, $fileline) === false)
		{
			echo "Cannot write to file ($filename)";
			exit;
		}
	}
	
	fclose($handle);
	
	print("Completed: MATLAB Data Export\n");
}

function importMATLABResults($connection)
{
	clearTable($connection, "jokeclusters");
	
	$dirname = "clusterindices/";
	
	$clusternum = 1;
	
	$query = "SELECT DISTINCT cluster FROM clusters ORDER BY cluster";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$filename = ("clusterindices" . $clusternum . ".dat");
		
		if (!$handle = fopen(($dirname . $filename), "r"))
		{
			echo "Cannot open file ($filename)";
			exit;
		}
		
		$filecontents = fread($handle, filesize($dirname . $filename));
		$jokelines = explode("\n", trim($filecontents));
		
		$jokeid = 1;
		
		foreach ($jokelines as $jokeline)
		{
			$jokecluster = $jokeline;
			
			$query = "INSERT INTO jokeclusters (cluster, jokeid, jokecluster) VALUES ('{$cluster}', {$jokeid}, {$jokecluster})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
			$jokeid++;
		}
		
		fclose($handle);
		
		$clusternum++;
	}
	
	print("Completed: MATLAB Results Import\n");
}

function importMATLABResultsAllUsers($connection)
{
	clearTable($connection, "jokeclusters");
	
	$dirname = "clusterindices/";
	
	$clusternum = 1;
	
	$query = "SELECT DISTINCT cluster FROM clusters ORDER BY cluster";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$filename = ("clusterindices.dat");
		
		if (!$handle = fopen(($dirname . $filename), "r"))
		{
			echo "Cannot open file ($filename)";
			exit;
		}
		
		$filecontents = fread($handle, filesize($dirname . $filename));
		$jokelines = explode("\n", trim($filecontents));
		
		$jokeid = 1;
		
		foreach ($jokelines as $jokeline)
		{
			$jokecluster = $jokeline;
			
			$query = "INSERT INTO jokeclusters (cluster, jokeid, jokecluster) VALUES ('{$cluster}', {$jokeid}, {$jokecluster})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
			$jokeid++;
		}
		
		fclose($handle);
		
		$clusternum++;
	}
	
	print("Completed: MATLAB Results Import\n");
}

function runSimulationUsersRatedX($connection, $meanweight, &$numjokestoratearray, &$e2plot, &$prede2plot, &$pte2plot, &$ptprede2plot,
	&$e3plot, &$prede3plot, &$pte3plot, &$ptprede3plot, &$se2plot, &$sprede2plot, &$se3plot, &$sprede3plot)
{	
	$maxnumjokestorate = max($numjokestoratearray);

	//calculateEigentastePredictionsForClusters($connection);
	
	runSimulationE2UsersRatedX($connection, $numjokestoratearray, $pte2plot, $ptprede2plot);
	runSimulationE3UsersRatedX($connection, $meanweight, $numjokestoratearray, $pte3plot, $ptprede3plot);
	
	calculateCumulativeRatings($numjokestoratearray, $maxnumjokestorate, $pte2plot, $e2plot);
	calculateCumulativeRatings($numjokestoratearray, $maxnumjokestorate, $ptprede2plot, $prede2plot);
	calculateCumulativeRatings($numjokestoratearray, $maxnumjokestorate, $pte3plot, $e3plot);
	calculateCumulativeRatings($numjokestoratearray, $maxnumjokestorate, $ptprede3plot, $prede3plot);
	
	$smoothingweight = 2;
	
	calculateSmoothedRatings($numjokestoratearray, $maxnumjokestorate, $pte2plot, $se2plot, $smoothingweight);
	calculateSmoothedRatings($numjokestoratearray, $maxnumjokestorate, $ptprede2plot, $sprede2plot, $smoothingweight);
	calculateSmoothedRatings($numjokestoratearray, $maxnumjokestorate, $pte3plot, $se3plot, $smoothingweight);
	calculateSmoothedRatings($numjokestoratearray, $maxnumjokestorate, $ptprede3plot, $sprede3plot, $smoothingweight);
}

function runSimulationE2UsersRatedX($connection, &$numjokestoratearray, &$pte2plot, &$ptprede2plot)
{	
	global $predictjokes, $numjokes;
	
	$maxnumjokestorate = max($numjokestoratearray);
	
	$predictvectors = array();
	loadPredictVectors($connection, $predictvectors);
	
	$pttotaljokerating = array();
	$pttotaljokeratingcount = array();
	$pttotalpredictedrating = array();
	$pttotalpredictedratingcount = array();
	
	foreach ($numjokestoratearray as $numjokestorate)
	{
		$pttotaljokerating[$numjokestorate] = 0;
		$pttotaljokeratingcount[$numjokestorate] = 0;
		$pttotalpredictedrating[$numjokestorate] = 0;
		$pttotalpredictedratingcount[$numjokestorate] = 0;
	}
	
	$usercount = 0;
	
	$query = "SELECT userid FROM users WHERE ininitialusergroup=0 AND ratedpredictjokes=1 AND numrated={$numjokes}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];
		
		if (($usercount % 100) == 0)
			print "User count has passed $usercount...\n";
		
		$cluster = matchWithCluster($connection, $userid);
		
		$dbjokeratings = array_fill(1, NUMJOKES, false);
		getJokeRatings($connection, $userid, $dbjokeratings);
		
		$predictionsforcluster = array_fill(1, NUMJOKES, false);
		getEigentastePredictionsForCluster($connection, $cluster, $predictionsforcluster);
		
		$ptjokerating = array();
		$ptpredictedrating = array();
		
		foreach ($numjokestoratearray as $numjokestorate)
		{
			$ptjokerating[$numjokestorate] = 0;
			$ptpredictedrating[$numjokestorate] = 0;
		}
		
		$ratedjokes = array(5, 7, 8, 13, 15, 16, 17, 18, 19, 20);

		foreach ($numjokestoratearray as $numjokestorate)
		{
			//Find the highest rated joke for this cluster			
			$recommendedjokeid = getHighestUnratedJokeIDInCluster($predictvectors[$cluster], $ratedjokes);
			
			if ($recommendedjokeid === false)
			{
				print "Error: a blank joke ID was recommended...";
				exit;
			}
			
			$jokerating = $dbjokeratings[$recommendedjokeid];
			$predictedrating = $predictionsforcluster[$recommendedjokeid];

			//This shouldn't happen, because $query should only choose users have who rated $maxnumjokestorate
			if ($jokerating === false)
			{
				print "Error: a user that should have been skipped has actually been looked at...";
				continue 2;
			}
			
			if ($predictedrating === false)
			{
				print "Error: predictedrating === false...";
				exit;
			}

			$ratedjokes[] = $recommendedjokeid;
			
			$ptjokerating[$numjokestorate] = $jokerating;
			$ptpredictedrating[$numjokestorate] = $predictedrating;
		}
		
		foreach ($numjokestoratearray as $numjokestorate)
		{
			$pttotaljokerating[$numjokestorate] += $ptjokerating[$numjokestorate];
			$pttotaljokeratingcount[$numjokestorate]++;
			
			$pttotalpredictedrating[$numjokestorate] += $ptpredictedrating[$numjokestorate];
			$pttotalpredictedratingcount[$numjokestorate]++;
		}
		
		$usercount++;
	}
	
	foreach ($numjokestoratearray as $numjokestorate)
	{
		$ptmeanjokerating = ($pttotaljokerating[$numjokestorate] / $pttotaljokeratingcount[$numjokestorate]);
		$ptmeanpredictedrating = ($pttotalpredictedrating[$numjokestorate] / $pttotalpredictedratingcount[$numjokestorate]);
		
		$pte2plot[$numjokestorate] = $ptmeanjokerating;
		$ptprede2plot[$numjokestorate] = $ptmeanpredictedrating;
	}
	
	print("Completed: Simulation E2 Users Rated X\n");
}

function runSimulationE3UsersRatedX($connection, $meanweight, &$numjokestoratearray, &$pte3plot, &$ptprede3plot)
{		
	global $predictjokes, $numjokes;
	
	$maxnumjokestorate = max($numjokestoratearray);
	
	$highestratedjokes = array();
	
	//Added
	//(Consider the user's actual ratings of prediction jokes, rather than just the cluster means)
	$jokeclustersforjokes = array();
	getJokeClustersForJokes($connection, $jokeclustersforjokes);
	//End Added
	
	$query = "SELECT DISTINCT cluster FROM clusters ORDER BY cluster";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$highestratedjokes[$cluster] = array();
		getHighestRatedJokesInJokeClusters($connection, $cluster, $highestratedjokes[$cluster]);
	}
	
	$pttotaljokerating = array();
	$pttotaljokeratingcount = array();
	$pttotalpredictedrating = array();
	$pttotalpredictedratingcount = array();
	
	foreach ($numjokestoratearray as $numjokestorate)
	{
		$pttotaljokerating[$numjokestorate] = 0;
		$pttotaljokeratingcount[$numjokestorate] = 0;
		$pttotalpredictedrating[$numjokestorate] = 0;
		$pttotalpredictedratingcount[$numjokestorate] = 0;
	}
	
	$usercount = 0;
	
	$query = "SELECT userid FROM users WHERE ininitialusergroup=0 AND ratedpredictjokes=1 AND numrated={$numjokes}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];
		
		if (($usercount % 100) == 0)
			print "User count has passed $usercount...\n";
		
		$cluster = matchWithCluster($connection, $userid);

		$dbjokeratings = array_fill(1, NUMJOKES, false);
		getJokeRatings($connection, $userid, $dbjokeratings);
		
		$predictionsforcluster = array_fill(1, NUMJOKES, false);
		getEigentastePredictionsForCluster($connection, $cluster, $predictionsforcluster);

		$ptjokerating = array();
		$ptpredictedrating = array();
		
		foreach ($numjokestoratearray as $numjokestorate)
		{
			$ptjokerating[$numjokestorate] = 0;
			$ptpredictedrating[$numjokestorate] = 0;
		}
		
		$jokeclustersums = array();
		$jokeclustercounts = array();
		
		$ratedjokes = array(5, 7, 8, 13, 15, 16, 17, 18, 19, 20);
		
		$query = "SELECT AVG(clustermeans.meanrating), jokeclusters.jokecluster FROM clustermeans, jokeclusters WHERE clustermeans.cluster = jokeclusters.cluster AND clustermeans.jokeid = jokeclusters.jokeid AND clustermeans.cluster='{$cluster}' GROUP BY jokeclusters.jokecluster";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$jokeclustermean = $row[0];
			$jokecluster = $row[1];
			
			preloadClusterSumsCount($connection, $cluster, $jokecluster, $jokeclustermean, $meanweight, $jokeclustersforjokes, $jokeclustersums, $jokeclustercounts, $dbjokeratings, $ratedjokes);
		}
		
		$emptyclusters = array();
		
		foreach ($numjokestoratearray as $numjokestorate)
		{
			$recommendedjokeid = false;
	
			while ($recommendedjokeid === false)
			{
				//Find the joke cluster that the user currently prefers
				$favoritejokecluster = getFavoriteJokeCluster($jokeclustersums, $jokeclustercounts, $emptyclusters);
				
				if ($favoritejokecluster === false)
				{
					print "Error: favoritejokecluster === false...";
					exit;
				}

				//Find the highest rated joke within that joke cluster
				$recommendedjokeid = getHighestUnratedJokeIDInJokeCluster($favoritejokecluster, $highestratedjokes[$cluster], $ratedjokes);
		
				if ($recommendedjokeid === false)
				{
					$emptyclusters[] = $favoritejokecluster;
				}
			}
	
			$jokerating = $dbjokeratings[$recommendedjokeid];
			$predictedrating = $predictionsforcluster[$recommendedjokeid];
	
			//This shouldn't happen, because $query should only choose users have who rated $maxnumjokestorate
			if ($jokerating === false)
			{
				print "Error: a user that should have been skipped has actually been looked at...";
				continue 2;
			}
			
			if ($predictedrating === false)
			{
				print "Error: predictedrating === false...";
				exit;
			}

			$ratedjokes[] = $recommendedjokeid;

			$jokeclustersums[$favoritejokecluster] += $jokerating;
			$jokeclustercounts[$favoritejokecluster]++;
			
			$ptjokerating[$numjokestorate] = $jokerating;
			$ptpredictedrating[$numjokestorate] = $predictedrating;
		}
		
		foreach ($numjokestoratearray as $numjokestorate)
		{
			$pttotaljokerating[$numjokestorate] += $ptjokerating[$numjokestorate];
			$pttotaljokeratingcount[$numjokestorate]++;
			
			$pttotalpredictedrating[$numjokestorate] += $ptpredictedrating[$numjokestorate];
			$pttotalpredictedratingcount[$numjokestorate]++;
		}
		
		$usercount++;
	}
	
	foreach ($numjokestoratearray as $numjokestorate)
	{
		$ptmeanjokerating = ($pttotaljokerating[$numjokestorate] / $pttotaljokeratingcount[$numjokestorate]);
		$ptmeanpredictedrating = ($pttotalpredictedrating[$numjokestorate] / $pttotalpredictedratingcount[$numjokestorate]);
		
		$pte3plot[$numjokestorate] = $ptmeanjokerating;
		$ptprede3plot[$numjokestorate] = $ptmeanpredictedrating;
	}
	
	print("Completed: Simulation E3 Users Rated X\n");
}

/* Tested - works */
function loadPredictVectors($connection, &$predictvectors)
{
	$query = "SELECT DISTINCT cluster FROM clusters ORDER BY cluster";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$predictvectors[$cluster] = array();
		
		$query = "SELECT jokeid FROM predictvectors WHERE cluster='{$cluster}' ORDER BY rank";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{		
			$jokeid = $row[0];

			$predictvectors[$cluster][] = $jokeid;
		}
	}
}

/* Tested - works */
function preloadClusterSumsCount($connection, $cluster, $jokecluster, $jokeclustermean, $meanweight, &$jokeclustersforjokes, &$jokeclustersums, &$jokeclustercounts, &$dbjokeratings, &$ratedjokes)
{
	//Added
	//(Consider the user's actual ratings of prediction jokes, rather than just the cluster means)
	$jokeclustersums[$jokecluster] = 0;
	$jokeclustercounts[$jokecluster] = 0;

	foreach ($ratedjokes as $ratedjokeid)
	{
		if ($jokeclustersforjokes[$cluster][$ratedjokeid] == $jokecluster)
		{
			$jokeclustersums[$jokecluster] += $dbjokeratings[$ratedjokeid];
			$jokeclustercounts[$jokecluster]++;
		}
	}

	$numratedjokesincluster = $jokeclustercounts[$jokecluster];

	$jokeclustersums[$jokecluster] += ($jokeclustermean * ($meanweight - $numratedjokesincluster));
	$jokeclustercounts[$jokecluster] += ($meanweight - $numratedjokesincluster);
	//End Added
}

/* Tested - works */
function getPlotString($plotarray)
{
	$plotstring = "";
	
	foreach ($plotarray as $numjokestorate => $plotelement)
	{
		if (!($plotelement === false))
		{
			$plotstring .= "$numjokestorate $plotelement\n";
		}
	}
	
	return $plotstring;
}

/* Tested - works */
function calculateCumulativeRatings($numjokestoratearray, $maxnumjokestorate, &$pointwiseratings, &$cumulativeratings)
{
	$cumulativeratingssum = array_fill(1, $maxnumjokestorate, 0);
	$cumulativeratingscount = array_fill(1, $maxnumjokestorate, 0);
	
	foreach ($numjokestoratearray as $numjokestorate)
	{
		for ($i = 1; $i <= $numjokestorate; $i++)
		{
			$cumulativeratingssum[$numjokestorate] += $pointwiseratings[$i];
			$cumulativeratingscount[$numjokestorate]++;
		}

		$cumulativeratings[$numjokestorate] = ($cumulativeratingssum[$numjokestorate] / $cumulativeratingscount[$numjokestorate]);
	}
}

/* Tested - works */
function calculateSmoothedRatings($numjokestoratearray, $maxnumjokestorate, &$pointwiseratings, &$smoothedratings, $smoothingweight)
{
	$smoothedratingssum = array_fill(1, $maxnumjokestorate, 0);
	$smoothedratingscount = array_fill(1, $maxnumjokestorate, 0);
	
	foreach ($numjokestoratearray as $numjokestorate)
	{
		if ($numjokestorate <= $smoothingweight)
		{
			$smoothedratings[$numjokestorate] = false;
			continue;
		}
			
		if ($numjokestorate > ($maxnumjokestorate - $smoothingweight))
		{
			$smoothedratings[$numjokestorate] = false;
			continue;
		}
			
		for ($i = ($numjokestorate - $smoothingweight); $i <= ($numjokestorate + $smoothingweight); $i++)
		{
			if (($i >= 1) && ($i <= $maxnumjokestorate))
			{
				$smoothedratingssum[$numjokestorate] += $pointwiseratings[$i];
				$smoothedratingscount[$numjokestorate]++;
			}
		}

		$smoothedratings[$numjokestorate] = ($smoothedratingssum[$numjokestorate] / $smoothedratingscount[$numjokestorate]);
	}
}

/* Tested - works */
function getFavoriteJokeCluster(&$jokeclustersums, &$jokeclustercounts, &$emptyclusters)
{
	$maxjokeclustermean = null;
	$maxjokecluster = null;
	
	foreach ($jokeclustersums as $jokecluster => $jokeclustersum)
	{	
		$jokeclustermean = ($jokeclustersums[$jokecluster] / $jokeclustercounts[$jokecluster]);
		
		if ((($maxjokeclustermean == null) || ($jokeclustermean > $maxjokeclustermean)) && !in_array($jokecluster, $emptyclusters))
		{
			$maxjokeclustermean = $jokeclustermean;
			$maxjokecluster = $jokecluster;
		}
	}
	
	if ($maxjokecluster == null)
		return false;
	
	return $maxjokecluster;
}

/* Tested - works */
function getJokeClustersForJokes($connection, &$jokeclustersforjokes)
{
	$query = "SELECT cluster, jokecluster, jokeid FROM jokeclusters";
	$resultinner = mysql_query($query, $connection);
	if (!$resultinner)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($resultinner))
	{
		$cluster = $row[0];
		$jokecluster = $row[1];
		$jokeid = $row[2];
		
		$jokeclustersforjokes[$cluster][$jokeid] = $jokecluster;
	}
}

/* Tested - works */
//Create a descending list of all the mean ratings of jokes by users in the cluster, and in that order, stick those jokes into the jokecluster buckets of highestratedjokesforcluster
//This way, for any joke cluster chosen, the jokes that the people in the user cluster like the most are given first
function getHighestRatedJokesInJokeClusters($connection, $cluster, &$highestratedjokesforcluster)
{	
	$query = "SELECT clustermeans.jokeid, jokeclusters.jokecluster FROM clustermeans, jokeclusters WHERE jokeclusters.cluster = clustermeans.cluster AND clustermeans.jokeid = jokeclusters.jokeid AND clustermeans.cluster = '{$cluster}' ORDER BY clustermeans.meanrating DESC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$jokeid = $row[0];
		$jokecluster = $row[1];
		
		$highestratedjokesforcluster[$jokecluster][] = $jokeid;
	}
}

/* Tested - works */
function getHighestUnratedJokeIDInJokeCluster($jokecluster, &$highestratedjokesforcluster, &$ratedjokes)
{	
	foreach ($highestratedjokesforcluster[$jokecluster] as $jokeid)
	{
		if (!in_array($jokeid, $ratedjokes))
			return $jokeid;
	}
	
	return false;
}

/* Tested - works */
function getHighestUnratedJokeIDInCluster(&$predictvectorforcluster, &$ratedjokes)
{
	foreach ($predictvectorforcluster as $jokeid)
	{
		if (!in_array($jokeid, $ratedjokes))
			return $jokeid;
	}
	
	return false;
}

/* Tested - works */
function matchWithCluster($connection, $userid)
{
	global $predictjokes, $eigenaxes;
	
	//Select the first two eigenvectors

	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($axis = 0; $axis < $eigenaxes; $axis++)
		{
			$query = "SELECT eigenvectorelement FROM eigenvectors WHERE row={$i} AND col={$axis}";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

			$row = mysql_fetch_row($result);
			$eigenvecs[$i][$axis] = $row[0];
		}
	}

	//Project on eigenplane

	project($connection, $projection, $eigenvecs, $userid, $eigenaxes);

	//Get the layer

	$query = "SELECT layervalue, layerindex FROM layer";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$layervalue = $row[0];
		$layerindex = $row[1];

		$layer[$layerindex] = $layervalue;
	}

	//Determine the cluster

	$cluster = getClusterName($layer, $projection);
	
	return $cluster;
}

/* Tested - works */
function getRandomFloat($min, $max)
{
	return ($min + (lcg_value() * (abs($max - $min))));
}

/* Tested - works */
function getJokeRatings($connection, $userid, &$jokeratings)
{
	$query = "SELECT jokeid, jokerating FROM ratings WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$jokeid = $row[0];
		$jokerating = $row[1];
		
		$jokeratings[$jokeid] = $jokerating;
	}
}

/* Tested - works */
function getEigentastePredictionsForCluster($connection, $cluster, &$predictionsforcluster)
{	
	$query = "SELECT jokeid, prediction FROM epredictions WHERE cluster='{$cluster}'";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$jokeid = $row[0];
		$prediction = $row[1];
		
		$predictionsforcluster[$jokeid] = $prediction;
	}
}

/* Tested - works (at least on 36,705 initialgroup 1 users) */
//Calculate Eigentaste Predictions for Clusters
//Note: This only works when the users who are being predicted for are not clustered, too (if that's the case, knowledge is being used when it shouldn't be)
function calculateEigentastePredictionsForClusters($connection)
{
	global $numjokes;
	
	clearTable($connection, "epredictions");
	
	$predictions = array();
	
	$query = "SELECT DISTINCT cluster FROM clusters ORDER BY cluster";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$predictions[$cluster] = array_fill(1, $numjokes, false);
	
		$usersincluster = array();
	
		$query = "SELECT userid FROM clusters WHERE cluster='{$cluster}' ORDER BY userid";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$userid = $row[0];
			$usersincluster[] = $userid; 
		}
	
		for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++) //Takes into account removed jokes
		{	
			$jokeid = $jokeindex + 1;

			$num = 0;
			$denom = 0;
			$count = 0;

			//Generate the prediction of the user's rating of joke jokeid

			foreach ($usersincluster as $userid)
			{	
				$jokerating = isGetJokeRating($connection, $userid, $jokeid);
			
				if (!($jokerating === false))
				{
					$num += $jokerating;
					$denom++;
					$count++;
				}
			}

			//If count == 0 (i.e. there were no users to generate a prediction from), do not generate a prediction
			if ($count != 0)
			{
				$predictions[$cluster][$jokeid] = $num / $denom;
				$prediction = $predictions[$cluster][$jokeid];

				$query = "INSERT INTO epredictions (cluster, jokeid, prediction) VALUES ('{$cluster}', {$jokeid}, {$prediction})";
				$resultinnerinner = mysql_query($query, $connection);
				if (!$resultinnerinner)
					die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			}
		}
	}
}

function unitTest($connection)
{	
	//Test: loadPredictVectors
	
	$predictvectors = array();
	loadPredictVectors($connection, $predictvectors);
	
	foreach ($predictvectors as $cluster => $predictvector)
	{
		print "Cluster $cluster:\n";
		foreach ($predictvector as $jokeid)
		{
			print "$jokeid ";
		}
		print "\n\n";
	}
	
	/*
	//Test: preloadClusterSumsCount
	
	$cluster = "001000";
	$jokecluster = 7;
	$jokeclustermean = 4.2;
	$meanweight = 20;
	$jokeclustersforjokes[$cluster][2] = 5;
	$jokeclustersforjokes[$cluster][3] = 7;
	$jokeclustersforjokes[$cluster][6] = 9;
	$jokeclustersforjokes[$cluster][8] = 7;
	$jokeclustersums = array();
	$jokeclustercounts = array();
	$dbjokeratings = array();
	$dbjokeratings[3] = 3.25;
	$dbjokeratings[6] = 2.77;
	$dbjokeratings[8] = 4.66;
	$ratedjokes = array(2, 3, 6, 8);
	
	preloadClusterSumsCount($connection, $cluster, $jokecluster, $jokeclustermean, $meanweight, $jokeclustersforjokes, $jokeclustersums, $jokeclustercounts, $dbjokeratings, $ratedjokes);
	
	print "jokeclustersums[$jokecluster] = " . $jokeclustersums[$jokecluster] . "\n";
	print "jokeclustercounts[$jokecluster] = " . $jokeclustercounts[$jokecluster] . "\n";
	
	$cluster = "100100";
	$jokecluster = 7;
	$jokeclustermean = 3.2;
	$meanweight = 10;
	$jokeclustersforjokes[$cluster][2] = 7;
	$jokeclustersforjokes[$cluster][3] = 7;
	$jokeclustersforjokes[$cluster][6] = 9;
	$jokeclustersforjokes[$cluster][8] = 7;
	$jokeclustersums = array();
	$jokeclustercounts = array();
	$dbjokeratings = array();
	$dbjokeratings[3] = 3.25;
	$dbjokeratings[2] = 2.77;
	$dbjokeratings[8] = 4.66;
	$ratedjokes = array(2, 3, 6, 8);
	
	preloadClusterSumsCount($connection, $cluster, $jokecluster, $jokeclustermean, $meanweight, $jokeclustersforjokes, $jokeclustersums, $jokeclustercounts, $dbjokeratings, $ratedjokes);
	
	print "jokeclustersums[$jokecluster] = " . $jokeclustersums[$jokecluster] . "\n";
	print "jokeclustercounts[$jokecluster] = " . $jokeclustercounts[$jokecluster] . "\n";
	
	//Test: getFavoriteJokeCluster
	
	$jokeclustersums = array();
	$jokeclustercounts = array();

	$jokeclustersums[1] = 34;
	$jokeclustercounts[1] = 2;
	$jokeclustersums[2] = 36;
	$jokeclustercounts[2] = 3;
	$jokeclustersums[3] = 40;
	$jokeclustercounts[3] = 4;
	$jokeclustersums[4] = 100;
	$jokeclustercounts[4] = 4;
	$jokeclustersums[5] = 24;
	$jokeclustercounts[5] = 2;
	
	$emptyclusters = array(1, 4, 2, 3);
	
	$favoritejokecluster = getFavoriteJokeCluster($jokeclustersums, $jokeclustercounts, $emptyclusters);
	
	if ($favoritejokecluster === false)
	{
		print "Error: favoritejokecluster === false...";
		exit;
	}
	
	print "Favorite cluster: $favoritejokecluster\n";
	
	//Test: getJokeClustersForJokes
	
	$jokeclustersforjokes = array();
	getJokeClustersForJokes($connection, $jokeclustersforjokes);
	
	foreach ($jokeclustersforjokes as $cluster => $jokeclustersforjokesforcluster)
	{
		foreach ($jokeclustersforjokesforcluster as $jokeid => $jokecluster)
		{
			print "jokeclustersforjokesforcluster[$cluster][$jokeid] = " . $jokecluster . "\n";
		}
	}
	
	//Test: getHighestRatedJokesInJokeClusters
	
	$highestratedjokesforcluster = array();
	$cluster = "010100";
	
	getHighestRatedJokesInJokeClusters($connection, $cluster, $highestratedjokesforcluster);
	
	print "Cluster $cluster\n";
	foreach ($highestratedjokesforcluster as $jokecluster => $highestratedjokesforclusterforjokecluster)
	{
		print "Joke cluster $jokecluster: ";
		foreach ($highestratedjokesforclusterforjokecluster as $jokeid)
		{
			print "$jokeid ";
		}
		print "\n";
	}
	print "\n";
	
	$highestratedjokesforcluster = array();
	$cluster = "100010";
	
	getHighestRatedJokesInJokeClusters($connection, $cluster, $highestratedjokesforcluster);
	
	print "Cluster $cluster\n";
	foreach ($highestratedjokesforcluster as $jokecluster => $highestratedjokesforclusterforjokecluster)
	{
		print "Joke cluster $jokecluster: ";
		foreach ($highestratedjokesforclusterforjokecluster as $jokeid)
		{
			print "$jokeid ";
		}
		print "\n";
	}
	print "\n";
	
	//Test: getHighestUnratedJokeIDInJokeCluster
	
	$jokecluster1 = 9;
	$jokecluster2 = 6;
	
	$highestratedjokesforcluster[$jokecluster1] = array(3, 6, 9, 4, 1, 5, 2, 8);
	$highestratedjokesforcluster[$jokecluster2] = array(15, 18, 19, 13, 11, 22, 17, 23);
	$ratedjokes = array(4, 2, 6, 18);
	
	$rand = rand(0,1);
	if ($rand == 0)
		$jokecluster = $jokecluster1;
	else
		$jokecluster = $jokecluster2;
		
	while (!(getHighestUnratedJokeIDInJokeCluster($jokecluster, $highestratedjokesforcluster, $ratedjokes) === false))
	{
		$rand = rand(0,1);
		if ($rand == 0)
			$jokecluster = $jokecluster1;
		else
			$jokecluster = $jokecluster2;
			
		$jokeid = getHighestUnratedJokeIDInJokeCluster($jokecluster, $highestratedjokesforcluster, $ratedjokes);
		$ratedjokes[] = $jokeid;
		
		print "Favorite joke cluster: $jokecluster\n";
		print "Rated $jokeid\n";
		
		foreach ($ratedjokes as $ratedjoke)
		{
			print "$ratedjoke ";
		}
		print "\n";
	}
	
	//Test: getHighestUnratedJokeIDInCluster
	
	$predictvectorforcluster = array(3, 6, 7, 4, 1, 5, 2, 8);
	$ratedjokes = array(4, 2, 6);
	
	while (!(getHighestUnratedJokeIDInCluster($predictvectorforcluster, $ratedjokes) === false))
	{
		$jokeid = getHighestUnratedJokeIDInCluster($predictvectorforcluster, $ratedjokes);
		$ratedjokes[] = $jokeid;
		
		print "Rated $jokeid\n";
		
		foreach ($ratedjokes as $ratedjoke)
		{
			print "$ratedjoke ";
		}
		print "\n";
	}
	
	//Test: matchWithCluster
	
	$useridarray = array(1, 43, 33063, 33085, 73398, 73405);
	
	foreach ($useridarray as $userid)
	{
		print "User $userid:\n";
		
		$query = "SELECT cluster FROM clusters WHERE userid={$userid}";
		$result = mysql_query($query, $connection);
		if (!$result)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		$row = mysql_fetch_row($result);
		$cluster = $row[0];
		
		print "Cluster: $cluster\n";
		
		$matchedcluster = matchWithCluster($connection, $userid);
		
		print "Matched cluster: $matchedcluster\n";
		print "\n";
	}
	
	//Test: getJokeRatings
	
	$userid = 1231;
	
	$jokeratings = array_fill(1, NUMJOKES, false);
	getJokeRatings($connection, $userid, $jokeratings);
	
	print "Ratings for user $userid:\n";
	
	foreach ($jokeratings as $jokeid => $rating)
	{
		if ($rating === false)
			print "rating[$jokeid] = false\n";
		else
			print "rating[$jokeid] = $rating\n";
	}
	
	$userid = 54775;
	
	$jokeratings = array_fill(1, NUMJOKES, false);
	getJokeRatings($connection, $userid, $jokeratings);
	
	print "Ratings for user $userid:\n";
	
	foreach ($jokeratings as $jokeid => $rating)
	{
		if ($rating === false)
			print "rating[$jokeid] = false\n";
		else
			print "rating[$jokeid] = $rating\n";
	}
	*/
}
?>