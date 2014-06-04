<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php
function getUserID($connection, $email)
{
	global $tablenames;
	
	$query = "SELECT userid FROM " . $tablenames["USERS"] . " WHERE email='{$email}'";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$row = mysql_fetch_row($result);
	$userid = $row[0];

	return $userid;
}

function getUserEmail($connection, $userid)
{
	global $tablenames;
	
	$query = "SELECT email FROM " . $tablenames["USERS"] . " WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$row = mysql_fetch_row($result);
	$email = $row[0];

	return $email;
}

function isRegistered($connection, $userid)
{
	$email = getUserEmail($connection, $userid);
	
	if ($email != null)
		return true;
	
	return false;
}

function getLoggedInPhrase($connection, $userid)
{
	if (isRegistered($connection, $userid)) //User is registered
		$loggedinphrase = "logged in";
	else
		$loggedinphrase = "in a Jester session";
		
	return $loggedinphrase;
}

function initializeTableNamesInSession()
{
	global $connection;
	
	openConnection();

	$userid = $_SESSION["userid"];
	initializeTableNames($connection, $userid);

	mysql_close($connection);
}

function initializeTableNames($connection, $userid)
{	
	if (isUsingJester4($connection, $userid))
	{
		setJester4TableNames($connection);
		
		return true;
	}
	else if (isUsingJester5($connection, $userid) && !isUsingJester4($connection, $userid)) //The latter part is necessary to protect against old users logging in (who are registered as both usingjester4 and usingjester5, but will experience Jester 4.0)
	{
		setJester5TableNames($connection);

		return true;
	}
	else
		return false;
}

function setJester4TableNames($connection)
{
	global $tablenames;
	
	$tablenames["CLUSTERMEANS"] = "jester4_clustermeans";
	$tablenames["CLUSTERS"] = "jester4_clusters";
	$tablenames["COVARIANCE"] = "jester4_covariance";
	$tablenames["EIGENVALUES"] = "jester4_eigenvalues";
	$tablenames["EIGENVECTORS"] = "jester4_eigenvectors";
	$tablenames["EMPTYJOKECLUSTERS"] = "jester4_emptyjokeclusters";
	$tablenames["JOKECLUSTERRATINGS"] = "jester4_jokeclusterratings";
	$tablenames["JOKECLUSTERS"] = "jester4_jokeclusters";
	$tablenames["JOKES"] = "jokes";
	$tablenames["LAYER"] = "jester4_layer";
	$tablenames["PREDICTVECTORS"] = "jester4_predictvectors";
	$tablenames["PROJECTION"] = "jester4_projection";
	$tablenames["EPREDICTIONS"] = "jester4_epredictions";
	$tablenames["RATINGS"] = "ratings";
	$tablenames["RECOMMENDEDJOKES"] = "recommendedjokes";
	$tablenames["USERS"] = "users";
}

function setJester5TableNames($connection)
{
	global $tablenames;
	
	$tablenames["CLUSTERMEANS"] = "jester5_clustermeans";
	$tablenames["CLUSTERS"] = "jester5_clusters";
	$tablenames["COVARIANCE"] = "jester5_covariance";
	$tablenames["EIGENVALUES"] = "jester5_eigenvalues";
	$tablenames["EIGENVECTORS"] = "jester5_eigenvectors";
	$tablenames["EMPTYJOKECLUSTERS"] = "jester5_emptyjokeclusters";
	$tablenames["JOKECLUSTERRATINGS"] = "jester5_jokeclusterratings";
	$tablenames["JOKECLUSTERS"] = "jester5_jokeclusters";
	$tablenames["JOKES"] = "jokes";
	$tablenames["LAYER"] = "jester5_layer";
	$tablenames["PREDICTVECTORS"] = "jester5_predictvectors";
	$tablenames["PROJECTION"] = "jester5_projection";
	$tablenames["EPREDICTIONS"] = "jester5_epredictions";
	$tablenames["RATINGS"] = "ratings";
	$tablenames["RECOMMENDEDJOKES"] = "recommendedjokes";
	$tablenames["USERS"] = "users";
}

function isUsingJester4($connection, $userid)
{
	global $tablenames;
	
	$query = "SELECT usingjester4 FROM " . $tablenames["USERS"] . " WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$row = mysql_fetch_row($result);
	$usingjester4 = $row[0];
	
	if ($usingjester4 == 1)
		return true;
	
	return false;
}

function isUsingJester5($connection, $userid)
{
	global $tablenames;
	
	$query = "SELECT usingjester5 FROM " . $tablenames["USERS"] . " WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$row = mysql_fetch_row($result);
	$usingjester5 = $row[0];
	
	if ($usingjester5 == 1)
		return true;
	
	return false;
}
?>