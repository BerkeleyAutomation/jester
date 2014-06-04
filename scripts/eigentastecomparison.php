<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php
$sqldb = "jester4and5"; //Set this to a different database (not "jester4and5") for testing

/* IMPORTANT NOTE: Do not run the offlinescripts on this data, because the only way I know each user's cluster is by the dot product. If the offlinescripts are run,
the clusters will change! */

/* Testing

Recommended Joke 1:

E2: SELECT AVG(jokerating) FROM ratings WHERE jokeratingid=9 AND userid IN (SELECT userid FROM `users` WHERE userid > 3538 AND usingjester5=0 AND usingjester4=1 AND numrated >= 33)
3.8147727272727
110

E5: SELECT AVG(jokerating) FROM ratings WHERE jokeratingid=9 AND userid IN (SELECT userid FROM `users` WHERE userid > 3538 AND usingjester5=1 AND usingjester4=0 AND numrated >= 33)
2.2457524271845
103

Recommended Joke 12:

E2: SELECT AVG(jokerating) FROM ratings WHERE jokeratingid=20 AND userid IN (SELECT userid FROM `users` WHERE userid > 3538 AND usingjester5=0 AND usingjester4=1 AND numrated >= 33)
2.7894886363636
110

E5: SELECT AVG(jokerating) FROM ratings WHERE jokeratingid=20 AND userid IN (SELECT userid FROM `users` WHERE userid > 3538 AND usingjester5=1 AND usingjester4=0 AND numrated >= 33)
2.8552791262136
103

*/

ini_set("memory_limit","10000M");

define("FIRSTUSERID", 3538);
define("NUMRECOMMENDEDANDRATEDJOKES", 30);

openConnection();
print("Running Comparison\n\n");
runComparison($connection);
print("\nComparison Complete\n");
mysql_close($connection);
?>
<?php
function runComparison($connection)
{
	global $predictjokes;

	$plotarray = array();
	$plotnamearray = array();

	$xaxisplot = array();
	for ($i = 1; $i <= NUMRECOMMENDEDANDRATEDJOKES; $i++)
	{
		$xaxisplot[] = $i;
	}
	$xaxisplotname = "xaxisplot";

	$pte2plot = array();
	$pte2plotname = "pte2plot";
	$ptprede2plot = array();
	$ptprede2plotname = "ptprede2plot";
	
	$pte5plot = array();
	$pte5plotname = "pte5plot";
	$ptprede5plot = array();
	$ptprede5plotname = "ptprede5plot";

	runComparisonOnBoth($connection, $pte2plot, $ptprede2plot, $pte5plot, $ptprede5plot);

	$plotarray[] = getPlotString($xaxisplot);
	$plotnamearray[] = $xaxisplotname;

	$plotarray[] = getPlotString($pte2plot);
	$plotnamearray[] = $pte2plotname;
	$plotarray[] = getPlotString($ptprede2plot);
	$plotnamearray[] = $ptprede2plotname;
	
	$plotarray[] = getPlotString($pte5plot);
	$plotnamearray[] = $pte5plotname;
	$plotarray[] = getPlotString($ptprede5plot);
	$plotnamearray[] = $ptprede5plotname;
	
	$plotarray[] = getPlotString(diffArray($pte5plot, $pte2plot));
	$plotnamearray[] = "diff_pte5plot_pte2plot";
	
	$plotarray[] = getPlotString(diffArray($pte2plot, $ptprede2plot));
	$plotnamearray[] = "diff_pte2plot_ptprede2plot";
	
	$plotarray[] = getPlotString(diffArray($pte5plot, $ptprede5plot));
	$plotnamearray[] = "diff_pte5plot_ptprede5plot";

	foreach ($plotarray as $key => $plot)
	{
		$plotname = $plotnamearray[$key];
		$filename = "eigentastecomparisonplots/" . $plotname . ".dat";
	
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
	
	print("Completed: Running Comparison\n");
}

function runComparisonOnBoth($connection, &$pte2plot, &$ptprede2plot, &$pte5plot, &$ptprede5plot)
{	
	setJester4TableNames($connection);
	$usingjester4 = 1;
	$usingjester5 = 0;
	calculateEigentastePredictionsForClusters($connection);
	$usercount = runComparisonOnce($connection, $usingjester4, $usingjester5, $pte2plot, $ptprede2plot);
	print("Eigentaste 2.0 Comparison: $usercount users\n");
	
	setJester5TableNames($connection);
	$usingjester4 = 0;
	$usingjester5 = 1;
	calculateEigentastePredictionsForClusters($connection);
	$usercount = runComparisonOnce($connection, $usingjester4, $usingjester5, $pte5plot, $ptprede5plot);
	print("Eigentaste 5.0 Comparison: $usercount users\n");
}

function runComparisonOnce($connection, $usingjester4, $usingjester5, &$ptplot, &$ptpredplot)
{		
	global $tablenames, $predictjokes, $numjokes;
	
	$pttotaljokerating = array();
	$pttotaljokeratingcount = array();
	$pttotalpredictedrating = array();
	$pttotalpredictedratingcount = array();
	
	for ($recommendedjokenum = 1; $recommendedjokenum <= NUMRECOMMENDEDANDRATEDJOKES; $recommendedjokenum++)
	{
		$pttotaljokerating[$recommendedjokenum] = 0;
		$pttotaljokeratingcount[$recommendedjokenum] = 0;
		$pttotalpredictedrating[$recommendedjokenum] = 0;
		$pttotalpredictedratingcount[$recommendedjokenum] = 0;
	}
	
	$usercount = 0;
	
	$query = "SELECT userid FROM " . $tablenames["USERS"] . " WHERE usingjester4={$usingjester4} AND usingjester5={$usingjester5} AND userid >= " . FIRSTUSERID;
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];
		
		//Check if user has rated enough recommended jokes
		
		$query = "SELECT COUNT(*) FROM " . $tablenames["RECOMMENDEDJOKES"] . " WHERE userid={$userid} AND jokeid IN (SELECT jokeid FROM " . $tablenames["RATINGS"] . " WHERE userid={$userid})";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
		$row = mysql_fetch_row($resultinner);
		$ratedandrecommendedjokecount = $row[0];
		
		//Must have been recommended at least NUMRECOMMENDEDANDRATEDJOKES jokes, +1 (the last joke recommended, which was not rated)
		if ($ratedandrecommendedjokecount < NUMRECOMMENDEDANDRATEDJOKES)
			continue;
		
		$sortedrecommendedjokes = array();
		sortedRecommendedJokes($connection, $userid, $sortedrecommendedjokes);
		
		//Get the user's actual ratings for all the jokes
		$jokeratings = array_fill(1, $numjokes, false);
		getJokeRatings($connection, $userid, $jokeratings);
		
		//Get the user's predicted ratings for all the jokes
		$predictedjokeratings = array_fill(1, $numjokes, false);
		getPredictedJokeRatings($connection, $userid, $predictedjokeratings);

		$ptjokerating = array();
		$ptpredictedrating = array();
		
		for ($recommendedjokenum = 1; $recommendedjokenum <= NUMRECOMMENDEDANDRATEDJOKES; $recommendedjokenum++)
		{
			$ptjokerating[$recommendedjokenum] = false;
			$ptpredictedrating[$recommendedjokenum] = false;
		}
		
		$sortedrecommendedjokesindex = 0;
		
		for ($recommendedjokenum = 1; $recommendedjokenum <= NUMRECOMMENDEDANDRATEDJOKES; $recommendedjokenum++)
		{
			if ($sortedrecommendedjokesindex < count($sortedrecommendedjokes))
			{
				$recommendedjokeid = $sortedrecommendedjokes[$sortedrecommendedjokesindex];
				
				$jokerating = $jokeratings[$recommendedjokeid];
				$predictedrating = $predictedjokeratings[$recommendedjokeid];

				$ptjokerating[$recommendedjokenum] = $jokerating;
				$ptpredictedrating[$recommendedjokenum] = $predictedrating;
				
				$sortedrecommendedjokesindex++;
			}
			else
				exit("Error: user has not been recommended one of the " . NUMRECOMMENDEDANDRATEDJOKES . " recommended jokes.");
		}
		
		for ($recommendedjokenum = 1; $recommendedjokenum <= NUMRECOMMENDEDANDRATEDJOKES; $recommendedjokenum++)
		{
			if ($ptjokerating[$recommendedjokenum] === false)
				exit("Error: user has not been recommended one of the " . NUMRECOMMENDEDANDRATEDJOKES . " recommended jokes.");
				
			$pttotaljokerating[$recommendedjokenum] += $ptjokerating[$recommendedjokenum];
			$pttotaljokeratingcount[$recommendedjokenum]++;
			
			if ($ptpredictedrating[$recommendedjokenum] === false)
				exit("Error: user has not been predicted one of the " . NUMRECOMMENDEDANDRATEDJOKES . " recommended jokes.");

			$pttotalpredictedrating[$recommendedjokenum] += $ptpredictedrating[$recommendedjokenum];
			$pttotalpredictedratingcount[$recommendedjokenum]++;
		}
		
		$usercount++;
	}
	
	for ($recommendedjokenum = 1; $recommendedjokenum <= NUMRECOMMENDEDANDRATEDJOKES; $recommendedjokenum++)
	{
		$ptmeanjokerating = ($pttotaljokerating[$recommendedjokenum] / $pttotaljokeratingcount[$recommendedjokenum]);
		$ptmeanpredictedrating = ($pttotalpredictedrating[$recommendedjokenum] / $pttotalpredictedratingcount[$recommendedjokenum]);
		
		$ptplot[$recommendedjokenum] = $ptmeanjokerating;
		$ptpredplot[$recommendedjokenum] = $ptmeanpredictedrating;
	}
	
	return $usercount;
}

function diffArray(&$plotarray1, &$plotarray2)
{
	$plotarray1minus2 = array();
	
	if (count($plotarray1) != count($plotarray2))
	{
		print "Error: cannot take the difference of arrays with unequal size!\n";
		exit;
	}
	
	foreach ($plotarray1 as $recommendedjokenum => $plotelement)
	{
		$plotarray1minus2[$recommendedjokenum] = ($plotarray1[$recommendedjokenum] - $plotarray2[$recommendedjokenum]);
	}
	
	return $plotarray1minus2;
}

function getPlotString($plotarray)
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

function sortedRecommendedJokes($connection, $userid, &$sortedrecommendedjokes)
{
	global $tablenames;
	
	$query = "SELECT jokeid FROM " . $tablenames["RATINGS"] . " WHERE userid={$userid} AND jokeid IN (SELECT jokeid FROM " . $tablenames["RECOMMENDEDJOKES"] . " WHERE userid={$userid}) ORDER BY jokeratingid";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$jokeid = $row[0];
		
		$sortedrecommendedjokes[] = $jokeid;
	}
}

function getJokeRatings($connection, $userid, &$jokeratings)
{
	global $tablenames;
	
	$query = "SELECT jokeid, jokerating FROM " . $tablenames["RATINGS"] . " WHERE userid={$userid}";
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

function getPredictedJokeRatings($connection, $userid, &$predictedjokeratings)
{	
	global $tablenames;
	
	$cluster = matchWithCluster($connection, $userid);
	
	$query = "SELECT jokeid, prediction FROM " . $tablenames["EPREDICTIONS"] . " WHERE cluster='{$cluster}'";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$jokeid = $row[0];
		$prediction = $row[1];
		
		$predictedjokeratings[$jokeid] = $prediction;
	}
}

//Calculate Eigentaste Predictions for Clusters
//Note: This only works when the users who are being predicted for are not clustered, too (if that's the case, knowledge is being used when it shouldn't be)
function calculateEigentastePredictionsForClusters($connection)
{
	global $tablenames, $numjokes;
	
	clearTable($connection, $tablenames["EPREDICTIONS"]);
	
	$predictions = array();
	
	$query = "SELECT DISTINCT cluster FROM " . $tablenames["CLUSTERS"] . " ORDER BY cluster";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		
		$predictions[$cluster] = array_fill(1, $numjokes, false);
	
		$usersincluster = array();
	
		$query = "SELECT userid FROM " . $tablenames["CLUSTERS"] . " WHERE cluster='{$cluster}' ORDER BY userid";
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

				$query = "INSERT INTO " . $tablenames["EPREDICTIONS"] . " (cluster, jokeid, prediction) VALUES ('{$cluster}', {$jokeid}, {$prediction})";
				$resultinnerinner = mysql_query($query, $connection);
				if (!$resultinnerinner)
					die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			}
		}
	}
}
?>