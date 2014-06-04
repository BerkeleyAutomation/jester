<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php
define("UNRATED", -99);
openConnection();
print("Running Similarity Metric Scripts\n\n");
//setDownForMaintenance($connection, 1);
//cosineBasedSimilarityPairs($connection);
//adjustedCosineBasedSimilarityPairs($connection);
//calcJokeCorrelation($connection);
//spearmanCorrelationPairs($connection);
//meanSquaredDifferencePairs($connection);
ephratPairs($connection);
topPairs($connection);
//setDownForMaintenance($connection, 0);
print("\nSimilarity Metric Complete\n");
mysql_close($connection);
?>
<?php
function setDownForMaintenance($connection, $downformaintenance)
{
	$query = "UPDATE maintenance SET downformaintenance={$downformaintenance}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
}

function topPairs($connection)
{	
	/*
	//Cosine:
	
	print "Cosine:\n\n";
		
	$query = "SELECT jokeidi, jokeidj, cosinesimij FROM cosinesim ORDER BY cosinesimij DESC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$jokeidi = $row[0];
		$jokeidj = $row[1];
		$cosinesimij = $row[2];

		print "Joke ID " . $jokeidi . " and Joke ID " . $jokeidj . ": " . $cosinesimij . "\n";
	}
	
	print "\n";

	//Adjusted Cosine:
	
	print "Adjusted Cosine:\n\n";
	
	$query = "SELECT jokeidi, jokeidj, adjustedcosinesimij FROM adjustedcosinesim ORDER BY adjustedcosinesimij DESC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$jokeidi = $row[0];
		$jokeidj = $row[1];
		$adjustedcosinesimij = $row[2];

		print "Joke ID " . $jokeidi . " and Joke ID " . $jokeidj . ": " . $adjustedcosinesimij . "\n";
	}
	
	print "\n";
	
	//Pearson Correlation:
	
	print "Pearson Correlation:\n\n";
	
	$query = "SELECT jokex, jokey, correlxy FROM jokecorrelations ORDER BY correlxy DESC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$jokex = $row[0];
		$jokey = $row[1];
		$correlxy = $row[2];

		print "Joke ID " . $jokex . " and Joke ID " . $jokey . ": " . $correlxy . "\n";
	}
	
	print "\n";
	
	//Spearman Correlation:
	
	print "Spearman Correlation:\n\n";
	
	$query = "SELECT jokex, jokey, correlxy FROM spearmancorrelations ORDER BY correlxy DESC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$jokex = $row[0];
		$jokey = $row[1];
		$correlxy = $row[2];

		print "Joke ID " . $jokex . " and Joke ID " . $jokey . ": " . $correlxy . "\n";
	}
	
	print "\n";
	
	//Mean-squared Difference:
	
	print "Mean-squared Difference:\n\n";
	
	$query = "SELECT jokeidi, jokeidj, msdij FROM msd ORDER BY msdij ASC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$jokeidi = $row[0];
		$jokeidj = $row[1];
		$msdij = $row[2];

		print "Joke ID " . $jokeidi . " and Joke ID " . $jokeidj . ": " . $msdij . "\n";
	}
	
	print "\n";
	*/
	
	//Ephrat's Metric:
	
	print "Ephrat's Metric:\n\n";
	
	$query = "SELECT jokeidi, jokeidj, ephratij FROM ephrat ORDER BY ephratij ASC";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$jokeidi = $row[0];
		$jokeidj = $row[1];
		$ephratij = $row[2];

		print "Joke ID " . $jokeidi . " and Joke ID " . $jokeidj . ": " . $ephratij . "\n";
	}
	
	print "\n";
}

function cosineBasedSimilarityPairs($connection)
{
	global $numjokes;
	
	//$numjokes = 30;

	clearTable($connection, "jokevectors");
	clearTable($connection, "cosinesim");
	
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];
		
		for ($jokeid = 1; $jokeid < ($numjokes + 1); $jokeid++)
		{	
			if (isRated($connection, $userid, $jokeid))
				$rating = getJokeRating($connection, $userid, $jokeid);
			else
				$rating = UNRATED;
				
			$query = "INSERT INTO jokevectors (jokeid, userid, rating) VALUES ({$jokeid}, {$userid}, {$rating})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}

	$jokevectori = array();
	$jokevectorj = array();
	
	for ($jokeidi = 1; $jokeidi < ($numjokes + 1); $jokeidi++)
	{
		if (isRemoved($connection, $jokeidi)) //Does not take into account removed jokes for the final similarity data
			continue;
			
		print "Finished Joke $jokeidi...\n";
		
		fillJokeVector($connection, $jokevectori, $jokeidi);
		
		for ($jokeidj = 1; $jokeidj < ($numjokes + 1); $jokeidj++)
		{
			if (isRemoved($connection, $jokeidj)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			fillJokeVector($connection, $jokevectorj, $jokeidj);
			
			/*
			if ($jokeidi == 30 && $jokeidj == 21)
			{
				print "Joke Vector 30:\n";
				
				foreach ($jokevectori as $key => $value)
				{
					print "jokevectori[$key] = $value\n";
				}
				print "\n";
				
				print "Joke Vector 21:\n";
				
				foreach ($jokevectorj as $key2 => $value2)
				{
					print "jokevectorj[$key2] = $value2\n";
				}
			}
			*/
//			print "CBS of $jokeidi and $jokeidj\n";
			$cosinesimij = cosineBasedSimilarity($jokevectori, $jokevectorj);
			
			$query = "INSERT INTO cosinesim (jokeidi, jokeidj, cosinesimij) VALUES ({$jokeidi}, {$jokeidj}, {$cosinesimij})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
}

function fillJokeVector($connection, &$jokevectori, $jokeidi)
{
	$query = "SELECT userid, rating FROM jokevectors WHERE jokeid={$jokeidi}";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{
		$userid = $row[0];
		$rating = $row[1];

		$jokevectori[$userid] = $rating;
	}
}

function adjustedCosineBasedSimilarityPairs($connection)
{
	global $numjokes;
	
	clearTable($connection, "adjustedcosinesim");
	
	$usermeanratings = array();
	
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];	
		$usermeanratings[$userid] = userMeanRating($connection, $userid); //Takes into account removed jokes for the user's mean
	}
	
	$sum = array_fill(1, $numjokes, array_fill(1, $numjokes, 0));
	$denompresqrti = array_fill(1, $numjokes, array_fill(1, $numjokes, 0));
	$denompresqrtj = array_fill(1, $numjokes, array_fill(1, $numjokes, 0));
	$denom = array_fill(1, $numjokes, array_fill(1, $numjokes, 0));
	
	foreach ($usermeanratings as $userid => $usermeanrating)
	{
		print "Finished User $userid...\n";
		
		$adjustedratings = array();

		for ($jokeid = 1; $jokeid < ($numjokes + 1); $jokeid++)
		{
			if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			if (isRated($connection, $userid, $jokeid))
			{
				$rating = getJokeRating($connection, $userid, $jokeid);
				$adjustedratings[$jokeid] = $rating - $usermeanrating;
			}
			else
				$adjustedratings[$jokeid] = UNRATED;
		}
					
		for ($jokeidi = 1; $jokeidi < ($numjokes + 1); $jokeidi++)
		{
			if (isRemoved($connection, $jokeidi)) //Does not take into account removed jokes for the final similarity data
				continue;
		
			for ($jokeidj = 1; $jokeidj < ($numjokes + 1); $jokeidj++)
			{	
				if (isRemoved($connection, $jokeidj)) //Does not take into account removed jokes for the final similarity data
					continue;
					
				if (!(($adjustedratings[$jokeidi] == UNRATED) || ($adjustedratings[$jokeidj] == UNRATED))) //Only takes into account users who have rated both jokes
				{
					$adjustedratingi = $adjustedratings[$jokeidi];
					$adjustedratingj = $adjustedratings[$jokeidj];

					$adjustedratingij = $adjustedratingi * $adjustedratingj;
					$adjustedratingii = $adjustedratingi * $adjustedratingi;
					$adjustedratingjj = $adjustedratingj * $adjustedratingj;
					
					$num[$jokeidi][$jokeidj] += $adjustedratingij;
					$denompresqrti[$jokeidi][$jokeidj] += $adjustedratingii;
					$denompresqrtj[$jokeidi][$jokeidj] += $adjustedratingjj;
				}
			}
		}
	}
	
	for ($jokeidi = 1; $jokeidi < ($numjokes + 1); $jokeidi++)
	{
		if (isRemoved($connection, $jokeidi)) //Does not take into account removed jokes for the final similarity data
			continue;
	
		for ($jokeidj = 1; $jokeidj < ($numjokes + 1); $jokeidj++)
		{	
			if (isRemoved($connection, $jokeidj)) //Does not take into account removed jokes for the final similarity data
				continue;
		
			$denom[$jokeidi][$jokeidj] = (sqrt($denompresqrti[$jokeidi][$jokeidj]) * sqrt($denompresqrtj[$jokeidi][$jokeidj]));

			if ($denom[$jokeidi][$jokeidj] != 0)
				$adjustedcosinesimij = $num[$jokeidi][$jokeidj] / $denom[$jokeidi][$jokeidj];
			else
				$adjustedcosinesimij = 0;
			
			$query = "INSERT INTO adjustedcosinesim (jokeidi, jokeidj, adjustedcosinesimij) VALUES ({$jokeidi}, {$jokeidj}, {$adjustedcosinesimij})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
}

/*
function oldAdjustedCosineBasedSimilarityPairs($connection)
{
	global $numjokes;
	
	//$numjokes = 30;
	
	clearTable($connection, "adjustedcosinesim");
	
	$usermeanratings = array();
	
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];	
		$usermeanratings[$userid] = userMeanRating($connection, $userid); //Takes into account removed jokes for the user's mean
	}
	
	for ($jokeidi = 1; $jokeidi < ($numjokes + 1); $jokeidi++)
	{
		if (isRemoved($connection, $jokeidi)) //Does not take into account removed jokes for the final similarity data
			continue;
			
		print "Finished Joke $jokeidi...\n";
		
		for ($jokeidj = 1; $jokeidj < ($numjokes + 1); $jokeidj++)
		{
			if (isRemoved($connection, $jokeidi)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			$adjustedcosinesimij = adjustedCosineBasedSimilarity($connection, $jokeidi, $jokeidj, $usermeanratings);
			
			$query = "INSERT INTO adjustedcosinesim (jokeidi, jokeidj, adjustedcosinesimij) VALUES ({$jokeidi}, {$jokeidj}, {$adjustedcosinesimij})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
}
*/

/*
function oldAdjustedCosineBasedSimilarity($connection, $jokeidi, $jokeidj, &$usermeanratings)
{
	global $numjokes;
	
	//$numjokes = 30;
	
	$num = 0;
	$denompresqrti = 0;
	$denompresqrtj = 0;
	
	print "test";
	
	foreach ($usermeanratings as $userid => $usermeanrating)
	{	
		if (isRated($connection, $userid, $jokeidi) && isRated($connection, $userid, $jokeidj)) //Only takes into account users who have rated both jokes
		{
			$ratingi = getJokeRating($connection, $userid, $jokeidi);
			$ratingj = getJokeRating($connection, $userid, $jokeidj);
			
			$adjustedratingi = $ratingi - $usermeanrating;
			$adjustedratingj = $ratingj - $usermeanrating;

			$adjustedratingij = $adjustedratingi * $adjustedratingj;
			$adjustedratingii = $adjustedratingi * $adjustedratingi;
			$adjustedratingjj = $adjustedratingj * $adjustedratingj;

			$num += $adjustedratingij;
			$denompresqrti += $adjustedratingii;
			$denompresqrtj += $adjustedratingjj;
		}
	}
	
	$denom = (sqrt($denompresqrti) * sqrt($denompresqrtj));
	
	if ($denom != 0)
		return $num / $denom;
	else
		return 0;
}
*/

function userMeanRating($connection, $userid)
{
	$ratingcount = 0;
	$ratingsum = 0;
	
	$query = "SELECT jokerating FROM ratings WHERE userid={$userid}";
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

function trimVectors(&$trimmedvectori, &$trimmedvectorj, &$vectori, &$vectorj)
{
	$count = 0;
	
	foreach ($vectori as $i => $value)
	{
		if (!(($vectori[$i] == UNRATED) || ($vectorj[$i] == UNRATED)))
		{
			$trimmedvectori[$count] = $vectori[$i];
			$trimmedvectorj[$count] = $vectorj[$i];
			
			$count++;
		}
	}
}

function cosineBasedSimilarity(&$vectori, &$vectorj)
{		
	$trimmedvectori = array();
	$trimmedvectorj = array();
	
	trimVectors($trimmedvectori, $trimmedvectorj, $vectori, $vectorj);
	
	$num = vectorDotProduct($trimmedvectori, $trimmedvectorj);
//	print "Num: $num\n";
	$denom = vectorMagnitude($trimmedvectori) * vectorMagnitude($trimmedvectorj);
//	print "Denom: $denom\n";
	
	if ($denom != 0)
		return $num / $denom;
	else
		return 0;
}

function vectorMultiplyC(&$arr, $c)
{
	$arrnew = array();
	
	for ($i = 0; $i < count($arr); $i++)
	{
		$arrnew[$i] = $arr[$i] * $c;
	}
	
	return $arrnew;
}

function vectorMagnitude(&$arr)
{	
	$presqrt = 0;
	
	foreach ($arr as $i => $value)
	{
		$presqrt += ($value * $value);
	}
	
	return sqrt($presqrt);
}

function vectorDotProduct(&$arri, &$arrj)
{
	$sum = 0;
	
	if (count($arri) != count($arrj))
	{
		print "Error: You cannot multiply two arrays with lengths that are not equal!";
		exit;
	}
	
	foreach ($arri as $i => $value)
	{
		$sum += ($arri[$i] * $arrj[$i]);
	//	print "$sum\n";
	}
	
	return $sum;
}

function calcJokeCorrelation($connection)
{
	global $numjokes;
	
	$covariance = array();
	$correlnormx = array();
	$correlnormy = array();
	$correlation = array();
	$meanvector = array();

	for ($i = 0; $i < $numjokes; $i++)
	{
		if (isRemoved($connection, $i + 1)) //Does not take into account removed jokes for the final similarity data
			continue;
			
		for ($j = 0; $j < $numjokes; $j++)
		{
			if (isRemoved($connection, $j + 1)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			$covariance[$i][$j] = 0.0;
			$correlnormx[$i][$j] = 0.0;
			$correlnormy[$i][$j] = 0.0;
			$correlation[$i][$j] = 0.0;
		}
	}
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		$jokeid = $i + 1;
		
		if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes for the final similarity data
			continue;
		
		$meanvector[$i] = getMeanRating($connection, $jokeid);
	}
	
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];
		$ratingvect = array();
		
		print "Finished User $userid...\n";

		for ($i = 0; $i < $numjokes; $i++)
		{
			$jokeid = $i + 1;
			
			if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes for the final similarity data
				continue;
			
			if (isRated($connection, $userid, $jokeid))
			{
				$jokerating = getJokeRating($connection, $userid, $jokeid);
				
				$ratingvect[$i] = $jokerating - $meanvector[$i];
			}
			else
			{
				$ratingvect[$i] = false;
			}
		}
		
		for ($i = 0; $i < $numjokes; $i++)
		{
			if (isRemoved($connection, $i + 1)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			for ($j = 0; $j < $numjokes; $j++)
			{
				if (isRemoved($connection, $j + 1)) //Does not take into account removed jokes for the final similarity data
					continue;
					
				if (!(($ratingvect[$i] === false) || ($ratingvect[$j] === false)))
				{
					$covij = $ratingvect[$i] * $ratingvect[$j]; //This represents the covariance of joke i and joke j (for this user)
					$correlnormx[$i][$j] += $ratingvect[$i] * $ratingvect[$i];
					$correlnormy[$i][$j] += $ratingvect[$j] * $ratingvect[$j];
				}
				else
				{
					$covij = 0.0;
				}
				
				$covariance[$i][$j] += $covij;
			}
		}
	}
	
	clearTable($connection, "jokecorrelations");
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		if (isRemoved($connection, $i + 1)) //Does not take into account removed jokes for the final similarity data
			continue;
			
		for ($j = 0; $j < $numjokes; $j++)
		{	
			if (isRemoved($connection, $j + 1)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			if ((sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j])) != 0)
				$correlation[$i][$j] = $covariance[$i][$j] / (sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j]));
			else //One or both of the jokes was not rated, so $correlnormx[$i][$j] and/or $correlnormy[$i][$j] are equal to zero
				$correlation[$i][$j] = 0; //So, if one or both of the jokes was not rated, there is no correlation
				
			$jokex = $i + 1;
			$jokey = $j + 1;
			$correlxy = $correlation[$i][$j];
				
			$query = "INSERT INTO jokecorrelations (jokex, jokey, correlxy) VALUES ({$jokex}, {$jokey}, {$correlxy})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
}

//Rankings *fully* tested
function spearmanCorrelationPairs($connection)
{
	global $numjokes;
	
	$numjokes = 30;
	
	$covariance = array();
	$correlnormx = array();
	$correlnormy = array();
	$correlation = array();
	$meanvector = array();
	
	$users = array();
	
	$query = "SELECT userid FROM users LIMIT 3";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	while ($row = mysql_fetch_array($result))
	{	
		$userid = $row[0];	
		$users[$userid] = $userid;
	}
	
	clearTable($connection, "ranks");
	
	//Highest rating gets rank 1
	//Tied ratings get the average of the ranks for their spot
	
	foreach ($users as $userid)
	{	
		$jokeranks = array();
		$rank = 1;
		$ties = array();
		$tieindex = 0;
		$count = 0;
		
		$prevrating = UNRATED;
		
		$query = "SELECT jokeid, jokerating FROM ratings WHERE userid={$userid} ORDER BY jokerating DESC";
		$result = mysql_query($query, $connection);
		if (!$result)
	   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

		while ($row = mysql_fetch_array($result))
		{
			$jokeid = $row[0];
			$jokerating = $row[1];
			
			$jokeranks[$jokeid] = $rank;
			
			if ($jokerating == $prevrating)
			{
				$ties[$tieindex][$count] = $prevjokeid;
				$ties[$tieindex][$count + 1] = $jokeid;
				$count++;
			}
			else
			{
				$tieindex++;
				$count = 0;
			}
			
			$prevjokeid = $jokeid;
			$prevrating = $jokerating;
			
			$rank++;
		}
		
		foreach ($ties as $tiedjokes)
		{
			$ranksum = 0;
			$rankcount = 0;
			
			foreach ($tiedjokes as $tiedjokeid)
			{
				$ranksum += $jokeranks[$tiedjokeid];
				$rankcount++;
			}
			
			$rankaverage = $ranksum / $rankcount;
			
			foreach ($tiedjokes as $tiedjokeid)
			{
				$jokeranks[$tiedjokeid] = $rankaverage;
			}
		}
		
		foreach ($jokeranks as $jokeid => $rank)
		{
			$query = "INSERT INTO ranks (userid, jokeid, rank) VALUES ({$userid}, {$jokeid}, {$rank})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	//Begin Pearson-like...

	for ($i = 0; $i < $numjokes; $i++)
	{
		if (isRemoved($connection, $i + 1)) //Does not take into account removed jokes for the final similarity data
			continue;
			
		for ($j = 0; $j < $numjokes; $j++)
		{
			if (isRemoved($connection, $j + 1)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			$covariance[$i][$j] = 0.0;
			$correlnormx[$i][$j] = 0.0;
			$correlnormy[$i][$j] = 0.0;
			$correlation[$i][$j] = 0.0;
		}
	}
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		$jokeid = $i + 1;
		
		if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes for the final similarity data
			continue;
		
		$meanvector[$i] = getMeanRank($connection, $jokeid);
		
		if ($jokeid == 8 || $jokeid == 13)
			print "Joke $jokeid rank mean: " . $meanvector[$i] . "\n";
	}
	
	$query = "SELECT userid FROM users LIMIT 3";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];
		$rankvect = array();
		
		print "Finished User $userid...\n";

		for ($i = 0; $i < $numjokes; $i++)
		{
			$jokeid = $i + 1;
			
			if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes for the final similarity data
				continue;
			
			if (isRated($connection, $userid, $jokeid))
			{
				$rank = getJokeRank($connection, $userid, $jokeid);
				
				$rankvect[$i] = $rank - $meanvector[$i];
			}
			else
			{
				$rankvect[$i] = false;
			}
		}
		
		for ($i = 0; $i < $numjokes; $i++)
		{
			if (isRemoved($connection, $i + 1)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			for ($j = 0; $j < $numjokes; $j++)
			{
				if (isRemoved($connection, $j + 1)) //Does not take into account removed jokes for the final similarity data
					continue;
					
				if (!(($rankvect[$i] === false) || ($rankvect[$j] === false)))
				{
					$covij = $rankvect[$i] * $rankvect[$j]; //This represents the covariance of joke i and joke j (for this user)
					$correlnormx[$i][$j] += $rankvect[$i] * $rankvect[$i];
					$correlnormy[$i][$j] += $rankvect[$j] * $rankvect[$j];
				}
				else
				{
					$covij = 0.0;
				}
				
				$covariance[$i][$j] += $covij;
			}
		}
	}
	
	clearTable($connection, "spearmancorrelations");
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		if (isRemoved($connection, $i + 1)) //Does not take into account removed jokes for the final similarity data
			continue;
			
		for ($j = 0; $j < $numjokes; $j++)
		{	
			if (isRemoved($connection, $j + 1)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			if ((sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j])) != 0)
				$correlation[$i][$j] = $covariance[$i][$j] / (sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j]));
			else //One or both of the jokes was not rated, so $correlnormx[$i][$j] and/or $correlnormy[$i][$j] are equal to zero
				$correlation[$i][$j] = 0; //So, if one or both of the jokes was not rated, there is no correlation
				
			$jokex = $i + 1;
			$jokey = $j + 1;
			$correlxy = $correlation[$i][$j];
				
			$query = "INSERT INTO spearmancorrelations (jokex, jokey, correlxy) VALUES ({$jokex}, {$jokey}, {$correlxy})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
}

function getJokeRank($connection, $userid, $jokeid)
{
	$query = "SELECT rank FROM ranks WHERE userid={$userid} AND jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	if (mysql_num_rows($result) == 0)
	{
		die($_POST["presystemerror"] . "Ranking not found. Please try again later." . $_POST["postsystemerror"]);
	}
	else if (mysql_num_rows($result) > 1)
	{
		die($_POST["presystemerror"] . "Repeated ranking in database. Please try again later." . $_POST["postsystemerror"]);
	}
	else
	{
		$row = mysql_fetch_row($result);
		$rank = $row[0];
		
		return $rank;
	}
}

function getMeanRank($connection, $jokeid)
{
	$rankcount = 0;
	$ranksum = 0;
	
	$query = "SELECT rank FROM ranks WHERE jokeid={$jokeid}";
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	while ($row = mysql_fetch_array($result))
	{
		$rank = $row[0];
		
		$ranksum += $rank;
		$rankcount++;
	}
	
	if ($rankcount == 0)
		$meanrank = 0;
	else
		$meanrank = $ranksum / $rankcount;
	
	return $meanrank;
}

function meanSquaredDifferencePairs($connection)
{
	global $numjokes;
	
	clearTable($connection, "msd");
	
	$num = array_fill(1, $numjokes, array_fill(1, $numjokes, 0));
	$denom = array_fill(1, $numjokes, array_fill(1, $numjokes, 0));
	
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];
		
		print "Finished User $userid...\n";
		
		$ratings = array();
		
		for ($jokeid = 1; $jokeid < ($numjokes + 1); $jokeid++)
		{
			if (isRemoved($connection, $jokeid)) //Does not take into account removed jokes for the final similarity data
				continue;

			if (isRated($connection, $userid, $jokeid))
			{
				$rating = getJokeRating($connection, $userid, $jokeid);
				$ratings[$jokeid] = $rating;
			}
			else
				$ratings[$jokeid] = UNRATED;
		}

		for ($jokeidi = 1; $jokeidi < ($numjokes + 1); $jokeidi++)
		{
			if (isRemoved($connection, $jokeidi)) //Does not take into account removed jokes for the final similarity data
				continue;

			for ($jokeidj = 1; $jokeidj < ($numjokes + 1); $jokeidj++)
			{	
				if (isRemoved($connection, $jokeidj)) //Does not take into account removed jokes for the final similarity data
					continue;

				if (!(($ratings[$jokeidi] == UNRATED) || ($ratings[$jokeidj] == UNRATED))) //Only takes into account users who have rated both jokes
				{
					$ratingi = $ratings[$jokeidi];
					$ratingj = $ratings[$jokeidj];
					$difference = $ratingi - $ratingj;

					$num[$jokeidi][$jokeidj] += ($difference * $difference);
					$denom[$jokeidi][$jokeidj]++;
				}
			}
		}
	}
	
	for ($jokeidi = 1; $jokeidi < ($numjokes + 1); $jokeidi++)
	{
		if (isRemoved($connection, $jokeidi)) //Does not take into account removed jokes for the final similarity data
			continue;
	
		for ($jokeidj = 1; $jokeidj < ($numjokes + 1); $jokeidj++)
		{	
			if (isRemoved($connection, $jokeidj)) //Does not take into account removed jokes for the final similarity data
				continue;

			if ($denom[$jokeidi][$jokeidj] != 0)
				$msdij = $num[$jokeidi][$jokeidj] / $denom[$jokeidi][$jokeidj];
			else
				$msdij = 0;
			
			$query = "INSERT INTO msd (jokeidi, jokeidj, msdij) VALUES ({$jokeidi}, {$jokeidj}, {$msdij})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
}

function ephratPairs($connection)
{
	global $numjokes;
	global $predictjokes;
	
	clearTable($connection, "ephrat");
	
	$sum = array_fill(1, $numjokes, array_fill(1, $numjokes, 0));
	$intersect = array_fill(1, $numjokes, array_fill(1, $numjokes, 0)); //For each joke pair, the number of users who have rated both jokes
	$union = array_fill(1, $numjokes, array_fill(1, $numjokes, 0)); //For each joke pair, the number of users who have rated one of the jokes
	
	$query = "SELECT userid FROM users";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];

		print "Finished User $userid...\n";
		
		$ratings = array();
		
		for ($jokeid = 1; $jokeid < ($numjokes + 1); $jokeid++)
		{
			if (isRemoved($connection, $jokeid) || in_array($jokeid, $predictjokes)) //Does not take into account removed jokes for the final similarity data
				continue;

			if (isRated($connection, $userid, $jokeid))
			{
				$rating = getJokeRating($connection, $userid, $jokeid);
				$ratings[$jokeid] = $rating;
			}
			else
				$ratings[$jokeid] = UNRATED;
		}

		for ($jokeidi = 1; $jokeidi < ($numjokes + 1); $jokeidi++)
		{
			if (isRemoved($connection, $jokeidi) || in_array($jokeidi, $predictjokes)) //Does not take into account removed jokes for the final similarity data
				continue;

			for ($jokeidj = 1; $jokeidj < ($numjokes + 1); $jokeidj++)
			{	
				if (isRemoved($connection, $jokeidj) || in_array($jokeidj, $predictjokes)) //Does not take into account removed jokes for the final similarity data
					continue;

				if (!(($ratings[$jokeidi] == UNRATED) || ($ratings[$jokeidj] == UNRATED))) //If both jokes have been rated by the user, add to the intersect and the union
				{
					$ratingi = $ratings[$jokeidi];
					$ratingj = $ratings[$jokeidj];
					$difference = $ratingi - $ratingj;

					$sum[$jokeidi][$jokeidj] += abs($difference);
					$intersect[$jokeidi][$jokeidj]++;
					$union[$jokeidi][$jokeidj]++;
				}
				else if (($ratings[$jokeidi] != UNRATED) || ($ratings[$jokeidj] != UNRATED)) //If one of the jokes has been rated by the user, add to the union (but not the intersect)
				{
					$union[$jokeidi][$jokeidj]++;
				}
			}
		}
	}
	
	for ($jokeidi = 1; $jokeidi < ($numjokes + 1); $jokeidi++)
	{
		if (isRemoved($connection, $jokeidi) || in_array($jokeidi, $predictjokes)) //Does not take into account removed jokes for the final similarity data
			continue;
	
		for ($jokeidj = 1; $jokeidj < ($numjokes + 1); $jokeidj++)
		{	
			if (isRemoved($connection, $jokeidj) || in_array($jokeidj, $predictjokes)) //Does not take into account removed jokes for the final similarity data
				continue;
				
			if ($union[$jokeidi][$jokeidj] != 0)
				$ephratij = (1 - ($intersect[$jokeidi][$jokeidj]/$union[$jokeidi][$jokeidj])) * $sum[$jokeidi][$jokeidj];
			else
				$ephratij = 0;
			
			$query = "INSERT INTO ephrat (jokeidi, jokeidj, ephratij) VALUES ({$jokeidi}, {$jokeidj}, {$ephratij})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
}
?>