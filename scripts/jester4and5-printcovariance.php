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
setJester4TableNames($connection);
printCovarianceMatrix($connection);
setJester5TableNames($connection);
printCovarianceMatrix($connection);
mysql_close($connection);
?>
<?php
function printCovarianceMatrix($connection)
{
	global $tablenames;
	
	$covariance = array();

	$query = "SELECT row, col, covariance FROM " . $tablenames["COVARIANCE"] . " ORDER BY row, col";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$rowval = $row[0] + 1;
		$colval = $row[1] + 1;
		$covarianceval = $row[2];
		
		print "cov[$rowval, $colval] = " . $covarianceval . "\n";
	}
	
	print "\n";
}
?>