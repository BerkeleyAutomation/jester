<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require_once("../includes/generalfunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php
function recommendJoke($connection, $userid)
{
	global $predictjokes, $eigenaxes, $interleavedseedcount, $interleavedreccount, $tablenames;
	
	if ($_SESSION["seedcount"] >= $interleavedseedcount)
	{
		$_SESSION["seedcount"] = 0;
		$_SESSION["recommendcount"] = 0;
		$_SESSION["recommendcount"]++;
	}
	else if ($_SESSION["recommendcount"] >= $interleavedreccount)
	{
		$_SESSION["seedcount"]++;
		
		//Construct details string
		//Warning: Always ensure that getFavoriteJokeCluster does not change any tables (i.e. the emptyjokeclusters table, etc.)
		$_SESSION["detailsstring"] = "<p>Displaying seed joke...</p>";
		
		if (isUsingJester5($connection, $userid) && !isUsingJester4($connection, $userid))
		{
			$detailsjokeclustermeans = array();
			$detailsjokeclusterratings = array();
			$detailsemptyclusters = array();
			$favoritejokecluster = getFavoriteJokeCluster($connection, $detailsjokeclustermeans, $detailsjokeclusterratings, $detailsemptyclusters, $userid);
			
			$_SESSION["detailsstring"] .= getE5DetailsString($detailsjokeclustermeans, $detailsjokeclusterratings, $detailsemptyclusters, $favoritejokecluster, false);
		}
		//End construct details string
		
		return getSeedJokeID($connection, $userid);
	}
	else
	{
		$_SESSION["recommendcount"]++;
	}
	
	//Select the first two eigenvectors
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($axis = 0; $axis < $eigenaxes; $axis++)
		{
			$query = "SELECT eigenvectorelement FROM " . $tablenames["EIGENVECTORS"] . " WHERE row={$i} AND col={$axis}";
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
	
	$query = "SELECT layervalue, layerindex FROM " . $tablenames["LAYER"];
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
	
	if (isUsingJester4($connection, $userid))
	{
		//Get the prediction vector for the cluster

		$query = "SELECT jokeid, rank FROM " . $tablenames["PREDICTVECTORS"] . " WHERE cluster='{$cluster}'";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		//This cluster does not exist yet, but because of this user, it will exist after the offline scripts are run again
		if (mysql_num_rows($result) == 0)
		{
			$_SESSION["recommendcount"] = 0; //No more jokes to recommend

			return 0; 
		}

		while ($row = mysql_fetch_array($result))
		{		
			$jokeid = $row[0];
			$rank = $row[1];

			$predictvector[$rank] = $jokeid;
		}
		
		//Recommend joke
		
		for ($i = 0; $i < count($predictvector); $i++)
		{
			$jokeid = $predictvector[$i];
		
			if (!isRated($connection, $userid, $jokeid) && !isRemoved($connection, $jokeid))
			{
				//Construct details string
				$_SESSION["detailsstring"] = "<p>Rank of Joke $jokeid by Cluster $cluster: $i</p>";
				//End construct details string
				
				//Insert into recommendedjokes
				$query = "INSERT INTO recommendedjokes (userid, jokeid) VALUES ({$userid}, {$jokeid})";
				$resultinner = mysql_query($query, $connection);
				if (!$resultinner)
					die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
				//End insert
				
				return $jokeid;
			}
		}
	}
	else if (isUsingJester5($connection, $userid) && !isUsingJester4($connection, $userid)) //The latter part is necessary to protect against old users logging in (who are registered as both usingjester4 and usingjester5, but will experience Jester 4.0)
	{
		//Recommend joke
		
		$jokeid = false;
		
		while ($jokeid === false)
		{
			if (allJokeClustersEmpty($connection, $userid))
				break;
				
			$detailsjokeclustermeans = array();
			$detailsjokeclusterratings = array();
			$detailsemptyclusters = array();
			$detailsmeanrating = 0;
			
			//Find the joke cluster that the user currently prefers
			$favoritejokecluster = getFavoriteJokeCluster($connection, $detailsjokeclustermeans, $detailsjokeclusterratings, $detailsemptyclusters, $userid);
			
			//Find the highest available joke within that joke cluster
			$jokeid = getHighestAvailableJokeIDInJokeCluster($connection, $userid, $favoritejokecluster, $detailsmeanrating);
			
			if ($favoritejokecluster === false)
				die($_POST["presystemerror"] . "Favorite joke cluster cannot be selected. Please try again later." . $_POST["postsystemerror"]);
		
			if ($jokeid === false)
 				addAsEmptyJokeCluster($connection, $userid, $favoritejokecluster);
		}
		
		//Construct details string
		$cluster = matchWithCluster($connection, $userid);
		
		$_SESSION["detailsstring"] = "";
		$_SESSION["detailsstring"] .= "<p>Chosen Joke Cluster: $favoritejokecluster<br />";
		$_SESSION["detailsstring"] .= "Mean Rating for Joke $jokeid by Cluster $cluster: " . sprintf("%.3f", $detailsmeanrating) . "</p>";
		
		$_SESSION["detailsstring"] .= getE5DetailsString($detailsjokeclustermeans, $detailsjokeclusterratings, $detailsemptyclusters, $favoritejokecluster, true);
		//End construct details string
		
		if (!($jokeid === false))
		{
			//Insert into recommendedjokes
			$query = "INSERT INTO recommendedjokes (userid, jokeid) VALUES ({$userid}, {$jokeid})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			//End insert
			
			return $jokeid;
		}
	}
	
	$_SESSION["recommendcount"] = 0; //No more jokes to recommend
	
	return 0;
}

function getE5DetailsString(&$detailsjokeclustermeans, &$detailsjokeclusterratings, &$detailsemptyclusters, $highlightedjokecluster, $boldon)
{
	$detailsstring = "";
	$detailsstring .= "<ul>";
	
	foreach ($detailsjokeclustermeans as $jokecluster => $detailsjokeclustermean)
	{
		if (($jokecluster == $highlightedjokecluster) && $boldon)
			$detailsstring .= "<b>";
			
		if (in_array($jokecluster, $detailsemptyclusters))
			$detailsstring .= "<i>";
			
		$detailsstring .= "<li>Joke Cluster $jokecluster: " . sprintf("%.3f", $detailsjokeclustermean) . " ";
		$firstelem = true;
		foreach (array_reverse($detailsjokeclusterratings[$jokecluster]) as $detailsjokeclusterrating)
		{
			if ($firstelem)
			{
				$detailsstring .= "(";
				$firstelem = false;
			}
			else
				$detailsstring .= ", ";
				
			$detailsstring .= sprintf("%.3f", $detailsjokeclusterrating);
		}
		$detailsstring .= ")</li>";
	
		if (($jokecluster == $highlightedjokecluster) && $boldon)
			$detailsstring .= "</b>";
			
		if (in_array($jokecluster, $detailsemptyclusters))
			$detailsstring .= "</i>";
	}
	$detailsstring .= "</ul>";
	
	return $detailsstring;
}

function matchWithCluster($connection, $userid)
{
	global $predictjokes, $eigenaxes, $tablenames;
	
	//Select the first two eigenvectors
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($axis = 0; $axis < $eigenaxes; $axis++)
		{
			$query = "SELECT eigenvectorelement FROM " . $tablenames["EIGENVECTORS"] . " WHERE row={$i} AND col={$axis}";
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
	
	$query = "SELECT layervalue, layerindex FROM " . $tablenames["LAYER"];
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

function getFavoriteJokeCluster($connection, &$detailsjokeclustermeans, &$detailsjokeclusterratings, &$detailsemptyclusters, $userid)
{
	global $tablenames, $movingaveragesize;
	
	$maxjokeclustermean = null;
	$maxjokecluster = null;
	
	$query = "SELECT jokecluster, jokeratingid, jokerating FROM " . $tablenames["JOKECLUSTERRATINGS"] . " WHERE userid={$userid} ORDER BY jokecluster, jokeratingid";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$jokecluster = $row[0];
		$jokeratingid = $row[1];
		$jokerating = $row[2];
		
		$jokeclusterratings[$jokecluster][] = $jokerating;
	}
	
	foreach ($jokeclusterratings as $jokecluster => $jokeclusterratingsforjokecluster)
	{	
		$jokeclustersum = 0;
		$jokeclustercount = 0;
		
		$reversedjokeclusterratingsforjokecluster = array_reverse($jokeclusterratingsforjokecluster);
		
		foreach ($reversedjokeclusterratingsforjokecluster as $jokeclusterrating)
		{
			if ($jokeclustercount >= $movingaveragesize)
				break;
				
			$jokeclustersum += $jokeclusterrating;
			$jokeclustercount++;
			
			$detailsjokeclusterratings[$jokecluster][] = $jokeclusterrating;
		}
		
		if ($jokeclustercount == 0)
		{
			die($_POST["presystemerror"] . "Cannot take a moving average of zero elements. Please try again later." . $_POST["postsystemerror"]);
		}
		
		$jokeclustermean = ($jokeclustersum / $jokeclustercount);
		
		$detailsjokeclustermeans[$jokecluster] = $jokeclustermean;
			
		if ((($maxjokeclustermean == null) || ($jokeclustermean > $maxjokeclustermean)) && !isEmptyJokeCluster($connection, $userid, $jokecluster))
		{
			$maxjokeclustermean = $jokeclustermean;
			$maxjokecluster = $jokecluster;
		}
		else if (isEmptyJokeCluster($connection, $userid, $jokecluster))
		{
			$detailsemptyclusters[] = $jokecluster;
		}
	}
	
	if ($maxjokecluster == null)
		return false;
	
	return $maxjokecluster;
}

function getHighestAvailableJokeIDInJokeCluster($connection, $userid, $jokecluster, &$detailsmeanrating)
{	
	global $tablenames;
	
	$cluster = matchWithCluster($connection, $userid);
	
	$query = "SELECT " . $tablenames["CLUSTERMEANS"] . ".jokeid, " . $tablenames["CLUSTERMEANS"] . ".meanrating FROM " . $tablenames["CLUSTERMEANS"] . ", " . $tablenames["JOKECLUSTERS"] . " WHERE " . $tablenames["CLUSTERMEANS"] . ".jokeid = " . $tablenames["JOKECLUSTERS"] . ".jokeid AND " . $tablenames["CLUSTERMEANS"] . ".cluster = '{$cluster}' AND " . $tablenames["JOKECLUSTERS"] . ".jokecluster = {$jokecluster} ORDER BY " . $tablenames["CLUSTERMEANS"] . ".meanrating DESC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	while ($row = mysql_fetch_array($result))
	{
		$jokeid = $row[0];
		$tempmeanrating = $row[1];
		
		if (!isRated($connection, $userid, $jokeid) && !isRemoved($connection, $jokeid))
		{
			$detailsmeanrating = $tempmeanrating;
			return $jokeid;
		}
	}
	
	return false;
}

function addAsEmptyJokeCluster($connection, $userid, $jokecluster)
{
	global $tablenames;
	
	if (!isEmptyJokeCluster($connection, $userid, $jokecluster))
	{
		$query = "INSERT INTO " . $tablenames["EMPTYJOKECLUSTERS"] . " (userid, jokecluster) VALUES ({$userid}, {$jokecluster})";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	else
		die($_POST["presystemerror"] . "Attempted to re-add joke cluster as empty. Please try again later." . $_POST["postsystemerror"]);
}

function isEmptyJokeCluster($connection, $userid, $jokecluster)
{
	global $tablenames;
	
	$query = "SELECT jokecluster FROM " . $tablenames["EMPTYJOKECLUSTERS"] . " WHERE userid={$userid} AND jokecluster={$jokecluster}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	if (mysql_num_rows($result) == 0)
	{
		return false;
	}
	else if (mysql_num_rows($result) > 1)
	{
		die($_POST["presystemerror"] . "Repeated joke cluster. Please try again later." . $_POST["postsystemerror"]);
	}
	else
	{
		return true;
	}
}

function allJokeClustersEmpty($connection, $userid)
{
	global $tablenames;
	
	$query = "SELECT COUNT(jokecluster) FROM " . $tablenames["EMPTYJOKECLUSTERS"] . " WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$row = mysql_fetch_row($result);
	$emptyjokeclustercount = $row[0];
	
	$query = "SELECT COUNT(DISTINCT jokecluster) FROM " . $tablenames["JOKECLUSTERS"];
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	$row = mysql_fetch_row($result);
	$jokeclustercount = $row[0];
	
	if ($emptyjokeclustercount == $jokeclustercount)
		return true;
		
	return false;
}

function initializeJokeClusterRatings($connection, $userid)
{
	global $meanweight, $tablenames;
	
	$cluster = matchWithCluster($connection, $userid);
	
	$query = "SELECT AVG(" . $tablenames["CLUSTERMEANS"] . ".meanrating), " . $tablenames["JOKECLUSTERS"] . ".jokecluster FROM " . $tablenames["CLUSTERMEANS"] . ", " . $tablenames["JOKECLUSTERS"] . " WHERE " . $tablenames["CLUSTERMEANS"] . ".jokeid = " . $tablenames["JOKECLUSTERS"] . ".jokeid AND " . $tablenames["CLUSTERMEANS"] . ".cluster='{$cluster}' GROUP BY " . $tablenames["JOKECLUSTERS"] . ".jokecluster";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$jokeclustermean = $row[0];
		$jokecluster = $row[1];
		
		$query = "DELETE FROM " . $tablenames["JOKECLUSTERRATINGS"] . " WHERE userid={$userid} AND jokecluster={$jokecluster}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
		$jokeclusterratings = array();
		$numratedjokesinjokecluster = 0;
		
		$query = "SELECT jokeid, jokeratingid, jokerating FROM " . $tablenames["RATINGS"] . " WHERE userid={$userid} ORDER BY jokeratingid";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($resultinner))
		{
			$jokeid = $row[0];
			$jokeratingid = $row[1];
			$jokerating = $row[2];
			
			$jokeclusterofjokeid = getJokeCluster($connection, $jokeid);
			
			if ($jokeclusterofjokeid == $jokecluster)
			{
				$jokeclusterratings[] = $jokerating;
				$numratedjokesinjokecluster++;
			}
		}

		//If the array was (0 => 5.5, 1 => 2.5, 2 => 3.0), it is now (0 => 3.0, 1 => 2.5, 2 => 5.5)
		$jokeclusterratings = array_reverse($jokeclusterratings);

		$remainingratings = ($meanweight - $numratedjokesinjokecluster);
		
		for ($remainingratingscount = 1; $remainingratingscount <= $remainingratings; $remainingratingscount++)
		{
			$jokeclusterratings[] = $jokeclustermean;
		}

		//If the array was (0 => 3.0, 1 => 2.5, 2 => 5.5, 3 => [meanvalue]), it is now (0 => [meanvalue], 1 => 5.5, 2 => 2.5, 3 => 3.0)
		$jokeclusterratings = array_reverse($jokeclusterratings);
		
		//Make sure that $jokeclusterratings only has length $meanweight
		$jokeclusterratings = array_splice($jokeclusterratings, ($meanweight * -1));
			
		foreach ($jokeclusterratings as $jokeratingid => $jokerating)
		{
			$query = "INSERT INTO " . $tablenames["JOKECLUSTERRATINGS"] . " (userid, jokecluster, jokeratingid, jokerating) VALUES ({$userid}, {$jokecluster}, {$jokeratingid}, {$jokerating})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
		
		//Adds a joke cluster as an empty joke cluster if it is already exhaused prior to recommendation
		
		$detailsmeanrating = 0;
		
		if (getHighestAvailableJokeIDInJokeCluster($connection, $userid, $jokecluster, $detailsmeanrating) === false)
 			addAsEmptyJokeCluster($connection, $userid, $jokecluster);
	}
}

function setJokeClusterRating($connection, $userid, $jokeid, $jokerating)
{
	global $tablenames;
	
	$jokecluster = getJokeCluster($connection, $jokeid);
	
	$query = "SELECT MAX(jokeratingid) FROM " . $tablenames["JOKECLUSTERRATINGS"] . " WHERE userid={$userid} AND jokecluster={$jokecluster}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	$row = mysql_fetch_row($result);
	$maxjokeratingid = $row[0];
	
	$jokeratingid = $maxjokeratingid + 1;
	
	$query = "INSERT INTO " . $tablenames["JOKECLUSTERRATINGS"] . " (userid, jokecluster, jokeratingid, jokerating) VALUES ({$userid}, {$jokecluster}, {$jokeratingid}, {$jokerating})";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function getJokeCluster($connection, $jokeid)
{
	global $tablenames;
	
	$query = "SELECT jokecluster FROM " . $tablenames["JOKECLUSTERS"] . " WHERE jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	$row = mysql_fetch_row($result);
	$jokeclusterofjokeid = $row[0];
	
	return $jokeclusterofjokeid;
}

function isRated($connection, $userid, $jokeid)
{
	global $tablenames;
	
	$query = "SELECT jokerating FROM " . $tablenames["RATINGS"] . " WHERE userid={$userid} AND jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	if (mysql_num_rows($result) == 0)
	{
		return false;
	}
	else if (mysql_num_rows($result) > 1)
	{
		die($_POST["presystemerror"] . "Repeated rating in database. Please try again later." . $_POST["postsystemerror"]);
	}
	else
	{
		return true;
	}
}

function isRemoved($connection, $jokeid)
{
	global $removedjokes;
	
	if (in_array($jokeid, $removedjokes))
		return true;
	
	return false;
}

function getJokeRating($connection, $userid, $jokeid)
{
	global $tablenames;
	
	$query = "SELECT jokerating FROM " . $tablenames["RATINGS"] . " WHERE userid={$userid} AND jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	if (mysql_num_rows($result) == 0)
	{
		die($_POST["presystemerror"] . "Rating not found. Please try again later." . $_POST["postsystemerror"]);
	}
	else if (mysql_num_rows($result) > 1)
	{
		die($_POST["presystemerror"] . "Repeated rating in database. Please try again later." . $_POST["postsystemerror"]);
	}
	else
	{
		$row = mysql_fetch_row($result);
		$jokerating = $row[0];
		
		return $jokerating;
	}
}

function isGetJokeRating($connection, $userid, $jokeid)
{
	$query = "SELECT jokerating FROM ratings WHERE userid={$userid} AND jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	if (mysql_num_rows($result) == 0)
	{
		return false;
	}
	else if (mysql_num_rows($result) > 1)
	{
		die($_POST["presystemerror"] . "Repeated rating in database. Please try again later." . $_POST["postsystemerror"]);
	}
	else
	{
		$row = mysql_fetch_row($result);
		$jokerating = $row[0];
		
		return $jokerating;
	}
}

function getNumRated($connection, $userid)
{
	global $tablenames;
	
	$query = "SELECT numrated FROM " . $tablenames["USERS"] . " WHERE userid={$userid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$row = mysql_fetch_row($result);
	$numrated = $row[0];
	
	return $numrated;
}

function getMeanRating($connection, $jokeid)
{
	global $tablenames;
	
	$ratingcount = 0;
	$ratingsum = 0;
	
	$query = "SELECT jokerating FROM " . $tablenames["RATINGS"] . " WHERE jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	while ($row = mysql_fetch_array($result))
	{
		$jokerating = $row[0];
		
		$ratingsum += $jokerating;
		$ratingcount++;
	}
	
	if ($ratingcount == 0)
		$meanrating = 0;
	else
		$meanrating = $ratingsum / $ratingcount;
	
	return $meanrating;
}

function getRatingVariance($connection, $jokeid)
{
	global $tablenames;
	
	$meanrating = getMeanRating($connection, $jokeid);
	
	$diffsqtotal = 0;
	$diffsqcount = 0;
	
	$query = "SELECT jokerating FROM " . $tablenames["RATINGS"] . " WHERE jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$jokerating = $row[0];
		
		$meandiff = $jokerating - $meanrating;
		$meandiffsq = pow($meandiff, 2);
		
		$diffsqtotal += $meandiffsq;
		$diffsqcount++;
	}
	
	if ($diffsqcount == 0)
		$variance = 0;
	else
		$variance = $diffsqtotal / $diffsqcount;
	
	return $variance;
}
?>