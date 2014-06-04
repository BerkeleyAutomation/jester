<?php require_once("../includes/settings.php") ?>
<?php
//Begin Configurable Region
$sqlhost = "localhost";
$sqluser = "root";
$sqlpassword = "root";
$sqldb = "jester4and5";

//Tables (Initial Values)
$tablenames = array();
$tablenames["CLUSTERMEANS"] = "clustermeans";
$tablenames["CLUSTERS"] = "clusters";
$tablenames["COVARIANCE"] = "covariance";
$tablenames["EIGENVALUES"] = "eigenvalues";
$tablenames["EIGENVECTORS"] = "eigenvectors";
$tablenames["EMPTYJOKECLUSTERS"] = "emptyjokeclusters";
$tablenames["JOKECLUSTERRATINGS"] = "jokeclusterratings";
$tablenames["JOKECLUSTERS"] = "jokeclusters";
$tablenames["JOKES"] = "jokes";
$tablenames["LAYER"] = "layer";
$tablenames["PREDICTVECTORS"] = "predictvectors";
$tablenames["PROJECTION"] = "projection";
$tablenames["RATINGS"] = "ratings";
$tablenames["USERS"] = "users";

$webmaster = "jester.support@gmail.com";
$goldberg = "goldberg@berkeley.edu";
$confirmationbody = "Thank you for registering with Jester, the online joke recommender!" . "\n\n" . "To use Jester, go to:\n" . "http://eigentaste.berkeley.edu/user/login.php" . "\n\n" . "Please do not reply to this email.";
$fromemail = "confirmation@rieff.ieor.berkeley.edu";

$_POST["preinvalidqueryhtml"] = "<p>\nInvalid query: ";
$_POST["postinvalidqueryhtml"] = "\n</p>\n";
$_POST["preinvalidquerytext"] = "Invalid query: ";
$_POST["postinvalidquerytext"] = "\n\n";
$_POST["prenoconnectdbhtml"] = "<p>\nCould not connect to user database: ";
$_POST["postnoconnectdbhtml"] = "\n</p>\n";
$_POST["prenoconnectdbtext"] = "\nCould not connect to user database: ";
$_POST["postnoconnectdbtext"] = "\n\n";
$_POST["prenoselectdbhtml"] = "<p>\nCould not select user database: ";
$_POST["postnoselectdbhtml"] = "\n</p>\n";
$_POST["prenoselectdbtext"] = "\nCould not select user database: ";
$_POST["postnoselectdbtext"] = "\n\n";
$_POST["presystemerrorhtml"] = "<p>\nSystem error: ";
$_POST["postsystemerrorhtml"] = "\n</p>\n";
$_POST["presystemerrortext"] = "System error: ";
$_POST["postsystemerrortext"] = "\n\n";

//$numseedjokes = 5; //Number of seed prediction jokes that are given after the set prediction jokes are already rated
$numseedjokes = 0; //Number of seed prediction jokes that are given after the set prediction jokes are already rated
//$interleavedseedcount = 2; //Number of seed jokes that are interleaved with recommended jokes
$interleavedseedcount = 0; //Number of seed jokes that are interleaved with recommended jokes
$interleavedreccount = 5; //Number of recommended jokes that are interleaved with seed jokes
$threshold = 35; //People who have rated more jokes than this number are considered alpha users
$clusterlevels = 6;
$layersize = pow(2, $clusterlevels) - 1;
$eigenaxes = 2; //Not changeable without changing the way certain functions work
$minjokelength = 30;
$maxjokelength = 5000;
$minfeedbacklength = 10;
$maxfeedbacklength = 5000;
$maxjokerating = 10;
$minjokerating = -10;

$meanweight = 5;
$movingaveragesize = 5;
//End Configurable Region
?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php
openConnection();

$removedjokes = getRemovedJokes($connection);
$predictjokes = getPredictJokes($connection);
$numjokes = getNumJokes($connection);
$numavailablejokes = getNumAvailableJokes($connection);

mysql_close($connection);

for ($i = 0; $i < $numjokes; $i++)
	$ispredictor[$i] = false;
	
for ($i = 0; $i < count($predictjokes); $i++)
	$ispredictor[$predictjokes[$i] - 1] = true;

function getRemovedJokes($connection)
{
	global $tablenames;
	
	$query = "SELECT jokeid FROM " . $tablenames["JOKES"] . " WHERE removed=1";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	$removedjokes = array(); //Array must exist even if no rows are found
		
	while ($row = mysql_fetch_array($result))
	{
  		$removedjokes[] = $row[0];
	}

	return $removedjokes;
}

function getPredictJokes($connection)
{
	global $removedjokes, $tablenames;
	
	$query = "SELECT jokeid FROM " . $tablenames["JOKES"] . " WHERE predictor=1";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	$predictjokes = array(); //Array must exist even if no rows are found
		
	while ($row = mysql_fetch_array($result))
	{
		$jokeid = $row[0];

		if (!in_array($jokeid, $removedjokes)) //Does not allow a removed joke to be a part of the prediction set of jokes
  			$predictjokes[] = $jokeid;
	}
	
	if (empty($predictjokes))
		die($_POST["presystemerror"] . "The prediction set of jokes is empty. Please try again later." . $_POST["postsystemerror"]);

	return $predictjokes;
}

function getNumJokes($connection)
{
	global $tablenames;
	
	$query = "SELECT * FROM " . $tablenames["JOKES"];
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	$numjokes = mysql_num_rows($result);

	return $numjokes;
}

function getNumAvailableJokes($connection)
{
	global $tablenames;
	
	$query = "SELECT COUNT(*) FROM " . $tablenames["JOKES"] . " WHERE removed=0";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	$row = mysql_fetch_row($result);
	$numavailablejokes = $row[0];
	
	return $numavailablejokes;
}
?>