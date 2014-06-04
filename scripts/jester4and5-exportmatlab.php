<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php require_once("../includes/generalfunctions.php") ?>
<?php
ini_set("memory_limit","10000M");

$sqldb = "jester4and5";

openConnection();
print("Running Jester 4.0 and 5.0 MATLAB Data Export\n\n");
exportMATLABData($connection);
print("\nJester 4.0 and 5.0 MATLAB Data Export Complete\n");
mysql_close($connection);
?>
<?php
function exportMATLABData($connection)
{
	global $numjokes, $numavailablejokes, $tablenames;
		
	$filename = "jokepoints.dat";
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
	
	$usersratednumjokes = array();
	
	//Important: Takes into account both Jester 4 and Jester 5 users, for more data
	$query = "SELECT userid FROM " . $tablenames["USERS"] . " WHERE numrated >= {$numavailablejokes} ORDER BY userid";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$userid = $row[0];
		$usersratednumjokes[] = $userid;
	}
	
	$jokeratings = array();

	for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
	{
		$jokeid = $jokeindex + 1;

		if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes
			continue;

		foreach ($usersratednumjokes as $userid)
		{
			$jokeratings[$jokeid][$userid] = isGetJokeRating($connection, $userid, $jokeid);
		}

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
?>