<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php
openConnection();

$tables = array();
$tables[] = "feedback";
$tables[] = "jester4_clustermeans";
$tables[] = "jester4_clusters";
$tables[] = "jester4_covariance";
$tables[] = "jester4_eigenvalues";
$tables[] = "jester4_eigenvectors";
$tables[] = "jester4_emptyjokeclusters";
$tables[] = "jester4_epredictions";
$tables[] = "jester4_jokeclusterratings";
$tables[] = "jester4_jokeclusters";
$tables[] = "jester4_layer";
$tables[] = "jester4_predictvectors";
$tables[] = "jester4_projection";
$tables[] = "jester5_clustermeans";
$tables[] = "jester5_clusters";
$tables[] = "jester5_covariance";
$tables[] = "jester5_eigenvalues";
$tables[] = "jester5_eigenvectors";
$tables[] = "jester5_emptyjokeclusters";
$tables[] = "jester5_jokeclusterratings";
$tables[] = "jester5_predictvectors";
$tables[] = "jester5_projection";
$tables[] = "jokes";
$tables[] = "maintenance";
$tables[] = "ratings";
$tables[] = "recommendedjokes";
$tables[] = "suggestedjokes";
$tables[] = "users";

foreach ($tables as $table) {
	$query = "ANALYZE TABLE {$table}";	
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	$row = mysql_fetch_row($result);
	print_r($row);
}

mysql_close($connection);
?>