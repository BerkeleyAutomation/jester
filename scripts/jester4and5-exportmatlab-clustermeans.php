<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php require_once("../includes/generalfunctions.php") ?>
<?php
$sqldb = "jester4and5";

openConnection();
print("Running Jester 4.0 and 5.0 MATLAB Data Export (Cluster Means)\n\n");
exportMATLABData($connection);
print("\nJester 4.0 and 5.0 MATLAB Data Export Complete\n");
mysql_close($connection);
?>
<?php
function exportMATLABData($connection)
{
	global $numjokes, $tablenames;
		
	$filename = "jokepoints-clustermeans.dat";
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
	
	setJester5TableNames($connection);
	
	$clusters = array();
	
	//Important: Jester 5 clusters include the initial Jester 4 users
	$query = "SELECT DISTINCT cluster FROM " . $tablenames["CLUSTERS"] . " ORDER BY cluster";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$cluster = $row[0];
		$clusters[] = $cluster;
	}
	
	$jokeratings = array();

	for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
	{
		$jokeid = $jokeindex + 1;

		if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes
			continue;

		foreach ($clusters as $cluster)
		{
			$jokeratings[$jokeid][$cluster] = getUserClusterJokeRating($connection, $cluster, $jokeid);
		}

		$fileline = "";
		
		foreach ($jokeratings[$jokeid] as $cluster => $meanrating)
		{
			$fileline .= ($jokeratings[$jokeid][$cluster] . " ");
		}
		
		$fileline .= "\n";
		
		if (fwrite($handle, $fileline) === false)
		{
			echo "Cannot write to file ($filename)";
			exit;
		}
	}
	
	fclose($handle);
	
	print("Completed: MATLAB Data Export (Cluster Means)\n");
}

function getUserClusterJokeRating($connection, $cluster, $jokeid)
{
	global $tablenames;
	
	$query = "SELECT meanrating FROM " . $tablenames["CLUSTERMEANS"] . " WHERE cluster='{$cluster}' AND jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$row = mysql_fetch_row($result);
	$meanrating = $row[0];
	
	return $meanrating;
}
?>