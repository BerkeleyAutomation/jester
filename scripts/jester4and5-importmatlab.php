<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php
$sqldb = "jester4and5";

openConnection();
print("Running Jester 4.0 and 5.0 MATLAB Results Import\n\n");
importMATLABResults($connection);
print("\nJester 4.0 and 5.0 MATLAB Results Import Complete\n");
mysql_close($connection);
?>
<?php
function importMATLABResults($connection)
{
	global $numjokes, $tablenames;
	
	setJester5TableNames($connection);
	
	clearTable($connection, $tablenames["JOKECLUSTERS"]);
	
	$filename = "jokeclusterindices.dat";
		
	if (!$handle = fopen($filename, "r"))
	{
		echo "Cannot open file ($filename)";
		exit;
	}
	
	$filecontents = fread($handle, filesize($filename));
	$jokelines = explode("\n", trim($filecontents));
	
	$lineindex = 0;
	
	for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
	{
		$jokeid = $jokeindex + 1;

		if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes
			continue;
				
		$jokecluster = $jokelines[$lineindex];
		
		$query = "INSERT INTO " . $tablenames["JOKECLUSTERS"] . " (jokeid, jokecluster) VALUES ({$jokeid}, {$jokecluster})";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
		$lineindex++;
	}
	
	fclose($handle);
	
	print("Completed: MATLAB Results Import\n");
}
?>