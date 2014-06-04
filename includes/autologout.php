<?php $_POST["errormessagetype"] = "html" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php

//Check if the system is down for maintenance

openConnection();

$query = "SELECT downformaintenance FROM maintenance";
$result = mysql_query($query, $connection);
if (!$result)
	die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

$row = mysql_fetch_row($result);
$maintenance = $row[0];

if ($maintenance)
{
	header("Location: ../user/maintenance.php");
	exit;
}

mysql_close($connection);
?>