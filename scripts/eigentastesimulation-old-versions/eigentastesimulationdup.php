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
/* End */

//Note: No joke seeding is used in this simulation. All seeding is bypassed.

ini_set("memory_limit","10000M");

openConnection();
print("Running Simulation\n\n");
//groupUsers($connection);
//markRatedPredictJokes($connection);

//editedCorrel($connection);
//genLayer($connection);
//genClusters($connection);
//genPredictVec($connection);

//exportMATLABData($connection);
importMATLABResults($connection);

/*
clearTable($connection, "e2simulationusers");
clearTable($connection, "e3simulationusers");

$numjokestoratearray = array(20, 25, 30, 35, 40, 45, 50, 5, 10, 15);
$meanweight = 5;

foreach ($numjokestoratearray as $numjokestorate)
{
	$userquery = "SELECT userid FROM users WHERE ininitialusergroup=0 AND ratedpredictjokes=1"; //If there are not enough ratings for this user (if he/she has not rated all the prediction jokes), do not try to place into a cluster
	$insert = true;
	runSimulationE2($connection, $numjokestorate, $userquery, $insert);
	runSimulationE3($connection, $meanweight, $numjokestorate, $userquery, $insert);

	$insert = false;
	$userquery = "SELECT e2simulationusers.userid FROM e2simulationusers, e3simulationusers WHERE e2simulationusers.numjokestorate = e3simulationusers.numjokestorate AND e2simulationusers.numjokestorate={$numjokestorate} AND e2simulationusers.userid = e3simulationusers.userid";
	runSimulationE2($connection, $numjokestorate, $userquery, $insert);
	runSimulationE3($connection, $meanweight, $numjokestorate, $userquery, $insert);
}*/

print("\nSimulation Complete\n");
mysql_close($connection);
?>
<?php
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
		
		/* JUST ADDED */
		
		$usermeans = array();
		
		foreach ($usersincluster as $userid)
		{	
			$userratingsum = 0;
			$userratingcount = 0;
			
			for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
			{
				$jokeid = $jokeindex + 1;

				if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes
					continue;
		
				$jokerating = isGetJokeRating($connection, $userid, $jokeid);
				
				if (!($jokerating === false))
				{
					$userratingsum += $jokerating;
					$userratingcount++;
				}
			}
			
			$usermeans[$userid] = $userratingsum / $userratingcount;
		}
		
		/* END JUSTE ADDED */

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
				//Replace null values with the mean rating for the user (reploaced from joke) so that MATLAB can evaluate
				
				if ($jokeratings[$jokeid][$userid] === false)
					$jokeratings[$jokeid][$userid] = getRandomFloat($minjokerating, $maxjokerating); //Changed from $meanratings[$jokeid]
					
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
		
/*
function genItemClusters($connection)
{
	global $numjokes, $maxjokerating, $minjokerating;
	
	$k = 10;
	
	$query = "SELECT DISTINCT cluster FROM clusters";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$usersincluster = array();
		
		$query = "SELECT userid FROM clusters WHERE cluster='{$cluster}'";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$userid = $row[0];
			$usersincluster[] = $userid;
		}
	
		//Normalization
		//*** Note: The standard deviations used for normalizing the jokes and centroids look at all users who have rated joke i, rather than just the users
		//who have rated both joke i and joke j (which is used for correl(i, j)). This might be a problem.
		
		//Create normalized joke points

		$normjokepoints = array();
		$jokeratings = array();
		$meanratings = array();
		$prerootstddevs = array();
		$stddevs = array();
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

			$prerootstddevs[$jokeid] = 0;

			foreach ($usersincluster as $userid)
			{	
				if (!($jokeratings[$jokeid][$userid] === false))
				{
					$normrating = $jokeratings[$jokeid][$userid] - $meanratings[$jokeid];
					$prerootstddevs[$jokeid] += ($normrating * $normrating);
				}
			}

			$stddevs[$jokeid] = sqrt($prerootstddevs[$jokeid]); //Calculate the standard deviation of jokeid

			foreach ($usersincluster as $userid)
			{
				if (!($jokeratings[$jokeid][$userid] === false))
				{
					$normjokepoints[$jokeid][$userid] = ($jokeratings[$jokeid][$userid] - $meanratings[$jokeid]) / $stddevs[$jokeid];
				}
				else
					$normjokepoints[$jokeid][$userid] = false;
			}
		}
		
		//Create normalized centroid points
		
		$normcentroidpoints = array();
		$centroidratings = array();
		$centmeanratings = array();
		$centprerootstddevs = array();
		$centstddevs = array();
		$centratingsum = array();
		$centratingcount = array();
		
		for ($centroidid = 0; $centroidid < $k; $centroidid++)
		{
			$centratingsum[$centroidid] = 0;
			$centratingcount[$centroidid] = 0;

			foreach ($usersincluster as $userid)
			{
				$centroidratings[$centroidid][$userid] = getRandomFloat($minjokerating, $maxjokerating);

				if (!($centroidratings[$centroidid][$userid] === false))
				{
					$centratingsum[$centroidid] += $centroidratings[$centroidid][$userid];
					$centratingcount[$centroidid]++;
				}
			}

			$centmeanratings[$centroidid] = $centratingsum[$centroidid] / $centratingcount[$centroidid]; //Calculate the mean of centroidid

			$centprerootstddevs[$centroidid] = 0;

			foreach ($usersincluster as $userid)
			{	
				if (!($centroidratings[$centroidid][$userid] === false))
				{
					$normrating = $centroidratings[$centroidid][$userid] - $centmeanratings[$centroidid];
					$centprerootstddevs[$centroidid] += ($normrating * $normrating);
				}
			}

			$centstddevs[$centroidid] = sqrt($centprerootstddevs[$centroidid]); //Calculate the standard deviation of centroidid

			foreach ($usersincluster as $userid)
			{
				if (!($centroidratings[$centroidid][$userid] === false))
				{
					$normcentroidpoints[$centroidid][$userid] = ($centroidratings[$centroidid][$userid] - $centmeanratings[$centroidid]) / $centstddevs[$centroidid];
				}
				else
					$normcentroidpoints[$centroidid][$userid] = false;
			}
		}
		
		$centroidschanged = true;
		
		$centroidgroups = array();
		
		while ($centroidschanged == true)
		{
			$centroidschanged = false;
			
			$newcentroidgroups = array();
			
			foreach ($jokeratings as $jokeid => $singlejokeratings)
			{
				$mindistancejokecentroid = null;
				$closestcentroidid = null;
				
				foreach ($centroidratings as $centroidid => $singlecentroidratings)
				{
					$correljokecentroid = getPearsonCorrel($usersincluster, $jokeratings[$jokeid], $centroidratings[$centroidid], $meanratings[$jokeid], $centmeanratings[$centroidid]);
					$distancejokecentroid = 1 - $correljokecentroid;
				
					if (($mindistancejokecentroid == null) || ($distancejokecentroid < $mindistancejokecentroid))
					{
						$mindistancejokecentroid = $distancejokecentroid;
						$closestcentroidid = $centroidid;
					}
				}
				
				$newcentroidgroups[$closestcentroidid][] = $jokeid;
			}
			
			foreach ($newcentroidgroups as $centroidid => $groupedjokeids)
			{
				foreach ($groupedjokeids as $key => $jokeid)
				{
					if (array_key_exists($centroidid, $centroidgroups) && array_key_exists($key, $centroidgroups[$centroidid]))
					{
						if ($newcentroidgroups[$centroidid][$key] != $centroidgroups[$centroidid][$key])
						{
							$centroidschanged = true;
							break 2;
						}
					}
				}
			}
			
			if ($centroidschanged == true)
			{
				//make new centroid_ratings
				
				//HOW DO I TAKET HE PEARSON CORRELATION OF CENTROID RATINGS WHEN THIS PRODUCES CENTROID NORMALIZED RATINGS?????
				
				foreach ($centroidratings as $centroidid => $singlecentroidratings)
				{
					$newcentnormsum = 0;
					$newcentnormcount = 0;
					
					foreach ($singlecentroidratings as $userid => $rating)
					{
						foreach ($newcentroidgroups[$centroidid] as $jokeid)
						{
							if (!($normjokepoints[$jokeid][$userid] === false))
							{
								$newcentnormsum += $normjokepoints[$centroidid][$userid];
								$newcentnormcount++;
							}
						}
					}
					
					$newcentnormmean = $newcentnormsum / $newcentnormcount;
				}
				
				$centroidratings[$centroidid][$userid]
				
				
				
				//Create a new centroid group array that will be used as a comparison in the next iteration of the loop
				
				$centroidgroups = $newcentroidgroups;
			}
		}
			
			//Calculate Pearson correlation

			
				
			//PEARSON CORRELATION SHOULD ONLY TAKE INTO ACCOUNT USERS IN THIS CLUSTER
			
			//Note that the data are centered by subtracting the mean, and scaled by dividing by the standard deviation.
			
			//Effectively, the Pearson distance -dp- is computed as dp = 1 - r and lies between 0 (when correlation coefficient is +1, i.e. the two samples are most similar) and 2 (when correlation coefficient is -1).
			//Note that the data are centered by subtracting the mean, and scaled by dividing by the standard deviation.
		}
	}
}

function getPearsonCorrel(&$usersincluster, &$ratingsi, &$ratingsj, $meanratingi, $meanratingj)
{
	$covariance = 0;
	$correlnormx = 0;
	$correlnormy = 0;
	$correl = 0;
	
	foreach ($usersincluster as $userid)
	{		
		if (!(($ratingsi[$userid] === false) || ($ratingsj[$userid] === false)))
		{
			$covij = ($ratingsi[$userid] - $meanratingi) * ($ratingsj[$userid] - $meanratingj); //This represents the covariance of joke i and joke j (for this user)
			$correlnormx += ($ratingsi[$userid] - $meanratingi) * ($ratingsi[$userid] - $meanratingi);
			$correlnormy += ($ratingsj[$userid] - $meanratingj) * ($ratingsj[$userid] - $meanratingj);
		}
		else
		{
			$covij = 0.0;
		}
		
		$covariance += $covij;
	}
	
	if ((sqrt($correlnormx) * sqrt($correlnormy)) != 0)
		$correl = $covariance / (sqrt($correlnormx) * sqrt($correlnormy));
	else
		$correl = 0;
		
	return $correl;
}

function getPearsonCorrel(&$usersincluster, &$pointi, &$pointj, $meanratingi, $meanratingj, $stddevi, $stddevj)
{
	
	//should the standard dev only include corated????: stddev is sum ONLY OF USERS WHO HAVE CORATED BOTH JOKES?
	
	$num = 0;
	
	foreach ($usersincluster as $userid)
	{
		$num += (($pointi[$userid] - $meanratingi) * ($pointj[$userid] - $meanratingj));
	}
	
	$denom = $stddevi * $stddevj;
	
	if ($denom != 0)
		$correl = $num / $denom;
	else
		$correl = 0;
		
	return $correl;
}
*/

function runSimulationE2($connection, $numjokestorate, $userquery, $insert)
{	
	global $predictjokes;
	
	$predictvectors = array();
	
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
	
	$totalmeanjokerating = 0;
	$totalmeanjokeratingcount = 0;
	
	$usercount = 0;
	
	$query = $userquery;
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];
		
		$dbjokeratings = array_fill(1, 100, false);
		getJokeRatings($connection, $userid, $dbjokeratings);
		
		$jokeratingsum = 0;
		$jokeratingcount = 0;
		
		$ratedjokes = array(5, 7, 8, 13, 15, 16, 17, 18, 19, 20);
		
		$cluster = matchWithCluster($connection, $userid);
		
		while ($jokeratingcount < $numjokestorate)
		{
			//Find the highest rated joke for this cluster			
			$recommendedjokeid = getHighestUnratedJokeIDInCluster($userid, $predictvectors[$cluster], $ratedjokes);
			
			//print "recommended jokeid: $recommendedjokeid\n";
			
			$jokerating = $dbjokeratings[$recommendedjokeid];
			
			//print "jokerating: $jokerating\n";
			
			if ($jokerating === false)
			{
				//print "NOT RATED...\n";
				continue 2;
			}
		
			$ratedjokes[] = $recommendedjokeid;
		
			$jokeratingsum += $jokerating;
			$jokeratingcount++;
		}

		$meanjokerating = ($jokeratingsum / $jokeratingcount);

		$totalmeanjokerating += $meanjokerating;
		$totalmeanjokeratingcount++;
		
		$usercount++;
		
		if ($insert)
		{
			$query = "INSERT INTO e2simulationusers (numjokestorate, userid) VALUES ({$numjokestorate}, {$userid})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	$meanmeanjokerating = ($totalmeanjokerating / $totalmeanjokeratingcount);
	
	print("\nAveraged Ratings of First $numjokestorate Jokes (Eigentaste 2.0): $meanmeanjokerating\n");
	print("User Count: $usercount\n\n");
	print("Completed: Eigentaste 2.0 Simulation\n");
}

function runSimulationE3($connection, $meanweight, $numjokestorate, $userquery, $insert)
{	
	global $predictjokes;
	
	$highestratedjokes = array();
	
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
	
	$totalmeanjokerating = 0;
	$totalmeanjokeratingcount = 0;
	
	$usercount = 0;
	
	$query = $userquery;
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];
		
		$dbjokeratings = array_fill(1, 100, false);
		getJokeRatings($connection, $userid, $dbjokeratings);
		
		$jokeratingsum = 0;
		$jokeratingcount = 0;
		
		$jokeclustersums = array();
		$jokeclustercounts = array();
		$ratedjokes = array(5, 7, 8, 13, 15, 16, 17, 18, 19, 20);
		
		$cluster = matchWithCluster($connection, $userid);
		
		$query = "SELECT AVG(clustermeans.meanrating), jokeclusters.jokecluster FROM clustermeans, jokeclusters WHERE clustermeans.cluster = jokeclusters.cluster AND clustermeans.jokeid = jokeclusters.jokeid AND clustermeans.cluster='{$cluster}' GROUP BY jokeclusters.jokecluster";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$jokeclustermean = $row[0];
			$jokecluster = $row[1];
			
			$jokeclustersums[$jokecluster] = ($jokeclustermean * $meanweight);
			$jokeclustercounts[$jokecluster] = $meanweight;
		}
		
		while ($jokeratingcount < $numjokestorate)
		{
			$recommendedjokeid = false;
			$emptyclusters = array();
			
			while ($recommendedjokeid === false)
			{
				//Find the joke cluster that the user currently prefers
				$favoritejokecluster = getFavoriteJokeCluster($jokeclustersums, $jokeclustercounts, $emptyclusters);
				
				//Find the highest rated joke within that joke cluster
				$recommendedjokeid = getHighestUnratedJokeIDInJokeCluster($userid, $favoritejokecluster, $highestratedjokes[$cluster], $ratedjokes);
				
				if ($recommendedjokeid === false)
				{
					$emptyclusters[] = $favoritejokecluster;
				}
			}
			
			/*test
			foreach ($jokeclustersums as $jokecluster => $jokeclustersum)
			{
				print "My mean for joke cluster $jokecluster: " . ($jokeclustersums[$jokecluster] / $jokeclustercounts[$jokecluster]) . "(" . $jokeclustersums[$jokecluster] . " \ " . $jokeclustercounts[$jokecluster] . ")\n";
			}
			
			print "Picked $favoritejokecluster\n";
			//end test*/
			
			//print "recommended jokeid: $recommendedjokeid\n";
			
			$jokerating = $dbjokeratings[$recommendedjokeid];
			
			//print "jokerating: $jokerating\n";
			
			if ($jokerating === false)
			{
				//print "NOT RATED...\n";
				continue 2;
			}
		
			$ratedjokes[] = $recommendedjokeid;
		
			$jokeclustersums[$favoritejokecluster] += $jokerating;
			$jokeclustercounts[$favoritejokecluster]++;
		
			$jokeratingsum += $jokerating;
			$jokeratingcount++;
		}

		$meanjokerating = ($jokeratingsum / $jokeratingcount);

		$totalmeanjokerating += $meanjokerating;
		$totalmeanjokeratingcount++;
		
		$usercount++;
		
		if ($insert)
		{
			$query = "INSERT INTO e3simulationusers (numjokestorate, userid) VALUES ({$numjokestorate}, {$userid})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	$meanmeanjokerating = ($totalmeanjokerating / $totalmeanjokeratingcount);
	
	print("\nAveraged Ratings of First $numjokestorate Jokes (Eigentaste 3.0) with Mean Weight of $meanweight: $meanmeanjokerating\n");
	print("User Count: $usercount\n\n");
	print("Completed: Eigentaste 3.0 Simulation\n");
}

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
	
	return $maxjokecluster;
}

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

function getHighestUnratedJokeIDInJokeCluster($userid, $jokecluster, &$highestratedjokesforcluster, &$ratedjokes)
{
	foreach ($highestratedjokesforcluster[$jokecluster] as $jokeid)
	{
		if (!in_array($jokeid, $ratedjokes))
			return $jokeid;
	}
	
	return false;
}

function getHighestUnratedJokeIDInCluster($userid, &$predictvectorforcluster, &$ratedjokes)
{
	foreach ($predictvectorforcluster as $jokeid)
	{
		if (!in_array($jokeid, $ratedjokes))
			return $jokeid;
	}
	
	return false;
}

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

function getRandomFloat($min, $max)
{
	return ($min + (lcg_value() * (abs($max - $min))));
}

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

/*
function inInitialUserGroup($connection, $userid)
{
	$query = "SELECT ininitialusergroup FROM users WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	$row = mysql_fetch_row($result);
	$ininitialusergroup = $row[0];
	
	if ($ininitialusergroup == 1)
		return true;
	
	return false;
}
*/
?>