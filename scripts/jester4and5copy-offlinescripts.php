<?php $_POST["errormessagetype"] = "text" ?>
<?php require_once("../includes/settings.php") ?>
<?php require_once("../includes/constants.php") ?>
<?php require("../includes/errormessages.php") ?>
<?php require_once("../includes/dbfunctions.php") ?>
<?php require_once("../includes/jokefunctions.php") ?>
<?php require_once("../includes/scriptfunctions.php") ?>
<?php require_once("../includes/generalfunctions.php") ?>
<?php
$sqldb = "jester4and5copy";

/* Remove this if the new jokes/ratings are used */
$removedjokes = array();
$predictjokes = array(5, 7, 8, 13, 15, 16, 17, 18, 19, 20);
$numjokes = 100;
/* End */

ini_set("memory_limit","10000M");

openConnection();
print("Running jester4and5copy Offline Scripts\n\n");

assignOldUsers($connection);

truncateRatings($connection);
markRatedPredictJokes($connection);

clearArrays($connection);
setJester4TableNames($connection);
$usingjester4 = 1;
$usingjester5 = 0;
correl($connection);
genLayer($connection);
genClusters($connection);
genPredictVec($connection);

print("\njester4and5copy Offline Scripts Complete\n");
mysql_close($connection);
?>
<?php
//Only use once
function assignOldUsers($connection)
{
	global $tablenames;
	
	$query = "SELECT userid FROM " . $tablenames["USERS"];
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$userid = $row[0];
		
		$isusingjester4 = 1;
		$isusingjester5 = 0;
		
		$query = "UPDATE " . $tablenames["USERS"] . " SET usingjester4={$isusingjester4}, usingjester5={$isusingjester5} WHERE userid={$userid}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
}

function clearArrays($connection)
{
	global $covariance, $eigenvecs, $meanvector, $countvector, $covnormalizer, $correlnormx, $correlnormy;
	global $eigenvals, $projections, $projectionsforclustering, $useridsforprojections, $layer, $clusters, $useridsforclusters;
	
	$covariance = array();
	$eigenvecs = array();
	$meanvector = array();
	$countvector = array();
	$covnormalizer = array();
	$correlnormx = array();
	$correlnormy = array();
	$eigenvals = array();
	$projections = array();
	$projectionsforclustering = array();
	$useridsforprojections = array();
	$layer = array();
	$clusters = array();
	$useridsforclusters = array();
	
	print("Completed: Arrays Cleared\n");
}

function markRatedPredictJokes($connection)
{
	global $predictjokes, $tablenames;
	
	$query = "SELECT userid, numrated FROM " . $tablenames["USERS"];
	$result = mysql_query($query, $connection);
	if (!$result)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$userid = $row[0];
		$numrated = $row[1];
		
		if ($numrated < count($predictjokes))
		{
			$ratedpredictjokes = 0;
			$ratedpredictjokesandmore = 0;
		}
		else
		{
			$ratedpredictjokes = 1;
			$ratedpredictjokesandmore = 1;
		
			$jokeidarray = array();
		
			$query = "SELECT jokeid FROM " . $tablenames["RATINGS"] . " WHERE userid={$userid}";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

			while ($row = mysql_fetch_array($resultinner))
			{
				$jokeid = $row[0];
				$jokeidarray[] = $jokeid;
			}
		
			foreach ($predictjokes as $predictjokeid)
			{
				if (!in_array($predictjokeid, $jokeidarray))
				{
					$ratedpredictjokes = 0;
					$ratedpredictjokesandmore = 0;
					break;
				}
			}
		
			if (($ratedpredictjokes == 1) && $numrated <= count($predictjokes))
				$ratedpredictjokesandmore = 0;
		}
		
		$query = "UPDATE " . $tablenames["USERS"] . " SET ratedpredictjokes={$ratedpredictjokes}, ratedpredictjokesandmore={$ratedpredictjokesandmore} WHERE userid={$userid}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	print("Completed: Mark Rated Prediction Jokes\n");
}

function truncateRatings($connection)
{
	global $minjokerating, $maxjokerating;
	
	$query = "UPDATE ratings SET jokerating={$minjokerating} WHERE jokerating < {$minjokerating}";
	$resultinner = mysql_query($query, $connection);
	if (!$resultinner)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	$query = "UPDATE ratings SET jokerating={$maxjokerating} WHERE jokerating > {$maxjokerating}";
	$resultinner = mysql_query($query, $connection);
	if (!$resultinner)
		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);

	print("Completed: Truncate Ratings\n");
}

function correl($connection)
{
	global $covariance, $eigenvecs, $meanvector, $countvector, $covnormalizer, $correlnormx, $correlnormy, $eigenvals;
	global $numjokes, $predictjokes, $threshold, $ispredictor, $eigenaxes;
	global $projections, $projectionsforclustering, $useridsforprojections;
	global $tablenames, $usingjester4, $usingjester5;
	
	//Initialize all the arrays that will be used
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		$meanvector[$i] = 0.0; //The mean rating for each joke $i (from users who qualify)
		$countvector[$i] = 0; //The number of ratings for each joke $i (from users who qualify)
	}
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			$covariance[$i][$j] = 0.0;
			$eigenvecs[$i][$j] = 0.0;
			$covnormalizer[$i][$j] = 0;
			$correlnormx[$i][$j] = 0.0;
			$correlnormy[$i][$j] = 0.0;
		}
	}
	
	$query = "SELECT numrated, userid, ratedpredictjokesandmore FROM " . $tablenames["USERS"] . " WHERE (usingjester4={$usingjester4} OR usingjester5={$usingjester5})";
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
		$numrated = $row[0];
		$userid = $row[1];
		$ratedpredictjokesandmore = $row[2];
		
		//Skip user if an error occurs
		
		if ($numrated > $numjokes)
		{
			print($_POST["presystemerror"] . "User " . $userid . " has rated " . $numrated . " jokes." . $_POST["postsystemerror"]);
			continue;
		}
		
		//Skip (do not cluster) user if he/she has not rated all the prediction jokes (and at least one more joke, too)
		//*** Important Note: If the user is not skipped, his/her ratings will factor into the meanvector!
		
		if ($ratedpredictjokesandmore == 0)
		{
			continue;
		}
		
		//Process user ratings
		
		$query = "SELECT jokeid, jokerating FROM " . $tablenames["RATINGS"] . " WHERE userid={$userid}";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
		while ($row = mysql_fetch_array($resultinner))
		{
			$jokeid = $row[0];
			$jokerating = $row[1];
			$jokeindex = $jokeid - 1;
			
			$meanvector[$jokeindex] += $jokerating;
			$countvector[$jokeindex]++;
		}
	}

	//Compile the statistics
	
	for ($i = 0; $i < $numjokes; $i++)
	{
		if ($countvector[$i] != 0)
			$meanvector[$i] = $meanvector[$i] / $countvector[$i];
		//else $meanvector[$i] = 0.0 (neutral rating, because no one has rated it yet)
	}
	
	//Calculate covariance
	
	clearTable($connection, $tablenames["COVARIANCE"]);

	calcCovariance($connection);
	
	//Normalize covariance matrix and store it
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			if ((sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j])) != 0)
				$covariance[$i][$j] = $covariance[$i][$j] / (sqrt($correlnormx[$i][$j]) * sqrt($correlnormy[$i][$j]));
			else //One or both of the jokes was not rated, so $correlnormx[$i][$j] and/or $correlnormy[$i][$j] are equal to zero
				$covariance[$i][$j] = 0; //So, if one or both of the jokes was not rated, there is no correlation
			
			$query = "INSERT INTO " . $tablenames["COVARIANCE"] . " (row, col, covariance) VALUES ({$i}, {$j}, {$covariance[$i][$j]})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	//Calculate eigenvectors and eigenvalues
	
	jacobi(count($predictjokes));
	eigSrt(count($predictjokes));
	
	//Store eigenvectors and eigenvalues
	
	clearTable($connection, $tablenames["EIGENVALUES"]);
	clearTable($connection, $tablenames["EIGENVECTORS"]);
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		$query = "INSERT INTO " . $tablenames["EIGENVALUES"] . " (eigenvalueindex, eigenvalue) VALUES ({$i}, {$eigenvals[$i]})";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	for ($i = 0; $i < count($predictjokes); $i++)
	{
		for ($j = 0; $j < count($predictjokes); $j++)
		{
			$query = "INSERT INTO " . $tablenames["EIGENVECTORS"] . " (row, col, eigenvectorelement) VALUES ({$i}, {$j}, {$eigenvecs[$i][$j]})";
			$result = mysql_query($query, $connection);
			if (!$result)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
	}
	
	//Calculate and store projections
	
	clearTable($connection, $tablenames["PROJECTION"]);
	
	calcProjection($connection, $eigenaxes);
	
	print("Completed: Correlation\n");
}

//Calculate the global covariance matrix and normalization matrices
function calcCovariance($connection)
{
	global $predictjokes, $meanvector, $covariance, $covnormalizer, $correlnormx, $correlnormy, $tablenames, $usingjester4, $usingjester5;
	
	$query = "SELECT userid FROM " . $tablenames["USERS"] . " WHERE ratedpredictjokesandmore=1 AND (usingjester4={$usingjester4} OR usingjester5={$usingjester5})"; //If there are not enough ratings for this user (if he/she has not rated all the prediction set jokes and at least another one), do not try to calculate covariance
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		
	while ($row = mysql_fetch_array($result))
	{
  		$userid = $row[0];
		
		for ($i = 0; $i < count($predictjokes); $i++)
		{
			$jokeid = $predictjokes[$i];
			
			if (isRated($connection, $userid, $jokeid)) //The user may not have necessarily rated every prediction joke 
			{											//as new ones may have been added
				$jokerating = getJokeRating($connection, $userid, $jokeid);
				
				$ratingvect[$i] = $jokerating - $meanvector[$jokeid - 1];
			}
			else
				$ratingvect[$i] = false;
		}
		
		//Calculate covariance matrix for this user
		
		for ($i = 0; $i < count($predictjokes); $i++)
		{
			for ($j = 0; $j < count($predictjokes); $j++)
			{
				if (!(($ratingvect[$i] === false) || ($ratingvect[$j] === false)))
				{
					$covij = $ratingvect[$i] * $ratingvect[$j]; //This represents the covariance of joke i and joke j (for this user)
					$covnormalizer[$i][$j]++; //Keep track of rated contributions to the global covariance matrix
					$correlnormx[$i][$j] += $ratingvect[$i] * $ratingvect[$i];
					$correlnormy[$i][$j] += $ratingvect[$j] * $ratingvect[$j];
				}
				else
				{
					$covij = 0.0; //If the user did not rate one or two of these prediction jokes, the covariance is zero (rather than positive or negative)
				}
				
				$covariance[$i][$j] += $covij; //Add the covariance to the global covariance matrix
			}
		}
	}
}

//Project on eigenplane and store projections
function calcProjection($connection, $numaxes)
{
	global $predictjokes, $eigenvecs, $projections, $projectionsforclustering, $useridsforprojections, $tablenames, $usingjester4, $usingjester5;
	
	$projection = array();
	
	$usercount = 0;
	
	$query = "SELECT userid FROM " . $tablenames["USERS"] . " WHERE ratedpredictjokesandmore=1 AND (usingjester4={$usingjester4} OR usingjester5={$usingjester5})"; //If there are not enough ratings for this user (if he/she has not rated all the prediction set jokes and at least another one), do not try to project	
	$result = mysql_query($query, $connection);
	if (!$result)
   		die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	
	while ($row = mysql_fetch_array($result))
	{
		//$projection does not have to be cleared each time, here, as project clears it
		
		$userid = $row[0];

		project($connection, $projection, $eigenvecs, $userid, $numaxes);
		
		$useridsforprojections[$usercount] = $userid;
		
		for ($axis = 0; $axis < $numaxes; $axis++)
		{
			$projections[$usercount][$axis] = $projection[$axis]; //This will get changed by genLayer
			$projectionsforclustering[$usercount][$axis] = $projection[$axis]; //A version needs to be retained until genClusters
			
			$query = "INSERT INTO " . $tablenames["PROJECTION"] . " (userid, axis, projectionvalue) VALUES ({$userid}, {$axis}, {$projection[$axis]})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
		}
		
		$usercount++;
	}
}

//Calculate the eigenvectors and eigenvalues
function jacobi($n)
{
	global $covariance, $eigenvecs, $eigenvals;
	
	for ($ip = 0; $ip < $n; $ip++)
	{
		for ($iq = 0; $iq < $n; $iq++)
			$eigenvecs[$ip][$iq] = 0.0;
		$eigenvecs[$ip][$ip] = 1.0;
	}
	
	for ($ip = 0; $ip < $n; $ip++)
	{
		$b[$ip] = $covariance[$ip][$ip];
		$eigenvals[$ip] = $covariance[$ip][$ip];
		$z[$ip] = 0.0;
	}
	
	for ($i = 1; $i <= 100; $i++)
	{
		$sm = 0.0;
		
		for ($ip = 0; $ip < ($n - 1); $ip++)
		{
			for ($iq = ($ip + 1); $iq < $n; $iq++)
				$sm += abs($covariance[$ip][$iq]);
		}
		
		if ($sm == 0.0)
		{
			return;
		}
		
		if ($i < 4)
			$tresh = 0.2 * ($sm / ($n * $n));
		else
			$tresh = 0.0;
			
		for ($ip = 0; $ip < ($n - 1); $ip++)
		{
			for ($iq = ($ip + 1); $iq < $n; $iq++)
			{
				$g = 100.0 * abs($covariance[$ip][$iq]);
				
				if (($i > 4) && 
					((abs($eigenvals[$ip]) + $g) == abs($eigenvals[$ip])) && 
					((abs($eigenvals[$iq]) + $g) == abs($eigenvals[$iq])))
				{
					$covariance[$ip][$iq] = 0.0;
				}
				else if (abs($covariance[$ip][$iq]) > $tresh)
				{
					$h = $eigenvals[$iq] - $eigenvals[$ip];
					
					if ((abs($h) + $g) == abs($h))
					{
						if ($h != 0.0)
							$t = ($covariance[$ip][$iq]) / $h;
						else
							$t = 0.0;
					}
					else
					{
						if ($covariance[$ip][$iq] != 0.0)
							$theta = 0.5 * ($h / $covariance[$ip][$iq]);
						else
							$theta = 0.0;
							
						$t = 1.0 / (abs($theta) + sqrt(1.0 + ($theta * $theta)));
						
						if ($theta < 0.0)
							$t = -$t;
					}
					
					$c = 1.0 / sqrt(1 + ($t * $t));
					$s = $t * $c;
					$tau = $s / (1.0 + $c);
					$h = $t * $covariance[$ip][$iq];
					$z[$ip] -= $h;
					$z[$iq] += $h;
					$eigenvals[$ip] -= $h;
					$eigenvals[$iq] += $h;
					$covariance[$ip][$iq] = 0.0;
					
					for ($j = 0; $j < ($ip - 1); $j++)
					{
						rotate($covariance, $j, $ip, $j, $iq, $s, $tau);
					}
					
					for ($j = ($ip + 1); $j < ($iq - 1); $j++)
					{
						rotate($covariance, $ip, $j, $j, $iq, $s, $tau);
					}
					
					for ($j = ($iq + 1); $j < $n; $j++)
					{
						rotate($covariance, $ip, $j, $iq, $j, $s, $tau);
					}
					
					for ($j = 0; $j < $n; $j++)
					{
						rotate($eigenvecs, $j, $ip, $j, $iq, $s, $tau);
					}
				}
			}
		}
		
		for ($ip = 0; $ip < $n; $ip++)
		{
			$b[$ip] = $b[$ip] + $z[$ip];
			$eigenvals[$ip] = $b[$ip];
			$z[$ip] = 0.0;
		}
	}
}

function rotate(&$array, $i, $j, $k, $l, $s, $tau)
{	
	$g = $array[$i][$j];
	$h = $array[$k][$l];
	$array[$i][$j] = $g - ($s * ($h + ($g * $tau)));
	$array[$k][$l] = $h + ($s * ($g - ($h * $tau)));
}

//Sort eigenvectors by rank of eigenvalues
function eigSrt($n)
{
	global $eigenvecs, $eigenvals;
	
	for ($i = 0; $i < $n - 1; $i++)
	{
		$p = $eigenvals[$k = $i];
		
		for ($j = $i + 1; $j < $n; $j++)
		{
			if ($eigenvals[$j] >= $p)
				$p = $eigenvals[$k = $j];
		}
		
		if ($k != $i)
		{
			$eigenvals[$k] = $eigenvals[$i];
			$eigenvals[$i] = $p;
			
			for ($j = 0; $j < $n; $j++)
			{
				$p = $eigenvecs[$j][$i];
				$eigenvecs[$j][$i] = $eigenvecs[$j][$k];
				$eigenvecs[$j][$k] = $p;
			}
		}
	}
}

function genLayer($connection)
{	
	global $projections, $layer, $layersize, $eigenaxes, $tablenames;

	$layer = array_fill(0, $layersize, 0);
	
	//Generate layer
	
	if (count($projections) > 0)
		recurseMedian(0, 0, 0, 0, (count($projections) - 1));
	
	//Store layer
	
	clearTable($connection, $tablenames["LAYER"]);
	
	for ($i = 0; $i < $layersize; $i++)
	{
		$query = "INSERT INTO " . $tablenames["LAYER"] . " (layerindex, layervalue) VALUES ({$i}, {$layer[$i]})";
		$result = mysql_query($query, $connection);
		if (!$result)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
	}
	
	print("Completed: Layer Generation\n");
}

//Sorts the array between the indices $i and $j ($col picks which column of the array should be sorted)
function quickSort(&$arr, $i, $j, $col)
{
	if ($i < $j)
	{
		$k = partition($arr, $i, $j, $col);
		quickSort($arr, $i, $k, $col);
		quickSort($arr, ($k + 1), $j, $col);
	}
}

//Picks a pivot and sorts the array $arr into elements greater than the pivot or elements less than the pivot ($col picks which column of the array should be sorted)
function partition(&$arr, $left, $right, $col)
{
	$pivot = $arr[(int) (($left + $right) / 2)][$col]; //The index needs to be cast to an integer, as an index cannot be a float
	$left--;
	$right++;
	
	while (1)
	{
		do
			$right--;
		while ($arr[$right][$col] > $pivot);
		
		do
			$left++;
		while ($arr[$left][$col] < $pivot);
		
		if ($left < $right)
		{
			$temp = $arr[$left];
			$arr[$left] = $arr[$right];
			$arr[$right] = $temp;
		}
		else
			return $right;
	}
}

//Determines the median of the values $left and $right for the column $col.
function detMedians(&$array, $left, $right, $col)
{
	$above = 0;
	
	$medInd = (int) (($left + $right) / 2); //Needs to be cast to an integer, as an index cannot be a float
	$medValue = $array[$medInd][$col];
	
	while ($array[$medInd + $above][$col] == $medValue)
	{
		$above++;
		
		if (($medInd + $above) >= count($array))
		{
			break;
		}
	}
	
	return ($medInd + $above);
}

//Recursively determines the layer
//layer[0] represents the first vertical cut, which is the median of all the points' horizontal values. layer[1] and layer[2] represent the horizontal cuts on each of those
//areas, which are the median of those areas' points' vertical values. layer[3], layer[4], layer[5], and layer[6] represent the next vertical cuts on each of *those* areas, etc.
function recurseMedian($level, $index, $col, $left, $right)
{
	global $projections, $layer, $clusterlevels;
	
	if ($level == $clusterlevels)
		return;
	
	//Left child
	
	quickSort($projections, $left, $right, $col);
	$midInd = detMedians($projections, $left, $right, $col);
	
	if ($midInd >= count($projections))
		$layer[$index] = 0.0;
	else
		$layer[$index] = $projections[$midInd][$col];
	
	recurseMedian(($level + 1), ((2 * ($index + 1)) - 1), flip($col), $left, ($midInd - 1));

	//Right child
	
	recurseMedian(($level + 1), (2 * ($index + 1)), flip($col), $midInd, $right);
}

function genClusters($connection)
{	
	global $eigenaxes, $layer, $projectionsforclustering, $useridsforprojections, $clusters, $useridsforclusters, $tablenames;
	
	clearTable($connection, $tablenames["CLUSTERS"]);
	
	$usercount = 0;
	$clustercount = 0;
	$clusterindex = 0;

	$clusters = array();
	$useridsforclusters = array();
	
	while ($usercount < count($projectionsforclustering))
	{	
		$cluster = getClusterName($layer, $projectionsforclustering[$usercount]);
		$userid = $useridsforprojections[$usercount];
		
		//This code makes sure that the cluster index is only incremented when a new cluster is found, and that
		//it is temporarily reverted back to the older cluster index if it was already seen

		if (!empty($clusters))
		{
			if (!in_array($cluster, $clusters))
			{
				$clustercount++;
				$clusterindex = $clustercount;
			}
			else
			{
				$clusterindex = array_search($cluster, $clusters);
			}
		}
		//else $clusterindex = 0
		
		$clusters[$clusterindex] = $cluster;
		$useridsforclusters[$clusterindex][] = $userid;
		
		$query = "INSERT INTO " . $tablenames["CLUSTERS"] . " (userid, cluster) VALUES ({$userid}, '{$cluster}')";
		$resultinner = mysql_query($query, $connection);
		if (!$resultinner)
			die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
		$usercount++;
	}
	
	print("Completed: Cluster Generation\n");
}

function genPredictVec($connection)
{
	global $numjokes, $predictjokes, $ispredictor, $clusters, $useridsforclusters, $tablenames;
	
	clearTable($connection, $tablenames["CLUSTERMEANS"]);
	clearTable($connection, $tablenames["PREDICTVECTORS"]);

	$clusterindex = 0;
	
	while ($clusterindex < count($clusters))
	{
		$cluster = $clusters[$clusterindex];
		
		for ($i = 0; $i < $numjokes; $i++)
		{
			$meanclustervector[$i] = 0.0;
			$countclustervector[$i] = 0;
			$jokeindexvector[$i] = $i;
		}

		for ($i = 0; $i < count($useridsforclusters[$clusterindex]); $i++)
		{	
			$userid = $useridsforclusters[$clusterindex][$i];
			$numrated = getNumRated($connection, $userid);
			
			if ($numrated > $numjokes)
			{
				print($_POST["presystemerror"] . "User " . $userid . " has rated " . $numrated . " jokes." . $_POST["postsystemerror"]);
				$numerrors++;
				continue;
			}
			
			for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
			{
				$jokeid = $jokeindex + 1;
				
				if (isRated($connection, $userid, $jokeid))
				{
					$jokerating = getJokeRating($connection, $userid, $jokeid); //The actual rating
					
					$meanclustervector[$jokeindex] += $jokerating;
					$countclustervector[$jokeindex]++;
				}
			}
		}
			
		for ($jokeindex = 0; $jokeindex < $numjokes; $jokeindex++)
		{
			if ($countclustervector[$jokeindex] != 0)
				$meanclustervector[$jokeindex] = ($meanclustervector[$jokeindex] / $countclustervector[$jokeindex]);
			else
				$meanclustervector[$jokeindex] = 0.0;
		}
		
		arsort($meanclustervector);
		
		//Store mean ratings and the prediction vector (joke indices sorted by rank) for this cluster

		$rank = 0;
		
		foreach ($meanclustervector as $jokeindex => $meanrating)
		{
			$jokeid = $jokeindex + 1;
			
			$query = "INSERT INTO " . $tablenames["CLUSTERMEANS"] . " (cluster, jokeid, meanrating) VALUES ('{$cluster}', {$jokeid}, {$meanrating})";
			$resultinner = mysql_query($query, $connection);
			if (!$resultinner)
				die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
			
			if (!$ispredictor[$jokeindex])
			{	
				$query = "INSERT INTO " . $tablenames["PREDICTVECTORS"] . " (cluster, rank, jokeid) VALUES ('{$cluster}', {$rank}, {$jokeid})";
				$resultinner = mysql_query($query, $connection);
				if (!$resultinner)
					die($_POST["preinvalidquery"] . mysql_error() . $_POST["postinvalidquery"]);
				
				$rank++;
			}
		}
		
		$clusterindex++;
	}
	
	print("Completed: Prediction Vector Generation\n");
}
?>